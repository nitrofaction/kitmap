<?php

namespace Kitmap\command\faction\subcommands;

use Kitmap\command\faction\FactionCommand;
use Kitmap\handler\Cache;
use Kitmap\handler\Faction;
use Kitmap\Main;
use Kitmap\Session;
use Kitmap\Util;
use pocketmine\permission\DefaultPermissions;
use pocketmine\player\Player;

class AllyAccept extends FactionCommand
{
    public function __construct()
    {
        parent::__construct(
            Main::getInstance(),
            "allyaccept",
            "Accepte la dernière demande d'alliance recu"
        );

        $this->setPermissions([DefaultPermissions::ROOT_USER]);
    }

    public function onNormalRun(Player $sender, Session $session, ?string $faction, array $args): void
    {
        if (!is_null(Faction::getAlly($faction))) {
            $sender->sendMessage(Util::PREFIX . "Vous possèdez déjà une alliance");
            return;
        }

        $ally = Cache::$pendingAlly[strtolower($faction)] ?? null;

        if (is_null($ally) || !Faction::exists($ally)) {
            $sender->sendMessage(Util::PREFIX . "Vous n'avez aucune demande d'alliance en attente");
            return;
        } else if (!is_null(Faction::getAlly($ally))) {
            $sender->sendMessage(Util::PREFIX . "La faction §n" . $ally . "§f possède déjà une alliance");
            return;
        }

        Faction::setAlly($faction, $ally);
        Faction::setAlly($ally, $faction);

        unset(Cache::$pendingAlly[$faction]);

        Faction::broadcastFactionMessage($faction, "Votre faction vient d'accepter la demande d'alliance de la part de la faction §n" . $ally);
        Faction::broadcastFactionMessage($ally, "La faction §n" . $faction . " §fa accepté votre demande d'alliance");
    }

    protected function prepare(): void
    {
    }
}