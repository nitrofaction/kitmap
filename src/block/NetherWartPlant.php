<?php

namespace Kitmap\block;

use pocketmine\block\Block as PmBlock;
use pocketmine\block\NetherWartPlant as PmNetherWartPlant;
use pocketmine\block\VanillaBlocks;
use pocketmine\item\Item;
use pocketmine\item\VanillaItems;
use pocketmine\player\Player;

class NetherWartPlant extends Block
{
    public function getDrops(PmBlock $block, ?Item $item = null, Player $player = null): ?array
    {
        if (
            $block instanceof PmNetherWartPlant &&
            $block->getAge() === $block::MAX_AGE
        ) {
            if (mt_rand(0, 30) === 0) {
                return [VanillaItems::RABBIT_FOOT()];
            } else {
                return [];
            }
        } else {
            return [VanillaBlocks::NETHER_WART()->asItem()];
        }
    }

    public function getXpDropAmount(PmBlock $block): ?int
    {
        if (
            $block instanceof PmNetherWartPlant &&
            $block->getAge() === $block::MAX_AGE
        ) {
            return mt_rand(1, 3);
        } else {
            return 0;
        }
    }
}