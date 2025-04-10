<?php /** @noinspection PhpUnused */

namespace Kitmap\command\staff\op;

use CortexPE\Commando\args\OptionArgument;
use CortexPE\Commando\BaseCommand;
use Kitmap\Main;
use Kitmap\task\repeat\child\FarmWarsTask;
use Kitmap\Util;
use pocketmine\command\CommandSender;
use pocketmine\permission\DefaultPermissions;
use pocketmine\plugin\PluginBase;

class FarmWars extends BaseCommand
{
    public function __construct(PluginBase $plugin)
    {
        parent::__construct(
            $plugin,
            "farmwars",
            "Commence ou arrête un event farm wars §c(O)"
        );

        $this->setPermissions([DefaultPermissions::ROOT_OPERATOR]);
    }

    public function onRun(CommandSender $sender, string $aliasUsed, array $args): void
    {
        switch ($args["opt"]) {
            case "start":
                if (is_numeric(FarmWarsTask::$current)) {
                    $sender->sendMessage(Util::PREFIX . "Un event §nFARM WARS §fest déjà en cours... Vous pouvez l'arrêter avec la commande §n/farmwars end");
                    return;
                }

                FarmWarsTask::$current = 15 * 60;
                FarmWarsTask::$players = [];

                Main::getInstance()->getServer()->broadcastMessage(Util::PREFIX . "Un event §nFARM WARS §fvient de commencer ! Cassez le §nMAXIMUM §fd'agriculture pour augmenter en §nscore §fafin de gagner des packs !");
                break;
            case "end":
                FarmWarsTask::$current = null;
                FarmWarsTask::$players = [];

                Main::getInstance()->getServer()->broadcastMessage(Util::PREFIX . "L'event §nFARM WARS §fa été arrêté par un administrateur du serveur");
                break;
        }
    }

    protected function prepare(): void
    {
        $this->registerArgument(0, new OptionArgument("opt", ["start", "end"]));
    }
}