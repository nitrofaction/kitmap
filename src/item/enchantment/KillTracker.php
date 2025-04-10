<?php /* @noinspection PhpDeprecationInspection */

namespace Kitmap\item\enchantment;

use Kitmap\Util;
use pocketmine\event\entity\EntityDeathEvent;
use pocketmine\item\enchantment\EnchantmentInstance;
use pocketmine\player\Player;

class KillTracker extends Enchantment
{
    public function onKill(EntityDeathEvent $event, EnchantmentInstance $enchantmentInstance, Player $player, Player $victim): void
    {
        $oldItem = $player->getInventory()->getItemInHand();
        $item = clone $oldItem;

        $kills = $item->getNamedTag()->getInt("kills", 0) + 1;
        $names = $item->getNamedTag()->getString("names", "") . "," . $victim->getName();

        $lore = Util::stringToIcon("bar") . "\n§r";
        $i = 0;

        foreach (explode(",", $names) as $name) {
            if (strlen($name) > 1) {
                $i++;
                $lore .= "§r§n#" . $i . " " . Util::PREFIX . " " . $name . "\n";
            }
        }

        $lore .= "\n" . Util::stringToIcon("bar");

        $item->getNamedTag()->setInt("kills", $kills);
        $item->getNamedTag()->setString("names", $names);
        $item->setCustomName("§r§bÉpée de " . $player->getName() . " §8(§7" . $kills . " kills§8)");
        $item->setLore([$lore]);

        $player->getInventory()->setItemInHand($item);
    }
}
