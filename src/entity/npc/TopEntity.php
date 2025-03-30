<?php

namespace Kitmap\entity\npc;

use Kitmap\command\player\Top;
use Kitmap\handler\Cache;
use Kitmap\handler\Cosmetic;
use pocketmine\entity\Human;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\nbt\tag\CompoundTag;

class TopEntity extends Human
{
    private int $tickToUpdate;

    public function attack(EntityDamageEvent $source): void
    {
        $source->cancel();
    }

    protected function entityBaseTick(int $tickDiff = 1): bool
    {
        if ($this->isClosed()) {
            return false;
        }

        if ($this->isAlive()) {
            --$this->tickToUpdate;

            if ($this->tickToUpdate <= 0) {
                $this->setNameTag($this->getUpdate());
            }
        }
        return parent::entityBaseTick($tickDiff);
    }

    private function getUpdate(): string
    {
        $top = Cache::$config["pos"]["top"];

        $position = $this->getLocation();
        $text = $position->getX() . ":" . $position->getY() . ":" . $position->getZ() . ":" . intval($position->getYaw());

        $category = $top[$text] ?? null;

        if (is_null($category)) {
            return "";
        }

        $list = Top::getTopByCategory($category);

        $name = array_keys($list)[0];
        $skin = Cosmetic::getSkinFromName("", strtolower($name));

        $this->setSkin($skin);
        $this->sendSkin();

        $this->tickToUpdate = 600;

        return match ($category) {
            "death" => "§n" . $name . "\nTop #1 Mort",
            "elo" => "§n" . $name . "\nTop #1 Elo",
            "money" => "§n" . $name . "\nTop #1 Money",
            "played_time" => "§n" . $name . "\nTop #1 Nerd",
            default => "§n" . $name . "\nTop #1 Kill",
        };
    }

    protected function initEntity(CompoundTag $nbt): void
    {
        parent::initEntity($nbt);

        $this->tickToUpdate = 600;
        $this->setScale(0.80);

        $this->setNameTag($this->getUpdate());
        $this->setNameTagAlwaysVisible();
    }
}