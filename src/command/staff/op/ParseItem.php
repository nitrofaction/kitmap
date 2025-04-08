<?php /** @noinspection PhpUnused */

namespace Kitmap\command\staff\op;

use CortexPE\Commando\args\RawStringArgument;
use CortexPE\Commando\BaseCommand;
use Kitmap\Util;
use pocketmine\command\CommandSender;
use pocketmine\permission\DefaultPermissions;
use pocketmine\player\Player;
use pocketmine\plugin\PluginBase;

class ParseItem extends BaseCommand
{
    public function __construct(PluginBase $plugin)
    {
        parent::__construct(
            $plugin,
            "parseitem",
            "Permet de se donner un item en le parsant"
        );

        $this->setPermissions([DefaultPermissions::ROOT_OPERATOR]);
    }

    public function onRun(CommandSender $sender, string $aliasUsed, array $args): void
    {
        if ($sender instanceof Player) {
            $item = Util::parseItem($args["item"]);

            $sender->getInventory()->addItem($item);
            $sender->sendMessage(Util::PREFIX . "Vous avez recu l'item dans votre inventaire");
        }
    }

    protected function prepare(): void
    {
        $this->registerArgument(0, new RawStringArgument("item"));
    }
}