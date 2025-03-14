<?php

namespace Kitmap\block;

use Kitmap\handler\trait\CooldownTrait;
use pocketmine\block\Block as PmBlock;
use pocketmine\block\VanillaBlocks;
use pocketmine\event\block\BlockBreakEvent;
use pocketmine\event\block\BlockPlaceEvent;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\event\player\PlayerJumpEvent;
use pocketmine\event\player\PlayerToggleSneakEvent;
use pocketmine\item\Item;
use pocketmine\player\Player;

// use pocketmine\event\block\BlockSupportBreakEvent;

class Block
{
    use CooldownTrait;

    // False = return in the event
    // True = return in the event
    // Cancel in the function not automatic

    public function onInteract(PlayerInteractEvent $event): bool
    {
        return false;
    }

    public function onJump(PlayerJumpEvent $event): bool
    {
        return false;
    }

    public function onSneak(PlayerToggleSneakEvent $event): bool
    {
        return false;
    }

    public function onPlace(BlockPlaceEvent $event): bool
    {
        return false;
    }

    /*public function onSupportBreak(BlockSupportBreakEvent $event): bool
    {
        $drops = $this->getDrops($event->getBlock());
        $xp = $this->getXpDropAmount($event->getBlock());

        if (!is_null($drops)) $event->setDrops($drops);
        if (!is_null($xp)) $event->setXpDropAmount($xp);

        return false;
    }*/

    public function onBreak(BlockBreakEvent $event): bool
    {
        $player = $event->getPlayer();

        $drops = $this->getDrops($event->getBlock(), $event->getItem(), $player);
        $xp = $this->getXpDropAmount($event->getBlock());

        if (!is_null($drops) && !$player->isCreative()) $event->setDrops($drops);
        if (!is_null($xp) && !$player->isCreative()) $event->setXpDropAmount($xp);

        return false;
    }

    /** @noinspection PhpUnusedParameterInspection */
    public function getDrops(PmBlock $block, ?Item $item = null, Player $player = null): ?array
    {
        return null;
    }

    public function getXpDropAmount(PmBlock $block): ?int
    {
        return null;
    }

    public function breakableOnMine(): array
    {
        return [false, 0, VanillaBlocks::AIR()];
    }
}