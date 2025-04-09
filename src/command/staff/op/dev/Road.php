<?php /** @noinspection PhpUnused */

namespace Kitmap\command\staff\op\dev;

use CortexPE\Commando\BaseCommand;
use pocketmine\command\CommandSender;
use pocketmine\data\bedrock\EnchantmentIdMap;
use pocketmine\item\enchantment\EnchantmentInstance;
use pocketmine\item\VanillaItems;
use pocketmine\permission\DefaultPermissions;
use pocketmine\player\Player;
use pocketmine\plugin\PluginBase;

class Road extends BaseCommand
{
    public function __construct(PluginBase $plugin)
    {
        parent::__construct(
            $plugin,
            "road",
            "À utiliser seulement si on connait l'usage /!\ §4(D)"
        );

        $this->setPermissions([DefaultPermissions::ROOT_OPERATOR]);
    }

    public function onRun(CommandSender $sender, string $aliasUsed, array $args): void
    {
        if ($sender instanceof Player) {
            $item = VanillaItems::IRON_AXE();
            $item->setCustomName("§r§nRoad Axe");
            $item->getNamedTag()->setInt("road", 1);
            $item->addEnchantment(new EnchantmentInstance(EnchantmentIdMap::getInstance()->fromId(-1), 255));
            $sender->getInventory()->setItemInHand($item);
        }
    }

    protected function prepare(): void
    {
    }
}