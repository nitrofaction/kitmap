<?php /** @noinspection PhpUnused */

namespace Kitmap\command\staff\op;

use CortexPE\Commando\args\IntegerArgument;
use CortexPE\Commando\args\OptionArgument;
use CortexPE\Commando\args\RawStringArgument;
use CortexPE\Commando\args\TargetPlayerArgument;
use CortexPE\Commando\BaseCommand;
use Kitmap\handler\Cache;
use Kitmap\Main;
use Kitmap\Util;
use pocketmine\command\CommandSender;
use pocketmine\permission\DefaultPermissions;
use pocketmine\plugin\PluginBase;

class Buy extends BaseCommand
{
    public function __construct(PluginBase $plugin)
    {
        parent::__construct(
            $plugin,
            "buy",
            "Commande permettant de gérer les achats de la boutique"
        );

        $this->setPermissions([DefaultPermissions::ROOT_OPERATOR]);
    }

    public function onRun(CommandSender $sender, string $aliasUsed, array $args): void
    {
        $player = $args["joueur"];

        $gem = $args["gemme"] ?? null;
        $rank = $args["grade"] ?? null;

        $message = "§f§l=-= §r§qACHAT SUR LA BOUTIQUE §f§l=-=\n";
        $message .= "§r§fLe joueur §q" . $player . " §fvient\n";

        if (!is_null($gem)) {
            $gem = intval($gem);

            Util::executeCommand("addvalue \"" . $player . "\" " . $gem . " gem");

            $message .= "§r§fd'acheter §q" . $gem . " GEMMES §f!\n";
        }

        if (!is_null($rank)) {
            Util::executeCommand("setrank \"" . $player . "\" " . $rank);
            $message .= "§r§fd'acheter le grade §q" . ucfirst($rank) . " §f!\n";
        }

        $message .= "§f§q \n";
        $message .= "§r§fUn grand merci pour ton §qsoutien §f!\n";
        $message .= "§f§r \n";
        $message .= "§r§qhttps://store.nitrofaction.fr\n";
        $message .= "§f§l=§q-§f=§q-§f=§q-§f=§q-§f=§q-§f=§q-§f=§q-§f=§q-§f=§q-§f=§q-§f=§q-§f=";

        Main::getInstance()->getServer()->broadcastMessage($message);
    }

    protected function prepare(): void
    {
        $this->registerArgument(0, new TargetPlayerArgument(false, "joueur"));
        $this->registerArgument(0, new RawStringArgument("joueur"));

        $this->registerArgument(1, new OptionArgument("grade", array_keys(Cache::$config["rank"])));
        $this->registerArgument(1, new IntegerArgument("gemme"));
    }
}