<?php

namespace Kitmap\item;

use Kitmap\task\repeat\child\GamblingTask;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\item\Durable as PmDurable;
use pocketmine\player\Player;

class Sword extends Durable
{
    public function __construct(private readonly int $maxDurability = -1, private readonly int $attackPoints = -1)
    {
    }

    public function onAttack(EntityDamageEvent $event, ?Player $player = null): bool
    {
        if ($this->attackPoints > 0) {
            $event->setBaseDamage($this->getAttackPoints());
        }

        if ($player instanceof Player) {
            $item = $player->getInventory()->getItemInHand();

            if (
                $item instanceof PmDurable &&
                !in_array($player->getName(), GamblingTask::$players) &&
                !is_null($item->getNamedTag()->getTag("menu_item"))
            ) {
                $item->setDamage(999999);
                $player->getInventory()->setItemInHand($item);
            }
        }

        return false;
    }

    public function getAttackPoints(): int
    {
        return $this->attackPoints;
    }

    public function getMaxDurability(): int
    {
        return $this->maxDurability;
    }

    public function isRare(): bool
    {
        return true;
    }
}