<?php

namespace Kitmap\block;

use pocketmine\block\Block as PmBlock;
use pocketmine\block\VanillaBlocks;
use pocketmine\item\Item;
use pocketmine\item\VanillaItems;
use pocketmine\player\Player;

class EmeraldOre extends Block
{
    public function getDrops(PmBlock $block, ?Item $item = null, Player $player = null): ?array
    {
        return [
            VanillaItems::GOLD_NUGGET()->setCount(mt_rand(1, 3))
        ];
    }

    public function getXpDropAmount(PmBlock $block): ?int
    {
        return mt_rand(1, 2);
    }

    public function breakableOnMine(): array
    {
        return [
            true,
            15,
            VanillaBlocks::BEDROCK()
        ];
    }
}