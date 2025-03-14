<?php

namespace Kitmap\block;

use pocketmine\event\block\BlockBreakEvent;
use pocketmine\item\Axe;

class MelonStem extends Block
{
    public function onBreak(BlockBreakEvent $event): bool
    {
        $item = $event->getItem();

        if ($item instanceof Axe) {
            $event->cancel();
            return true;
        }

        return false;
    }
}