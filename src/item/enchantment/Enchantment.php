<?php

namespace Kitmap\item\enchantment;

use pocketmine\event\block\BlockBreakEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\entity\EntityDeathEvent;
use pocketmine\item\enchantment\EnchantmentInstance;
use pocketmine\player\Player;

class Enchantment
{
    public function onBreak(BlockBreakEvent $event, EnchantmentInstance $enchantmentInstance): void
    {
    }

    public function onAttack(EntityDamageEvent $event, EnchantmentInstance $enchantmentInstance, Player $player): void
    {
    }

    public function onKill(EntityDeathEvent $event, EnchantmentInstance $enchantmentInstance, Player $player, Player $victim): void
    {
    }
}