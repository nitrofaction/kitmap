<?php /** @noinspection PhpSameParameterValueInspection */

namespace Kitmap\item;

use pocketmine\block\Air;
use pocketmine\block\Block;
use pocketmine\block\utils\DyeColor;
use pocketmine\block\VanillaBlocks;
use pocketmine\event\player\PlayerItemUseEvent;
use pocketmine\math\Vector3;
use pocketmine\world\World;

class IronAxe extends Item
{
    public function onUse(PlayerItemUseEvent $event): bool
    {
        $player = $event->getPlayer();

        if (!is_null($player->getInventory()->getItemInHand()->getNamedTag()->getTag("road"))) {
            $block = $player->getTargetBlock(16);

            if ($block instanceof Block) {
                $this->place($block->getPosition(), 4, $block->getPosition()->getWorld());
            }

            $event->cancel();
            return true;
        }

        return parent::onUse($event);
    }

    private function place(Vector3 $center, int $radius, World $world): void
    {
        for ($x = -$radius; $x <= $radius; $x++) {
            for ($z = -$radius; $z <= $radius; $z++) {
                if ($x * $x + $z * $z <= $radius * $radius) {
                    $pos = $center->add($x, 0, $z);

                    if (!$world->getBlock($pos) instanceof Air) {
                        $world->setBlock($pos, VanillaBlocks::STAINED_HARDENED_GLASS()->setColor(DyeColor::BROWN()));
                        $world->setBlock($pos->add(0, -1, 0), VanillaBlocks::DEEPSLATE_EMERALD_ORE());
                    }
                }
            }
        }
    }
}