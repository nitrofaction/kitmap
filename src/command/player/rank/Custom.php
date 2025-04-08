<?php /** @noinspection PhpUnused */

namespace Kitmap\command\player\rank;

use CortexPE\Commando\args\OptionArgument;
use CortexPE\Commando\BaseCommand;
use jojoe77777\FormAPI\CustomForm;
use Kitmap\handler\Rank;
use Kitmap\Session;
use Kitmap\Util;
use pocketmine\command\CommandSender;
use pocketmine\permission\DefaultPermissions;
use pocketmine\player\Player;
use pocketmine\plugin\PluginBase;

class Custom extends BaseCommand
{
    public function __construct(PluginBase $plugin)
    {
        parent::__construct(
            $plugin,
            "custom",
            "Permet de personnaliser son grade"
        );

        $this->setAliases(["perso", "customrank", "personnaliser"]);
        $this->setPermissions([DefaultPermissions::ROOT_USER]);
    }

    public function onRun(CommandSender $sender, string $aliasUsed, array $args): void
    {
        if ($sender instanceof Player) {
            $session = Session::get($sender);
            $delete = !is_null($args["opt"] ?? null);

            $rank = Rank::getRankBySession($session);

            if ($rank !== "custom") {
                $sender->sendMessage(Util::PREFIX . "Vous devez avoir le grade personnalisé pour cette commande");
                return;
            } else if ($delete) {
                $session->data["format"]["custom"] = null;
                $sender->sendMessage(Util::PREFIX . "Vous venez de réinitialiser votre grade personnalisé");
                return;
            }

            $form = new CustomForm(function (Player $player, mixed $data) use ($session) {
                if (!is_array($data)) {
                    return;
                }

                $custom = preg_replace("/§[lo]+/i", "", $data[0]);

                if (strlen($custom) > 18) {
                    $player->sendMessage(Util::PREFIX . "Le nom de votre grade personnalisé ne doit pas dépasser les §n18 §fcaractères");
                    return;
                } else if (1 > strlen($custom)) {
                    $player->sendMessage(Util::PREFIX . "Le nom de votre grade personnalisé doit contenir au minimum §n1 §fcaractère");
                    return;
                }

                $session->data["format"]["custom"] = $custom;
                $player->sendMessage(Util::PREFIX . "Votre grade personnalisé est désormais \"§n" . $custom . "§f\" ! Pour changer la couleur de votre grade (nom, faction..), faites la commande §n/color");
            });
            $form->setTitle("§nGrade Personnalisé");
            $form->addInput(Util::PREFIX . "Choissisez le nom de votre grade personnalisé:");
            $sender->sendForm($form);
        }
    }

    protected function prepare(): void
    {
        $this->registerArgument(0, new OptionArgument("opt", ["delete"], true));
    }
}