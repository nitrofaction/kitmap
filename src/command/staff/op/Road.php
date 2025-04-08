<?php /** @noinspection PhpUnused */

namespace Kitmap\command\staff\op;

use CortexPE\Commando\BaseCommand;
use pocketmine\command\CommandSender;
use pocketmine\item\enchantment\EnchantmentInstance;
use pocketmine\item\enchantment\VanillaEnchantments;
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
            "Permet de créer une road"
        );

        $this->setPermissions([DefaultPermissions::ROOT_OPERATOR]);
    }

    public function onRun(CommandSender $sender, string $aliasUsed, array $args): void
    {
        if ($sender instanceof Player) {
            $item = VanillaItems::IRON_AXE();

            $item->setCustomName("§r§nRoad Axe");

            $item->getNamedTag()->setInt("road", 1);
            $item->addEnchantment(new EnchantmentInstance(VanillaEnchantments::UNBREAKING(), 10));

            $sender->getInventory()->setItemInHand($item);
        }
    }

    protected function prepare(): void
    {
    }
}