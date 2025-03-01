<?php

namespace Kitmap\entity;

use Closure;
use Kitmap\entity\animation\DynamicFloatingText;
use Kitmap\entity\animation\Message;
use Kitmap\entity\animation\Pack;
use Kitmap\entity\animation\PackItem;
use pocketmine\block\RuntimeBlockStateRegistry;
use pocketmine\data\bedrock\PotionTypeIdMap;
use pocketmine\data\bedrock\PotionTypeIds;
use pocketmine\data\SavedDataLoadingException;
use pocketmine\entity\Entity;
use pocketmine\entity\EntityDataHelper;
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
    public function __construct()
    {
        EntityFactory::getInstance()->register(AntiBackBall::class, function (World $world, CompoundTag $nbt): AntiBackBall {
            return new AntiBackBall(EntityDataHelper::parseLocation($nbt, $world), null, $nbt);
        }, ["AntiBackBallEntity"]);

        EntityFactory::getInstance()->register(LogoutNpc::class, function (World $world, CompoundTag $nbt): LogoutNpc {
            return new LogoutNpc(EntityDataHelper::parseLocation($nbt, $world), LogoutNpc::parseSkinNBT($nbt), $nbt);
        }, ["LogoutEntity"]);

        EntityFactory::getInstance()->register(Nexus::class, function (World $world, CompoundTag $nbt): Nexus {
            return new Nexus(EntityDataHelper::parseLocation($nbt, $world), $nbt);
        }, ["NexusEntity"]);

        EntityFactory::getInstance()->register(ElevatorPhantom::class, function (World $world, CompoundTag $nbt): ElevatorPhantom {
            return new ElevatorPhantom(EntityDataHelper::parseLocation($nbt, $world), $nbt);
        }, ["ElevatorPhantom"]);

        EntityFactory::getInstance()->register(SwitchBall::class, function (World $world, CompoundTag $nbt): SwitchBall {
            return new SwitchBall(EntityDataHelper::parseLocation($nbt, $world), null, $nbt);
        }, ["SwitcherEntity"]);

        EntityFactory::getInstance()->register(DynamicFloatingText::class, function (World $world, CompoundTag $nbt): DynamicFloatingText {
            return new DynamicFloatingText(EntityDataHelper::parseLocation($nbt, $world), $nbt);
        }, ["FloatingText"]);

        EntityFactory::getInstance()->register(BlackSmith::class, function (World $world, CompoundTag $nbt): BlackSmith {
            return new BlackSmith(EntityDataHelper::parseLocation($nbt, $world), $nbt);
        }, ["Forgeron"]);

        EntityFactory::getInstance()->register(EnderPearl::class, function (World $world, CompoundTag $nbt): EnderPearl {
            return new EnderPearl(EntityDataHelper::parseLocation($nbt, $world), null, $nbt);
        }, ["ThrownEnderpearl", "minecraft:ender_pearl"]);

        EntityFactory::getInstance()->register(GhostBlock::class, function (World $world, CompoundTag $nbt): GhostBlock {
            return new GhostBlock(EntityDataHelper::parseLocation($nbt, $world), GhostBlock::parseBlockNBT(RuntimeBlockStateRegistry::getInstance(), $nbt), $nbt);
        }, ["GhostBlock"]);

        EntityFactory::getInstance()->register(Message::class, function (World $world, CompoundTag $nbt): Message {
            return new Message(EntityDataHelper::parseLocation($nbt, $world), $nbt);
        }, ["MessageEntity"]);

        EntityFactory::getInstance()->register(SplashPotion::class, function (World $world, CompoundTag $nbt): SplashPotion {
            $potionType = PotionTypeIdMap::getInstance()->fromId($nbt->getShort(SplashPotion::TAG_POTION_ID, PotionTypeIds::WATER));
            if ($potionType === null) throw new SavedDataLoadingException("No such potion type");
            return new SplashPotion(EntityDataHelper::parseLocation($nbt, $world), null, $potionType, $nbt);
        }, ["ThrownPotion", "minecraft:potion", "thrownpotion"]);

        EntityFactory::getInstance()->register(PackItem::class, function (World $world, CompoundTag $nbt): PackItem {
            $itemTag = $nbt->getCompoundTag(ItemEntity::TAG_ITEM);
            $item = Item::nbtDeserialize($itemTag);

            if ($itemTag === null) throw new SavedDataLoadingException("Expected \"" . ItemEntity::TAG_ITEM . "\" NBT tag not found");
            if ($item->isNull()) throw new SavedDataLoadingException("Item is invalid");

            return new PackItem(EntityDataHelper::parseLocation($nbt, $world), $item, $nbt);
        }, ["Pack"]);

        $this->registerEntity(Pack::class, "nitro:pack");
    }

    public function registerEntity(string $className, string $identifier, ?Closure $creationFunc = null, string $behaviourId = ""): void
    {
        EntityFactory::getInstance()->register($className, $creationFunc ?? static function (World $world, CompoundTag $nbt) use ($className): Entity {
            return new $className(EntityDataHelper::parseLocation($nbt, $world), $nbt);
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