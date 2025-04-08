<?php

namespace Kitmap\block;

use pocketmine\event\player\PlayerInteractEvent;

class Cauldron extends Block
{
    public function onInteract(PlayerInteractEvent $event): bool
    {
        $event->cancel();
        return true;
    }
}