<?php

namespace Kitmap\command\faction\subcommands;

use CortexPE\Commando\args\OptionArgument;
use Kitmap\command\faction\FactionCommand;
use Kitmap\handler\Cache;
use Kitmap\handler\Faction;
use Kitmap\Main;
use Kitmap\Session;
use Kitmap\task\TeleportationTask;
use Kitmap\Util;
use pocketmine\permission\DefaultPermissions;
use pocketmine\player\Player;
use pocketmine\world\Position;

class Home extends FactionCommand
{
    public function __construct()
    {
        parent::__construct(
            Main::getInstance(),
            "home",
            "Se téléporter à l'home d'une faction"
        );

        $this->setPermissions([DefaultPermissions::ROOT_USER]);
        $this->setAliases(["h"]);
    }

    public function onNormalRun(Player $sender, Session $session, ?string $faction, array $args): void
    {
        $ally = !is_null($args["opt"] ?? null);

        if ($session->inCooldown("combat")) {
            $sender->sendMessage(Util::PREFIX . "Cette commande est interdite en combat");
            return;
        } else if ($session->inCooldown("teleportation")) {
            $sender->sendMessage(Util::PREFIX . "Vous ne pouvez pas executer cette commande en teleportation");
            return;
        }

        if ($ally && !Faction::hasAlly($faction)) {
            $sender->sendMessage(Util::PREFIX . "Votre faction n'a pas d'alliance");
            return;
        } else if ($ally && !Faction::hasPermission($sender, "home", true)) {
            $sender->sendMessage(Util::PREFIX . "Votre alliance ne vous autorise pas à accèder à son home de faction");
            return;
        } else if ($ally) {
            $faction = Faction::getAlly($faction);
        }

        $home = Cache::$factions[$faction]["home"];

        if (is_null($home) || $home === "0:0:0") {
            if ($ally) {
                $sender->sendMessage(Util::PREFIX . "Votre alliance n'a pas encore défini de f home");
            } else{
                $sender->sendMessage(Util::PREFIX . "Votre faction n'a pas encore défini de f home");
            }
            return;
        }

        [$x, $y, $z] = explode(":", Cache::$factions[$faction]["home"]);

        if (Faction::inClaim(intval($x), intval($z))[1] !== $faction) {
            if ($ally) {
                $sender->sendMessage(Util::PREFIX . "Le f home de votre alliance n'est pas dans son claim");
            } else {
                $sender->sendMessage(Util::PREFIX . "Votre home n'est pas dans votre claim");
            }

            Cache::$factions[$faction]["home"] = "0:0:0";
            return;
        }

        $position = new Position(intval($x), intval($y), intval($z), Main::getInstance()->getServer()->getWorldManager()->getDefaultWorld());
        Main::getInstance()->getScheduler()->scheduleRepeatingTask(new TeleportationTask($sender, $position), 20);
    }

    protected function prepare(): void
    {
        $this->registerArgument(0, new OptionArgument("opt", ["ally"], true));
    }
}