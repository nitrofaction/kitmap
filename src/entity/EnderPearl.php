<?php

namespace Kitmap\entity;

use Kitmap\Session;
use Kitmap\Util;
use pocketmine\block\Block;
use pocketmine\block\FenceGate;
use pocketmine\block\PressurePlate;
use pocketmine\block\Tripwire;
use pocketmine\block\utils\DyeColor;
use pocketmine\block\VanillaBlocks;
use pocketmine\entity\projectile\EnderPearl as PmEnderPearl;
use pocketmine\event\entity\ProjectileHitEvent;
use pocketmine\math\AxisAlignedBB;
use pocketmine\math\Facing;
use pocketmine\math\RayTraceResult;
use pocketmine\math\Vector3;
use pocketmine\player\Player;

class EnderPearl extends PmEnderPearl
{
    protected function onHit(ProjectileHitEvent $event): void
    {
        $owner = $this->getOwningEntity();

        if ($owner instanceof Player) {
            if ($owner->getWorld() !== $this->getWorld()) {
                return;
            } else if (Util::insideZone($this->getPosition(), "spawn")) {
                $this->cancel($owner, "Votre perle a été annulé car elle a attéri au spawn");
                return;
            }

            parent::onHit($event);
        }
    }

    private function cancel(Player $player, string $reason): void
    {
        $player->sendMessage(Util::PREFIX . $reason . ", votre cooldown perle à été reset à §n2 §fsecondes");
        Session::get($player)->setCooldown("enderpearl", 2);

        $this->setOwningEntity(null);
        $this->flagForDespawn();
    }

    protected function calculateInterceptWithBlock(Block $block, Vector3 $start, Vector3 $end): ?RayTraceResult
    {
        $player = $this->getOwningEntity();

        if ($player instanceof Player) {
            if ($block instanceof FenceGate) {
                $this->cancel($player, "Votre perle a été annulé car elle a touché un portillon");
            } else if ($block->isSameState(VanillaBlocks::STAINED_GLASS()->setColor(DyeColor::BROWN())) || $block->hasSameTypeId(VanillaBlocks::REDSTONE())) {
                $this->cancel($player, "Votre perle a été annulé car elle a touché un bloc antiback");
            }
        }

        if ($block instanceof PressurePlate || $block instanceof Tripwire) {
            $position = $block->getPosition();
            $bb = new AxisAlignedBB($position->getX(), $position->getY(), $position->getZ(), $position->getX(), $position->getY(), $position->getZ());

            return new RayTraceResult($bb, Facing::UP, $block->getPosition());
        } else {
            return $block->calculateIntercept($start, $end);
        }
    }
}