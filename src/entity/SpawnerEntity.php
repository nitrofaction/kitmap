<?php

namespace Kitmap\entity;

use Kitmap\handler\Cache;
use Kitmap\handler\Faction;
use Kitmap\handler\Job;
use Kitmap\Util;
use pocketmine\entity\animation\HurtAnimation;
use pocketmine\entity\Attribute;
use pocketmine\entity\EntitySizeInfo;
use pocketmine\entity\Living;
use pocketmine\entity\Location;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\entity\EntityDeathEvent;
use pocketmine\item\StringToItemParser;
use pocketmine\item\VanillaItems;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\network\mcpe\protocol\AddActorPacket;
use pocketmine\network\mcpe\protocol\types\entity\Attribute as NetworkAttribute;
use pocketmine\network\mcpe\protocol\types\entity\EntityIds;
use pocketmine\network\mcpe\protocol\types\entity\PropertySyncData;
use pocketmine\player\Player;

class SpawnerEntity extends Living
{
    protected int $stack;
    protected string $networkTypeId;

    public function __construct(Location $location, ?CompoundTag $nbt = null)
    {
        $this->networkTypeId = $nbt->getString("id", static::getNetworkTypeId());
        $this->stack = $nbt->getInt("stack", 1);

        parent::__construct($location, $nbt);
        $this->setNameTagAlwaysVisible();
    }

    public static function getNetworkTypeId(): string
    {
        return EntityIds::AGENT;
    }

    public function getName(): string
    {
        return Cache::$config["entities"][$this->networkTypeId]["name"];
    }

    public function saveNBT(): CompoundTag
    {
        $nbt = parent::saveNBT();
        $nbt->setString("id", $this->networkTypeId);
        $nbt->setInt("stack", $this->stack);
        return $nbt;
    }

    public function attack(EntityDamageEvent $source): void
    {
        $this->updateNametag();

        if ($source->isCancelled()) {
            return;
        }

        if ($source instanceof EntityDamageByEntityEvent) {
            $source->setKnockBack(0);
        }

        $this->broadcastAnimation(new HurtAnimation($this));

        if ($source->getFinalDamage() >= $this->getHealth()) {
            if ($this->getStackSize() > 1) {
                $source->cancel();
                $this->onDeath();
                return;
            }
        }

        parent::attack($source);
    }

    public function updateNametag(): void
    {
        $this->setNameTag("§n" . $this->getFrenchName() . "s §7[x§n" . $this->getStackSize() . "§7]\n§7" . round($this->getHealth(), 2) . " §c❤");
    }

    public function getFrenchName(): string
    {
        return Cache::$config["entities"][$this->networkTypeId]["french"];
    }

    public function getStackSize(): int
    {
        return min($this->getStackLimit(), $this->stack);
    }

    private function getStackLimit(): int
    {
        $world = $this->getWorld()->getFolderName();

        if (!str_starts_with($world, "island-")) {
            return 999;
        }

        $faction = substr($world, 7);
        return Faction::getIslandMobsLimit($faction);
    }

    public function onDeath(): void
    {
        if ($this->getStackSize() > 1) {
            $this->addStackSize(-1);
            $this->setHealth($this->getMaxHealth());
        }

        $ev = new EntityDeathEvent($this, $this->getDrops(), $this->getXpDropAmount());
        $ev->call();

        $cause = $this->getLastDamageCause();
        $dropped = false;

        if ($cause instanceof EntityDamageByEntityEvent) {
            $damager = $cause->getDamager();

            if ($damager instanceof Player) {
                Job::addXp($damager, "Assassin", mt_rand(1, 6));

                foreach ($ev->getDrops() as $item) {
                    Util::addItem($damager, $item);
                }

                $damager->getXpManager()->addXp($ev->getXpDropAmount());
                $dropped = true;
            }
        }

        if (!$dropped) {
            foreach ($ev->getDrops() as $item) {
                $this->getWorld()->dropItem($this->location, $item);
            }

            $this->getWorld()->dropExperience($this->location, $ev->getXpDropAmount());
        }

        $this->startDeathAnimation();
    }

    public function addStackSize(int $stack): void
    {
        $this->setStackSize($this->getStackSize() + $stack);
    }

    public function setStackSize(int $stack): void
    {
        $this->stack = max(1, $stack);
    }

    public function getDrops(): array
    {
        $drops = Cache::$config["entities"][$this->networkTypeId]["drops"];
        $itemDrops = [];

        foreach ($drops as $drop) {
            [$chances, $itemName, $number] = explode(":", $drop);
            $item = StringToItemParser::getInstance()->parse($itemName) ?? VanillaItems::AIR();

            if (str_contains($number, ",")) {
                [$min, $max] = explode(",", $number);
                $item = $item->setCount(mt_rand(intval($min), intval($max)));
            } else {
                $item = $item->setCount(intval($number));
            }

            if (1 > $item->getCount()) {
                continue;
            }

            if (str_contains($chances, ",")) {
                [$min, $max] = explode(",", $chances);

                if (mt_rand(intval($min), intval($max)) !== intval($min)) {
                    continue;
                }
            }

            $itemDrops[] = $item;
        }

        return $itemDrops;
    }

    public function getXpDropAmount(): int
    {
        return floor(Cache::$config["entities"][$this->networkTypeId]["xp"]);
    }

    public function startDeathAnimation(): void
    {
        if (!$this->isAlive()) {
            parent::startDeathAnimation();
        }
    }

    public function kill(): void
    {
        if ($this->getStackSize() > 1) {
            // for ($i = 1; $i <= $this->getStackSize(); $i++) {
            $this->onDeath();
            // }
        }
        parent::kill();
    }

    public function onUpdate(int $currentTick): bool
    {
        if ($this->closed) {
            return false;
        }

        $this->updateNametag();
        return parent::onUpdate($currentTick);
    }

    public function getCustomNetworkTypeId(): string
    {
        return $this->networkTypeId;
    }

    protected function getInitialSizeInfo(): EntitySizeInfo
    {
        return new EntitySizeInfo(
            Cache::$config["entities"][$this->networkTypeId]["height"],
            Cache::$config["entities"][$this->networkTypeId]["width"]
        );
    }

    protected function sendSpawnPacket(Player $player): void
    {
        $player->getNetworkSession()->sendDataPacket(AddActorPacket::create(
            $this->getId(),
            $this->getId(),
            $this->networkTypeId,
            $this->location->asVector3(),
            $this->getMotion(),
            $this->location->pitch,
            $this->location->yaw,
            $this->location->yaw,
            $this->location->yaw,
            array_map(function (Attribute $attr): NetworkAttribute {
                return new NetworkAttribute($attr->getId(), $attr->getMinValue(), $attr->getMaxValue(), $attr->getValue(), $attr->getDefaultValue(), []);
            }, $this->attributeMap->getAll()),
            $this->getAllNetworkData(),
            new PropertySyncData([], []),
            []
        ));
    }

    public function getMaxHealth(): int
    {
        return 10;
    }
}