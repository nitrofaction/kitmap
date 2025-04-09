<?php /** @noinspection PhpUnused */

namespace Kitmap\command\player;

use CortexPE\Commando\args\RawStringArgument;
use CortexPE\Commando\args\TargetPlayerArgument;
use CortexPE\Commando\BaseCommand;
use Kitmap\handler\Cache;
use Kitmap\handler\Faction;
use Kitmap\Main;
use Kitmap\Session;
use Kitmap\Util;
use pocketmine\command\CommandSender;
use pocketmine\permission\DefaultPermissions;
use pocketmine\player\Player;
use pocketmine\plugin\PluginBase;

class Stats extends BaseCommand
{
    public function __construct(PluginBase $plugin)
    {
        parent::__construct(
            $plugin,
            "stats",
            "Récupere ses informations ou celle d'une autre personne"
        );

        $this->setAliases(["info"]);
        $this->setPermissions([DefaultPermissions::ROOT_USER]);
    }

    public function onRun(CommandSender $sender, string $aliasUsed, array $args): void
    {
        if ($sender instanceof Player) {
            $username = strtolower($args["joueur"] ?? $sender->getName());

            /** @noinspection PhpDeprecationInspection */
            $target = Main::getInstance()->getServer()->getPlayerByPrefix($username);

            if (!isset(Cache::$players["upper_name"][$username])) {
                $sender->sendMessage(Util::PREFIX . "Ce joueur ne s'est jamais connecté au serveur (verifiez bien les caractères)");
                return;
            }

            if ($target instanceof Player) {
                $session = Session::get($target);
                Faction::hasFaction($target);

                $session->data["played_time"] += time() - $session->data["connection"];
                $session->data["connection"] = time();

                $data = $session->data;
            } else {
                $file = Util::getFile("data/players/" . $username);
                $data = $file->getAll();
            }

            $bar = Util::stringToIcon("dark-bar");
            $playtime = Util::formatDurationFromSeconds($data["played_time"]);

            $faction = $data["faction"];
            $faction = (is_null($faction)) ? "Aucune Faction" : Faction::getFactionUpperName($faction);

            $sender->sendMessage($bar);
            $sender->sendMessage("§n[§f" . $faction . "§n] [§f" . ucfirst(strtolower($data["rank"])) . "§n] §f" . $data["upper_name"]);
            $sender->sendMessage("§nK: §f" . $data["kill"] . " §nD: §f" . $data["death"] . " §nElo: §f" . $data["elo"]);
            $sender->sendMessage("§nEco: §f" . $data["money"] . "§f$ §n| §f" . $data["gem"] . " gemmes");
            $sender->sendMessage("§nKS: §f" . $data["killstreak"] . " §nPrime: §f" . $data["bounty"]);
            $sender->sendMessage("§nTemps: §f" . $playtime);
            $sender->sendMessage($bar);
        }
    }

    protected function prepare(): void
    {
        $this->registerArgument(0, new TargetPlayerArgument(true, "joueur"));
        $this->registerArgument(0, new RawStringArgument("joueur", true));
    }
}