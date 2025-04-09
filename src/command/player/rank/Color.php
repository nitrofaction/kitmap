<?php /** @noinspection PhpUnused */

namespace Kitmap\command\player\rank;

use CortexPE\Commando\BaseCommand;
use jojoe77777\FormAPI\SimpleForm;
use Kitmap\handler\Cache;
use Kitmap\handler\Rank;
use Kitmap\Session;
use Kitmap\Util;
use pocketmine\command\CommandSender;
use pocketmine\permission\DefaultPermissions;
use pocketmine\player\Player;
use pocketmine\plugin\PluginBase;

class Color extends BaseCommand
{
    public function __construct(PluginBase $plugin)
    {
        parent::__construct(
            $plugin,
            "color",
            "Permet de changer la couleur de son pseudo"
        );

        $this->setAliases(["couleur", "colorname", "pseudo"]);
        $this->setPermissions([DefaultPermissions::ROOT_USER]);
    }

    public function onRun(CommandSender $sender, string $aliasUsed, array $args): void
    {
        if ($sender instanceof Player) {
            $session = Session::get($sender);

            $rank = Rank::getRankBySession($session);
            $defaultColor = Rank::getRankValue($rank, "color");

            if (!Rank::hasRank($sender, "vip-plus")) {
                $sender->sendMessage(Util::PREFIX . "Pour accèder à la commande §n/color§f, vous devez avoir au minimum le grade §nVIP+ §f! Pour cela, achetez un grade sur la boutique: §nstore.nitrofaction.fr");
                return;
            }

            $form = new SimpleForm(function (Player $player, mixed $data) use ($defaultColor, $session) {
                if (!is_string($data) || !isset(Cache::$config["color"][$data])) {
                    return;
                }

                $name = Cache::$config["color"][$data];
                $player->sendMessage(Util::PREFIX . "Vous venez de changer la couleur de votre pseudo en " . $data . strtolower($name) . " §f!");

                $session->data["format"]["color"] = $data;
            });
            $form->setTitle("§nCouleurs");
            $form->setContent(Util::PREFIX . "Cliquez sur la couleur de votre choix et votre pseudo sera changé par celle-ci");
            $form->addButton($defaultColor . "Couleur par défaut", label: $defaultColor);
            foreach (Cache::$config["color"] as $color => $name) {
                $form->addButton($color . $name, label: $color);
            }
            $sender->sendForm($form);
        }
    }

    protected function prepare(): void
    {
    }
}