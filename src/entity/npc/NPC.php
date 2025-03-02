<?php

namespace Kitmap\entity\npc;

use Kitmap\command\staff\op\Elevator;
use Kitmap\handler\Cache;
use Kitmap\Util;
use pocketmine\entity\Human;
use pocketmine\entity\Location;
use pocketmine\entity\Skin;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\player\Player;

class NPC extends Human
{
    private string $npcIdentifier;

    public function __construct(Location $location, Skin $skin, ?CompoundTag $nbt = null)
    {
        parent::__construct($location, $skin, $nbt);
        $this->npcIdentifier = $nbt->getString("npc", "");
    }

    public function attack(EntityDamageEvent $source): void
    {
        $source->cancel();

        if ($source instanceof EntityDamageByEntityEvent) {
            $damager = $source->getDamager();

            if ($damager instanceof Player && isset($this->npcIdentifier)) {
                $damager->chat("/" . $this->npcIdentifier);
            }
        }
    }

    public function getName(): string
    {
        return "NPC";
    }

    public function saveNBT(): CompoundTag
    {
        $nbt = parent::saveNBT();
        $nbt->setString("npc", $this->npcIdentifier);
        return $nbt;
    }

    protected function initEntity(CompoundTag $nbt): void
    {
        parent::initEntity($nbt);

        $this->setNameTagAlwaysVisible();

        $this->npcIdentifier = $nbt->getString("npc", "");

        if (!isset($this->npcIdentifier) || $this->npcIdentifier === "") {
            $this->flagForDespawn();
            return;
        }

        $data = Cache::$config["npc"][$this->npcIdentifier] ?? null;

        if (is_null($data)) {
            $this->flagForDespawn();
            return;
        }

        [, , , , $name, $description,] = explode(":", $data);

        $this->setNameTag(Util::PREFIX . $name . Util::IARROW . "\n§f" . $description);
    }
}