<?php

namespace Kitmap\block;

use Kitmap\handler\Job;
use pocketmine\block\Block as PmBlock;
use pocketmine\block\VanillaBlocks;
use pocketmine\item\VanillaItems;
use pocketmine\player\Player;

class DeepslateEmeraldOre extends Block
{
    public function getDropsMine(Player $player, PmBlock $block): ?array
    {
        $emerald = VanillaItems::GOLD_NUGGET()->setCount(mt_rand(1, 5));
        Job::addXp($player, "Mineur", 15);

        return [
            15,
            VanillaBlocks::BEDROCK(),
            $block,
            [$emerald]
        ];
    }
}