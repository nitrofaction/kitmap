<?php

namespace Kitmap\block;

use pocketmine\block\Block as PmBlock;
use pocketmine\block\VanillaBlocks;
use pocketmine\item\Item;
use pocketmine\item\VanillaItems;
use pocketmine\player\Player;

class CocoaPod extends Block
{
    public function getDrops(PmBlock $block, ?Item $item = null, Player $player = null): ?array
    {
        $items = [
            VanillaItems::COOKED_FISH(),
            VanillaItems::COOKED_SALMON(),
            VanillaItems::RAW_SALMON()
        ];

        return [$items[array_rand($items)]];
    }

    public function breakableOnMine(): array
    {
        return [
            true,
            15,
            VanillaBlocks::AIR()
        ];
    }
}