<?php

namespace Kitmap\item;

use pocketmine\entity\effect\Effect;
use pocketmine\inventory\ArmorInventory;
use pocketmine\item\Item as PmItem;

class StrawArmor extends Armor
{
    public function __construct(float $maxDurability, int $defensePoints, private readonly Effect $effect, private readonly int $amplifier)
    {
        parent::__construct($maxDurability, $defensePoints);
    }

    public static function update(ArmorInventory $armorInventory): void
    {
        $strawArmor = true;

        foreach ($armorInventory->getContents(true) as $targetItem) {
            if (!ExtraVanillaItems::getItem($targetItem) instanceof StrawArmor) {
                $strawArmor = false;
            }
        }

        if ($strawArmor) {
            foreach ($armorInventory->getContents(true) as $targetItem) {
                ExtraVanillaItems::getItem($targetItem)->addEffects($armorInventory, $targetItem);
            }
        } else {
            foreach ($armorInventory->getContents(true) as $oldItem) {
                ExtraVanillaItems::getItem($oldItem)->removeEffects($armorInventory, $oldItem);
            }
        }
    }

    public function getEffects(PmItem $item): array
    {
        return [[$this->effect, $this->amplifier]];
    }

    public function isRare(): bool
    {
        return true;
    }
}