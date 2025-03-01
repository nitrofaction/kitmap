<?php

namespace Kitmap\item;

use pocketmine\block\Crops;
use pocketmine\block\NetherWartPlant;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\world\particle\HappyVillagerParticle;

class WateringCan extends Durable
{
    public function onInteract(PlayerInteractEvent $event): bool
    {
        if ($event->getAction() !== $event::RIGHT_CLICK_BLOCK) {
            return false;
        }

        $player = $event->getPlayer();
        $block = $event->getBlock();

        $event->cancel();

        if ($this->inCooldown($player)) {
            return false;
        }

        $this->setCooldown($player, 0.1);

        if ($block instanceof Crops || $block instanceof NetherWartPlant) {
            if (!$block->ticksRandomly()) {
                return false;
            }

            for ($i = 0; $i < mt_rand(1, 5); $i++) {
                if ($block->ticksRandomly()) {
                    $block->onRandomTick();
                }
            }

            $this->applyDamage($player);
            $block->getPosition()->getWorld()->addParticle($block->getPosition()->add(0.5, 1, 0.5), new HappyVillagerParticle());
        }

        return false;
    }

    public function getMaxDurability(): int
    {
        return 300;
    }
}