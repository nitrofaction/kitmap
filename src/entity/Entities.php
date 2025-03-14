<?php

namespace Kitmap\entity;

use Closure;
use Kitmap\entity\animation\Message;
use Kitmap\entity\animation\Pack;
use Kitmap\entity\animation\PackItem;
use Kitmap\entity\floating\DynamicFloatingText;
use Kitmap\entity\floating\LeaderboardFloatingText;
use Kitmap\entity\npc\BlackSmith;
use Kitmap\entity\npc\CmdEntity;
use Kitmap\entity\npc\LogoutEntity;
use Kitmap\entity\npc\Merchant;
use Kitmap\entity\npc\Quest;
use Kitmap\entity\npc\TopEntity;
use pocketmine\block\RuntimeBlockStateRegistry;
use pocketmine\data\bedrock\PotionTypeIdMap;
use pocketmine\data\bedrock\PotionTypeIds;
use pocketmine\data\SavedDataLoadingException;
use pocketmine\entity\Entity;
use pocketmine\entity\EntityDataHelper as Helper;
use pocketmine\entity\EntityFactory;
use pocketmine\entity\object\ItemEntity;
use pocketmine\item\Item;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\nbt\tag\ListTag;
use pocketmine\network\mcpe\cache\StaticPacketCache;
use pocketmine\network\mcpe\protocol\AvailableActorIdentifiersPacket;
use pocketmine\network\mcpe\protocol\types\CacheableNbt;
use pocketmine\world\World;
use ReflectionClass;

class Entities
{
    const NPC_TAG = "nitro_npc";

    public function __construct()
    {
        EntityFactory::getInstance()->register(AntiBackBall::class, function (World $world, CompoundTag $nbt): AntiBackBall {
            return new AntiBackBall(Helper::parseLocation($nbt, $world), null, $nbt);
        }, ["AntiBackBallEntity"]);

        EntityFactory::getInstance()->register(SwitchBall::class, function (World $world, CompoundTag $nbt): SwitchBall {
            return new SwitchBall(Helper::parseLocation($nbt, $world), null, $nbt);
        }, ["SwitcherEntity"]);

        EntityFactory::getInstance()->register(LogoutEntity::class, function (World $world, CompoundTag $nbt): LogoutEntity {
            return new LogoutEntity(Helper::parseLocation($nbt, $world), LogoutEntity::parseSkinNBT($nbt), $nbt);
        }, ["LogoutEntity"]);

        EntityFactory::getInstance()->register(Nexus::class, function (World $world, CompoundTag $nbt): Nexus {
            return new Nexus(Helper::parseLocation($nbt, $world), $nbt);
        }, ["NexusEntity"]);

        EntityFactory::getInstance()->register(DynamicFloatingText::class, function (World $world, CompoundTag $nbt): DynamicFloatingText {
            return new DynamicFloatingText(Helper::parseLocation($nbt, $world), $nbt);
        }, ["DynamicFloatingText"]);

        EntityFactory::getInstance()->register(LeaderboardFloatingText::class, function (World $world, CompoundTag $nbt): LeaderboardFloatingText {
            return new LeaderboardFloatingText(Helper::parseLocation($nbt, $world), $nbt);
        }, ["LeaderboardFloatingText"]);

        EntityFactory::getInstance()->register(GhostBlock::class, function (World $world, CompoundTag $nbt): GhostBlock {
            return new GhostBlock(Helper::parseLocation($nbt, $world), GhostBlock::parseBlockNBT(RuntimeBlockStateRegistry::getInstance(), $nbt), $nbt);
        }, ["GhostBlock"]);

        EntityFactory::getInstance()->register(Message::class, function (World $world, CompoundTag $nbt): Message {
            return new Message(Helper::parseLocation($nbt, $world), $nbt);
        }, ["MessageEntity"]);

        EntityFactory::getInstance()->register(EnderPearl::class, function (World $world, CompoundTag $nbt): EnderPearl {
            return new EnderPearl(Helper::parseLocation($nbt, $world), null, $nbt);
        }, ["ThrownEnderpearl", "minecraft:ender_pearl"]);

        EntityFactory::getInstance()->register(SplashPotion::class, function (World $world, CompoundTag $nbt): SplashPotion {
            $potionType = PotionTypeIdMap::getInstance()->fromId($nbt->getShort(SplashPotion::TAG_POTION_ID, PotionTypeIds::WATER));
            if ($potionType === null) throw new SavedDataLoadingException("No such potion type");
            return new SplashPotion(Helper::parseLocation($nbt, $world), null, $potionType, $nbt);
        }, ["ThrownPotion", "minecraft:potion", "thrownpotion"]);

        EntityFactory::getInstance()->register(PackItem::class, function (World $world, CompoundTag $nbt): PackItem {
            $itemTag = $nbt->getCompoundTag(ItemEntity::TAG_ITEM);
            $item = Item::nbtDeserialize($itemTag);

            if ($itemTag === null) throw new SavedDataLoadingException("Expected \"" . ItemEntity::TAG_ITEM . "\" NBT tag not found");
            if ($item->isNull()) throw new SavedDataLoadingException("Item is invalid");

            return new PackItem(Helper::parseLocation($nbt, $world), $item, $nbt);
        }, ["Pack"]);

        EntityFactory::getInstance()->register(CmdEntity::class, function (World $world, CompoundTag $nbt): CmdEntity {
            return new CmdEntity(Helper::parseLocation($nbt, $world), CmdEntity::parseSkinNBT($nbt), $nbt);
        }, ["Cmd"]);

        EntityFactory::getInstance()->register(TopEntity::class, function (World $world, CompoundTag $nbt): TopEntity {
            return new TopEntity(Helper::parseLocation($nbt, $world), TopEntity::parseSkinNBT($nbt), $nbt);
        }, ["Top"]);

        EntityFactory::getInstance()->register(BlackSmith::class, function (World $world, CompoundTag $nbt): BlackSmith {
            return new BlackSmith(Helper::parseLocation($nbt, $world), $nbt);
        }, ["BlackSmith"]);

        EntityFactory::getInstance()->register(Quest::class, function (World $world, CompoundTag $nbt): Quest {
            return new Quest(Helper::parseLocation($nbt, $world), $nbt);
        }, ["Quest"]);

        EntityFactory::getInstance()->register(Merchant::class, function (World $world, CompoundTag $nbt): Merchant {
            return new Merchant(Helper::parseLocation($nbt, $world), $nbt);
        }, ["Merchant"]);

        EntityFactory::getInstance()->register(SpawnerEntity::class, function (World $world, CompoundTag $nbt): SpawnerEntity {
            return new SpawnerEntity(Helper::parseLocation($nbt, $world), $nbt);
        }, ["SpawnerEntity"]);

        $this->registerEntity(Pack::class, "nitro:pack");
    }

    public function registerEntity(string $className, string $identifier, ?Closure $creationFunc = null, string $behaviourId = ""): void
    {
        EntityFactory::getInstance()->register($className, $creationFunc ?? static function (World $world, CompoundTag $nbt) use ($className): Entity {
            return new $className(Helper::parseLocation($nbt, $world), $nbt);
        }, [$identifier]);

        $this->updateStaticPacketCache($identifier, $behaviourId);
    }

    private function updateStaticPacketCache(string $identifier, string $behaviourId): void
    {
        $instance = StaticPacketCache::getInstance();
        $property = (new ReflectionClass($instance))->getProperty("availableActorIdentifiers");

        /** @var AvailableActorIdentifiersPacket $packet */
        $packet = $property->getValue($instance);

        /** @var CompoundTag $root */
        $root = $packet->identifiers->getRoot();

        ($root->getListTag("idlist") ?? new ListTag())->push(CompoundTag::create()
            ->setString("id", $identifier)
            ->setString("bid", $behaviourId));

        $packet->identifiers = new CacheableNbt($root);
    }
}