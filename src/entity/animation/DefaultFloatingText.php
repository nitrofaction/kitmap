<?php

namespace Kitmap\entity\animation;

use pocketmine\entity\Location;
use pocketmine\nbt\tag\CompoundTag;

class DefaultFloatingText extends FloatingText
{
    private string $content;
    private string $type;

    public function __construct(Location $location, ?CompoundTag $nbt = null)
    {
        if ($nbt instanceof CompoundTag) {
            $this->content = $nbt->getString("floating", "remove");
            $this->type = $nbt->getString("type", "remove");
        }

        parent::__construct($location, $nbt);
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function saveNBT(): CompoundTag
    {
        $nbt = parent::saveNBT();
        $nbt->setString("floating", $this->content);
        $nbt->setString("type", $this->type);
        return $nbt;
    }

    protected function getPeriod(): ?int
    {
        return $this->period;
    }

    protected function getUpdate(): string
    {
        if ($this->content === "remove") {
            $this->close();
        }

        $this->period = null;
        return $this->content;
    }
}