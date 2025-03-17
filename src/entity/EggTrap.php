<?php

namespace Kitmap\entity;

use pocketmine\entity\projectile\Egg;
use pocketmine\event\entity\ProjectileHitEvent;

class EggTrap extends Egg
{
    protected function onHit(ProjectileHitEvent $event): void
    {
    }
}