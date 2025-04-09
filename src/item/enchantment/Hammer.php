<?php /* @noinspection PhpDeprecationInspection */

namespace Kitmap\item\enchantment;

use pocketmine\event\block\BlockBreakEvent;
use pocketmine\item\enchantment\EnchantmentInstance;
use pocketmine\item\VanillaItems;
use pocketmine\math\Facing;
use pocketmine\math\Vector3;

class Hammer extends Enchantment
{
    public static array $faces = [];

    public function onBreak(BlockBreakEvent $event, EnchantmentInstance $enchantmentInstance): void
    {
        $block = $event->getBlock();
        $player = $event->getPlayer();

        $face = Hammer::$faces[$player->getXuid()] ?? Facing::UP;

        $position = $block->getPosition();
        $world = $position->getWorld();

        $item = VanillaItems::DIAMOND_PICKAXE();

        for ($a = -1; $a <= 1; $a++) {
            for ($b = -1; $b <= 1; $b++) {
                if ($a === 0 && $b == 0) {
                    continue;
                }

                if ($face == Facing::UP || $face == Facing::DOWN) $target = new Vector3($a, 0, $b);
                if ($face == Facing::NORTH || $face == Facing::SOUTH) $target = new Vector3($a, $b, 0);
                if ($face == Facing::EAST || $face == Facing::WEST) $target = new Vector3(0, $a, $b);

                $world->useBreakOn(
                    $position->addVector($target ?? new Vector3(0, 0, 0)),
                    $item, $player, true
                );
            }
        }
    }
}