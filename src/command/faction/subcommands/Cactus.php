<?php

namespace Kitmap\command\faction\subcommands;

use jojoe77777\FormAPI\CustomForm;
use Kitmap\command\faction\FactionCommand;
use Kitmap\handler\Cache;
use Kitmap\handler\Faction;
use Kitmap\Main;
use Kitmap\Session;
use Kitmap\Util;
use pocketmine\block\VanillaBlocks;
use pocketmine\permission\DefaultPermissions;
use pocketmine\player\Player;

class Cactus extends FactionCommand
{
    public function __construct()
    {
        parent::__construct(
            Main::getInstance(),
            "cactus",
            "Récupère l'entierté des cactus qui ont poussé"
        );

        $this->setPermissions([DefaultPermissions::ROOT_USER]);
    }

    public function onNormalRun(Player $sender, Session $session, ?string $faction, array $args): void
    {
        $cactus = Faction::getCactus($faction);

        if ($cactus <= 0) {
            $sender->sendMessage(Util::PREFIX . "Votre faction n'a aucun cactus à récupérer !");
            return;
        }

        $this->sendCactusForm($sender, $faction, $cactus);
    }

    private function sendCactusForm(Player $player, ?string $faction, int $maxCactus): void
    {
        $form = new CustomForm(function (Player $player, $data) use ($faction) {
            if (!is_array($data) || !Faction::exists($faction)) {
                return;
            }

            $maxCactus = Faction::getCactus($faction);
            $amount = intval($data[0]);

            if ($amount > $maxCactus || 1 > $amount || $amount > 255) {
                $player->sendMessage(Util::PREFIX . "Vous avez essayé de récupérer plus de cactus que disponible");
                return;
            }

            Faction::removeCactus($faction, $amount);
            Util::addItem($player, VanillaBlocks::CACTUS()->asItem()->setCount($amount));

            Cache::$factions[$faction]["logs"][time()] = "§q" . $player->getName() . " §fa récupérer §q" . $amount . " §fcactus";
            Faction::broadcastMessage($faction, "§q[§fF§q] §fLe joueur §q" . $player->getName() . " §fvient de récupérer §q" . $amount . " §fcactus à la faction");
        });
        $form->setTitle("Cactus");
        $form->addSlider(Util::PREFIX . "Votre faction possède actuellement §q" . $maxCactus . " §fcactus ! Vous pouvez récupérer maximum §q255 §fcactus par demande, les cactus seront mis dans votre inventaire\n\nChoisissez la quantité de cactus que vous voulez récupérer", 1, min(255, $maxCactus));
        $player->sendForm($form);
    }

    protected function prepare(): void
    {
    }
}