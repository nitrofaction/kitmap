<?php /** @noinspection PhpUnused */

namespace Kitmap\command\player;

use CortexPE\Commando\BaseCommand;
use jojoe77777\FormAPI\CustomForm;
use jojoe77777\FormAPI\SimpleForm;
use Kitmap\handler\Cache;
use Kitmap\handler\Cosmetic as Api;
use Kitmap\Session;
use Kitmap\Util;
use pocketmine\command\CommandSender;
use pocketmine\permission\DefaultPermissions;
use pocketmine\player\Player;
use pocketmine\plugin\PluginBase;

class Cosmetic extends BaseCommand
{
    public function __construct(PluginBase $plugin)
    {
        parent::__construct(
            $plugin,
            "cosmetics",
            "Permet d'avoir accès au cosmetiques"
        );

        $this->setAliases(["cosmetic", "cosmetique", "cosmetiques"]);
        $this->setPermissions([DefaultPermissions::ROOT_USER]);
    }

    public function onRun(CommandSender $sender, string $aliasUsed, array $args): void
    {
        if ($sender instanceof Player) {
            $session = Session::get($sender);

            $form = new SimpleForm(function (Player $player, mixed $data) use ($session) {
                if (!is_string($data)) {
                    return;
                }

                if ($data === "remove") {
                    $player->sendMessage(Util::PREFIX . "Votre skin a été réstauré, vous n'avez plus votre cosmetique d'activé");

                    $session->data["cosmetic"] = null;
                    Api::setDefaultSkin($player);
                } else {
                    $this->accessCategory($player, $session, $data);
                }
            });
            $form->setTitle("Cosmétiques");
            $form->setContent(Util::PREFIX . "Veuillez choisir la catégorie de votre choix");

            foreach (Cache::$config["cosmetic"] as $name => $cosmetic) {
                $form->addButton($cosmetic["name"], 0, $cosmetic["texture"], $name);
            }

            $form->addButton("Supprimer son cosmetique", -1, "", "remove");
            $sender->sendForm($form);
        }
    }

    public function accessCategory(Player $player, Session $session, string $type): void
    {
        $cosmetics = Cache::$config["cosmetic"][$type] ?? null;

        if (is_null($cosmetics)) {
            return;
        }

        $form = new SimpleForm(function (Player $player, mixed $data) use ($session) {
            if (!is_string($data)) {
                return;
            }

            if (in_array($data, $session->data["cosmetics"])) {
                $this->setCosmetic($player, $data);
            } else {
                $this->buyCosmetic($player, $session, $data);
            }
        });

        $form->setTitle("Cosmétiques");
        $form->setContent(Util::PREFIX . "Veuillez choisir sur le cosmétique que vous voulez acheter ou mettre");

        foreach ($cosmetics as $name => $cosmetic) {
            if (!is_array($cosmetic)) {
                continue;
            }

            $id = $type . ":" . $name;

            $name = match (true) {
                !in_array($id, $session->data["cosmetics"]) => "\n§cNon Débloqué",
                $session->data["cosmetic"] === $id => "\n§aCosmétique Actuel",
                default => ""
            };

            $form->addButton($cosmetic["button"] . $name, 0, $cosmetic["texture"], $id);
        }

        $player->sendForm($form);
    }

    private function setCosmetic(Player $player, string $cosmetic): void
    {
        if (strlen(Session::get($player)->data["skin"]->getSkinData()) !== 64 * 64 * 4) {
            $player->sendMessage(Util::PREFIX . "Vous ne pouvez pas mettre de cosmétique avec votre skin actuel");
            return;
        }

        [$type, $name] = explode(":", $cosmetic);
        $data = Cache::$config["cosmetic"][$type][$name];

        Session::get($player)->data["cosmetic"] = $cosmetic;
        Api::setCosmetic($player, $type, $name);

        $player->sendMessage(Util::PREFIX . "Vous venez de mettre le cosmetique §q" . $data["button"]);
    }

    private function buyCosmetic(Player $player, Session $session, string $cosmetic): void
    {
        [$type, $name] = explode(":", $cosmetic);

        $cosmeticData = Cache::$config["cosmetic"][$type][$name];

        $form = new CustomForm(function (Player $player, mixed $data) use ($session, $cosmetic, $cosmeticData) {
            if (!is_array($data) || !isset($data[1]) || !isset($data[2]) || !is_bool($data[2]) || !$data[2]) {
                return;
            }

            $devise = match ($data[1]) {
                1 => " §fgemmes",
                default => "$"
            };

            $money = match ($data[1]) {
                1 => "gem",
                default => "money"
            };

            if ($cosmeticData[$money] > $session->data[$money]) {
                $player->sendMessage(Util::PREFIX . "Vous ne possedez pas assez de " . $devise . " pour acheter le cosmétique §q" . $cosmeticData["button"]);
                return;
            }

            $session->data["cosmetics"][] = $cosmetic;

            $session->addValue($money, $cosmeticData[$money], true);
            $player->sendMessage(Util::PREFIX . "Vous venez d'acheter le cosmétique §q" . $cosmeticData["button"] . " §favec §q" . $cosmeticData[$money] . $devise);
        });
        $form->setTitle("Cosmétiques");

        if ($cosmeticData["money"] > 0) {
            $form->addLabel(Util::PREFIX . $cosmeticData["description"] . "\n\n§fPrix: §q" . Util::formatNumberWithSuffix($cosmeticData["money"]) . "$ §fou §q" . $cosmeticData["gem"] . " §fgemmes\n\nVous possedez §q" . $session->data["gem"] . " §fgemmes\nVous possedez §q" . $session->data["money"] . "$\n");
            $form->addDropdown("Méthode de payement", ["Argent", "Gemmes"]);
            $form->addToggle("Acheter le cosmetique §q" . $cosmeticData["button"] . " §f?", true);
        } else {
            $form->addLabel(Util::PREFIX . $cosmeticData["description"] . "\n\nCelui-ci n'est pas achetable");
        }

        $player->sendForm($form);
    }

    protected function prepare(): void
    {
    }
}