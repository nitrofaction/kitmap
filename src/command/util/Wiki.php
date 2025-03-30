<?php /** @noinspection PhpUnused */

namespace Kitmap\command\util;

use CortexPE\Commando\BaseCommand;
use Kitmap\handler\Cache;
use Kitmap\Util;
use pocketmine\command\CommandSender;
use pocketmine\permission\DefaultPermissions;
use pocketmine\player\Player;
use pocketmine\plugin\PluginBase;

class Wiki extends BaseCommand
{
    public function __construct(PluginBase $plugin)
    {
        parent::__construct(
            $plugin,
            "wiki",
            "Permet de donner l'utilité de l'item dans votre main"
        );

        $this->setPermissions([DefaultPermissions::ROOT_USER]);
    }

    public function onRun(CommandSender $sender, string $aliasUsed, array $args): void
    {
        if ($sender instanceof Player) {
            $item = $sender->getInventory()->getItemInHand();

            foreach (Cache::$config["wiki"] as $itemName => $data) {
                $targetItem = Util::getItemByName($itemName);

                if ($targetItem->equals($item, false, false)) {
                    $sender->sendMessage("§f[§n" . ($data[0] ?? "") . "§f] " . Util::PREFIX . ($data[1] ?? ""));
                    return;
                }
            }

            $sender->sendMessage(Util::PREFIX . "L'item dans votre main n'est pas un item moddé !");
        }
    }

    protected function prepare(): void
    {
    }
}