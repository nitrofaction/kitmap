<?php /* @noinspection PhpDeprecationInspection */

namespace Kitmap\item\enchantment;

use pocketmine\event\block\BlockBreakEvent;
use pocketmine\item\enchantment\EnchantmentInstance;
use pocketmine\item\VanillaItems;

class DoublePickaxe extends Enchantment
{
    public function onBreak(BlockBreakEvent $event, EnchantmentInstance $enchantmentInstance): void
    {
        $block = $event->getBlock();
        $player = $event->getPlayer();

        $item = VanillaItems::DIAMOND_PICKAXE();
        $block->getPosition()->getWorld()->useBreakOn($block->getPosition()->add(0, -1, 0), $item, $player, true);
    }
}