<?php

namespace Kitmap\entity\floating;

use Kitmap\handler\Rank;
use Kitmap\Util;
use pocketmine\event\player\PlayerDeathEvent;
use pocketmine\item\Item;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\player\Player;

class StuffFloating extends FloatingText
{
    private int $timeBeforeClose = 30;
    private string $protectedBy = "";

    public static function lockStuff(Player $victim, Player $damager, PlayerDeathEvent $event): void
    {
        $drops = $event->getdrops();
        $pos = $victim->getLocation();

        $event->setDrops([]);

        if (1 > count($drops)) {
            return;
        }

        $rank = Rank::getRank($damager->getName());
        $seconds = Rank::getRankValue(Rank::getEqualRankByString($rank), "stuff");

        if (1 > $seconds) {
            foreach ($drops as $item) {
                if ($item instanceof Item) {
                    $pos->getWorld()->dropItem($pos, $item);
                }
            }
            return;
        }

        $entity = new StuffFloating($pos);
        $entity->setTimeBeforeClose($seconds);
        $entity->setProtectedBy($damager->getName());
        $entity->spawnToAll();

        foreach ($drops as $item) {
            if ($item instanceof Item) {
                $item->getNamedTag()->setString("lockBy", $damager->getName());
                $item->getNamedTag()->setInt("lockWhile", time() + $seconds);
                $pos->getWorld()->dropItem($pos, $item);
            }
        }
    }

    public function setTimeBeforeClose(int $timeBeforeClose): void
    {
        $this->timeBeforeClose = $timeBeforeClose;
    }

    public function setProtectedBy(string $protectedBy): void
    {
        $this->protectedBy = $protectedBy;
    }

    protected function initEntity(CompoundTag $nbt): void
    {
        parent::initEntity($nbt);
        $this->setCanSaveWithChunk(false);
    }

    protected function getPeriod(): ?int
    {
        return $this->period;
    }

    protected function getUpdate(): string
    {
        if ($this->timeBeforeClose <= 0) {
            $this->flagForDespawn();
            return "";
        } else {
            $this->timeBeforeClose--;
            return Util::PREFIX . "Stuff protégé par: §n" . $this->protectedBy . Util::IARROW . "\n§fTemps restant: §n" . $this->timeBeforeClose . "§fs";
        }
    }
}