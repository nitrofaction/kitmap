<?php

namespace Kitmap\entity\animation;

use Kitmap\entity\FireworksRocket;
use Kitmap\handler\Pack as Api;
use Kitmap\item\Fireworks;
use Kitmap\Util;
use pocketmine\block\Block;
use pocketmine\entity\Human;
use pocketmine\entity\Location;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\item\ItemIdentifier;
use pocketmine\item\ItemTypeIds;
use pocketmine\math\Vector3;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\player\Player;

class Pack extends Human
{
    private Fireworks $item;

    private int $ticks = 0;

    public function attack(EntityDamageEvent $source): void
    {
        $source->cancel();

        if ($source instanceof EntityDamageByEntityEvent) {
            $player = $source->getDamager();
            $block = $this->getBlock();

            if (!$player instanceof Player) {
                return;
            }

            Util::removeCurrentWindow($player);
            Api::openPackUI($player, $block);
        }
    }

    private function getBlock(): Block
    {
        return $this->getPosition()->getWorld()->getBlock($this->getFloorVector());
    }

    public function getFloorVector(): Vector3
    {
        return new Vector3($this->getPosition()->getFloorX(), round($this->getPosition()->getY()), $this->getPosition()->getFloorZ());
    }

    public function getName(): string
    {
        return "Pack";
    }

    public function onUpdate(int $currentTick): bool
    {
        $this->ticks++;
        $this->location->yaw += 3;

        $this->move($this->motion->x, $this->motion->y, $this->motion->z);
        $this->updateMovement();

        $fireworkData = [
            5 => Fireworks::COLOR_BLUE,
            13 => Fireworks::COLOR_WHITE,
            21 => Fireworks::COLOR_GREEN
        ];

        if (isset($fireworkData[$this->ticks])) {
            $color = $fireworkData[$this->ticks];
            $this->spawnFirework($color);
        }

        if ($this->ticks >= 2500) {
            $this->ticks = 0;
        }

        return parent::onUpdate($currentTick);
    }

    private function spawnFirework(string $color): void
    {
        $this->item->addExplosion(mt_rand(0, 4), $color, "", true, true);

        $positions = [
            5 => [1.5, 0, 1.5],
            13 => [0, 0, 0],
            21 => [-1.5, 0, -1.5]
        ];

        $pos = $positions[$this->ticks] ?? [0, 0, 0];
        $entity = new FireworksRocket(Location::fromObject($this->location->add(...$pos), $this->location->world), $this->item);
        $entity->setLifeTime(mt_rand(17, 20));
        $entity->spawnToAll();
    }

    protected function initEntity(CompoundTag $nbt): void
    {
        parent::initEntity($nbt);

        $this->setNameTagAlwaysVisible(false);
        $this->setScale(4);
        $this->setNoClientPredictions();

        $this->item = new Fireworks(new ItemIdentifier(ItemTypeIds::newId()), "Fireworks");
    }
}