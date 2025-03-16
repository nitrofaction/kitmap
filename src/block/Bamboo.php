<?php

namespace Kitmap\block;

use Kitmap\handler\Job;
use Kitmap\Util;
use pocketmine\block\Bamboo as PmBamboo;
use pocketmine\block\VanillaBlocks;
use pocketmine\event\block\BlockBreakEvent;
use pocketmine\item\VanillaItems;
use pocketmine\math\Vector3;
use pocketmine\world\particle\BlockBreakParticle;

class Bamboo extends Block
{
    public function onBreak(BlockBreakEvent $event): bool
    {
        $block = $event->getBlock();
        $player = $event->getPlayer();

        $pos = $block->getPosition();
        $y = intval($pos->getY());

        $amount = 0;

        while (($b = $pos->getWorld()->getBlock(new Vector3($pos->getX(), $y, $pos->getZ()))) instanceof PmBamboo) {
            $y++;
            $amount++;

            $pos->getWorld()->addParticle($b->getPosition()->add(0.5, 0.5, 0.5), new BlockBreakParticle($b));
            $pos->getWorld()->setBlock($b->getPosition(), VanillaBlocks::AIR());
        }

        if (!$player->isCreative()) {
            Job::addXp($player, "Farmeur", $amount);
            Util::addItem($player, VanillaItems::BAMBOO()->setCount($amount));
        }

        $event->cancel();
        return true;
    }
}