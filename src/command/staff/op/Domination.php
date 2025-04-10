<?php /** @noinspection PhpUnused */

namespace Kitmap\command\staff\op;

use CortexPE\Commando\args\OptionArgument;
use CortexPE\Commando\BaseCommand;
use Kitmap\Main;
use Kitmap\task\repeat\child\DominationTask;
use Kitmap\Util;
use pocketmine\command\CommandSender;
use pocketmine\permission\DefaultPermissions;
use pocketmine\plugin\PluginBase;

class Domination extends BaseCommand
{
    public function __construct(PluginBase $plugin)
    {
        parent::__construct(
            $plugin,
            "domination",
            "Commence ou arrête un event domination ! §c(O)"
        );

        $this->setPermissions([DefaultPermissions::ROOT_OPERATOR]);
    }

    public function onRun(CommandSender $sender, string $aliasUsed, array $args): void
    {
        switch ($args["opt"]) {
            case "start":
                if (10 > count(Main::getInstance()->getServer()->getOnlinePlayers())) {
                    $sender->sendMessage(Util::PREFIX . "L'event domination demande au minimum §n10 §fjoueurs avant d'être lancé");
                    return;
                } else if (DominationTask::$currentDomination) {
                    $sender->sendMessage(Util::PREFIX . "Un event §ndomination §fest déjà en cours... Vous pouvez l'arrêter avec la commande §n/domination end");
                    return;
                }

                DominationTask::$currentDomination = true;
                Main::getInstance()->getServer()->broadcastMessage(Util::PREFIX . "Un event §ndomination §fvient de commencer ! Vous pouvez vous y téléporter grace à la commande §n/event domination");
                break;
            case "end":
                DominationTask::$currentDomination = false;
                DominationTask::$factions = [];
                DominationTask::$zones = [];
                DominationTask::$time = 900;

                Main::getInstance()->getServer()->broadcastMessage(Util::PREFIX . "L'event §nDOMINATION §fa été arrêté par un administrateur du serveur");
                break;
        }
    }

    protected function prepare(): void
    {
        $this->registerArgument(0, new OptionArgument("opt", ["start", "end"]));
    }
}