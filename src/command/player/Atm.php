<?php /** @noinspection PhpUnused */

namespace Kitmap\command\player;

use CortexPE\Commando\BaseCommand;
use jojoe77777\FormAPI\SimpleForm;
use Kitmap\handler\Cache;
use Kitmap\Main;
use Kitmap\Session;
use Kitmap\Util;
use pocketmine\command\CommandSender;
use pocketmine\permission\DefaultPermissions;
use pocketmine\player\Player;
use pocketmine\plugin\PluginBase;

class Atm extends BaseCommand
{
    public function __construct(PluginBase $plugin)
    {
        parent::__construct(
            $plugin,
            "atm",
            "Permet de convertir son temps de jeu en money"
        );

        $this->setPermissions([DefaultPermissions::ROOT_USER]);
    }

    public function onRun(CommandSender $sender, string $aliasUsed, array $args): void
    {
        if ($sender instanceof Player) {
            $session = Session::get($sender);

            $session->data["played_time"] += time() - $session->data["connection"];
            $session->data["connection"] = time();

            $remaining = $session->data["played_time"] - $session->data["atm"];
            $money = round($remaining / 60) * Cache::$config["atm"];

            $form = new SimpleForm(function (Player $player, mixed $data) use ($session) {
                if ($data !== 0) {
                    return;
                }

                $remaining = $session->data["played_time"] - $session->data["atm"];
                $money = round($remaining / 60) * Cache::$config["atm"];

                if (1 > $money) {
                    $player->sendMessage(Util::PREFIX . "Vous ne pouvez actuellement pas convertir votre temps de jeu en argent, veuillez jouer encore un peu plus");
                    return;
                }

                $session->data["atm"] = $session->data["played_time"];
                $session->addValue("money", $money);

                Main::getInstance()->getLogger()->info("Le joueur " . $player->getName() . " vient de convertir " . Util::formatDurationFromSeconds($remaining) . " de jeu en " . Util::formatNumberWithSuffix($money) . "$");
                $player->sendMessage(Util::PREFIX . "Vous venez de convertir §n" . Util::formatDurationFromSeconds($remaining) . " §fde jeu en §n" . Util::formatNumberWithSuffix($money) . "$ §f!");
            });
            $form->setTitle("ATM");
            $form->setContent(Util::PREFIX . "Actuellement vous avez §n" . Util::formatDurationFromSeconds($remaining) . " §fnon converties, ce qui est équivalent à §n" . Util::formatNumberWithSuffix($money) . "$ §f!\n\nCliquez sur les boutons ci-dessous si vous voulez les convertir");
            $form->addButton("Convertir");
            $form->addButton("Quitter");
            $sender->sendForm($form);
        }
    }

    protected function prepare(): void
    {
    }
}