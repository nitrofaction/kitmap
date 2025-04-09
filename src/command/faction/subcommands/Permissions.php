<?php

namespace Kitmap\command\faction\subcommands;

use CortexPE\Commando\args\OptionArgument;
use jojoe77777\FormAPI\CustomForm;
use Kitmap\command\faction\FactionCommand;
use Kitmap\handler\Cache;
use Kitmap\handler\Faction;
use Kitmap\Main;
use Kitmap\Session;
use Kitmap\Util;
use pocketmine\permission\DefaultPermissions;
use pocketmine\player\Player;

class Permissions extends FactionCommand
{
    public function __construct()
    {
        parent::__construct(
            Main::getInstance(),
            "permissions",
            "Classement des meilleurs factions"
        );

        $this->setPermissions([DefaultPermissions::ROOT_USER]);
        $this->setAliases(["perms", "perm", "permission"]);
    }

    public function onNormalRun(Player $sender, Session $session, ?string $faction, array $args): void
    {
        $ally = is_null($args["opt"] ?? null) ? "" : "ally-";
        $title = is_null($args["opt"] ?? null) ? "" : " d'Alliances";

        $permissions = Cache::$factions[$faction][$ally . "permissions"];
        $names = Cache::$config["faction"][$ally . "permission"];

        $form = new CustomForm(function (Player $player, mixed $data) use ($ally, $faction) {
            if (!is_array($data)) {
                return;
            } else if (!Faction::exists($faction)) {
                $player->sendMessage(Util::PREFIX . "Vous n'êtes dans aucune faction ou la faction a été renommé");
                return;
            }

            foreach ($data as $key => $value) {
                if (isset(Cache::$factions[$faction][$ally . "permissions"][$key])) {
                    $rank = array_keys(Cache::$config["faction"][$ally . "rank"])[$value] ?? "recruit";
                    Cache::$factions[$faction][$ally . "permissions"][$key] = $rank;
                }
            }

            $player->sendMessage(Util::PREFIX . "Vous venez de mettre à jour les permissions de votre faction");
        });

        $form->setTitle("§nPermissions" . $title);

        $bar = Util::stringToIcon("dark-bar");
        $label = $bar . "\n" . Util::caracterToUnicode("down-right-arrow") . " §r§f";

        if ($ally) {
            $label .= "Choissisez le rôle minimum du membre de votre alliance pour faire une des actions ci-dessous";
        } else {
            $label .= "Choissisez le rôle minimum du membre de votre faction pour faire une des actions ci-dessous";
        }

        $label .= "\n" . $bar;

        $form->addLabel($label);

        foreach ($names as $permission => $description) {
            $actual = $permissions[$permission] ?? "Probleme merci de contacter le staff";
            $form->addDropdown(Util::PREFIX . $description, array_values(Cache::$config["faction"][$ally . "rank"]), Faction::getRankPosition($actual, true), $permission);
        }

        $sender->sendForm($form);
    }

    protected function prepare(): void
    {
        $this->registerArgument(0, new OptionArgument("opt", ["ally"], true));
    }
}