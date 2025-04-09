<?php

namespace Kitmap\item;

use Kitmap\handler\Cache;
use Kitmap\Util;
use pocketmine\event\block\BlockBreakEvent;
use pocketmine\event\inventory\ItemDamageEvent;
use pocketmine\item\enchantment\EnchantmentInstance;
use pocketmine\item\enchantment\VanillaEnchantments;

class IlvaiteTool extends Durable
{
    public function onDamage(ItemDamageEvent $event): bool
    {
        $event->setDamage(0);
        return false;
    }

    public function onBreak(BlockBreakEvent $event): bool
    {
        $player = $event->getPlayer();

        $oldItem = $event->getItem();
        $item = clone $oldItem;

        $total = $item->getNamedTag()->getInt("total", 0) + 1;
        $blocks = $item->getNamedTag()->getInt("blocks", 0) + 1;

        $level = $item->getNamedTag()->getInt("level", 0);

        $item->getNamedTag()->setInt("blocks", $blocks);
        $item->getNamedTag()->setInt("total", $total);

        $nextLevel = $level + 1;
        $need = Cache::$config["ilvaite"][strval($nextLevel)];

        if ($blocks >= $need) {
            $item->addEnchantment(new EnchantmentInstance(VanillaEnchantments::EFFICIENCY(), $nextLevel));

            $item->getNamedTag()->setInt("blocks", 0);
            $item->getNamedTag()->setInt("level", $nextLevel);

            $blocks = 0;

            $level++;
            $nextLevel++;

            $need = Cache::$config["ilvaite"][strval($nextLevel)];
        }

        $progress = intval(max(1, round((($blocks / $need) * 40) / 2, 2)));
        $progress = "§n" . str_repeat("|", $progress) . "§8" . str_repeat("|", (40 / 2) - $progress);

        $lore = Util::stringToIcon("bar") . "\n§r";
        $lore .= Util::caracterToUnicode("|") . " §r§fNiveau: §n" . $level . "\n";
        $lore .= Util::caracterToUnicode("|") . " §r§fBlocs cassés: §n" . $total . "\n";
        $lore .= Util::caracterToUnicode("|") . " §r§fProgression: " . $progress . "\n";
        $lore .= Util::stringToIcon("bar");

        $item->setLore([$lore]);
        $player->getInventory()->setItemInHand($item);
        return false;
    }


    public function getMaxDurability(): int
    {
        return 9999;
    }
}