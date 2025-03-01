<?php

namespace Kitmap\handler;

use jojoe77777\FormAPI\CustomForm;
use jojoe77777\FormAPI\SimpleForm;
use Kitmap\Main;
use Kitmap\Session;
use Kitmap\task\PackAnimationTask;
use Kitmap\Util;
use muqsit\invmenu\InvMenu;
use muqsit\invmenu\type\InvMenuTypeIds;
use pocketmine\block\Block;
use pocketmine\event\player\PlayerItemUseEvent;
use pocketmine\item\VanillaItems;
use pocketmine\player\Player;
use pocketmine\world\sound\ChestOpenSound;

class Pack
{
    const ITEMS_AMOUNT = 3;

    public static function openPackUI(Player $player, ?Block $animationBlock = null): void
    {
        $session = Session::get($player);

        $form = new SimpleForm(function (Player $player, mixed $data) use ($session, $animationBlock) {
            if (!is_int($data)) {
                return;
            }

            switch ($data) {
                case 0:
                    if (0 >= $session->data["packs"]) {
                        $player->sendMessage(Util::PREFIX . "Vous ne possedez pas de pack actuellement");
                        return;
                    }

                    self::openPack($player, $animationBlock);
                    break;
                case 1:
                    self::buyPack($player);
                    break;
                case 2:
                    self::previsualizePack($player);
                    break;
            }
        });
        $form->setTitle("Pack ");
        $form->setContent(Util::PREFIX . "Vous possedez actuellement §q" . $session->data["packs"] . " §fpack(s)");
        $form->addButton("Ouvrir un pack");
        $form->addButton("Acheter un pack");
        $form->addButton("Visualiser les lots");
        $player->sendForm($form);
    }

    public static function openPack(Player $player, ?Block $animationBlock): void
    {
        $session = Session::get($player);

        if (0 >= $session->data["packs"]) {
            $player->sendMessage(Util::PREFIX . "Vous ne possedez pas de pack actuellement");
            return;
        }

        $session->addValue("packs", 1, true);

        if ($animationBlock instanceof Block) {
            if ($session->inCooldown("pack")) {
                $player->sendMessage(Util::PREFIX . "Veuillez attendre un peu avant de ré-ouvrir un §qpack §f(commande /pack pour éviter l'animation)..");
                return;
            }

            $player->getWorld()->addSound($animationBlock->getPosition()->add(0.5, 0.5, 0.5), new ChestOpenSound());
            $player->sendMessage(Util::PREFIX . "Vous ouvrez un §q§lPACK§r§f...");

            Main::getInstance()->getServer()->broadcastPopup(Util::PREFIX . "§q" . $player->getName() . " §fouvre un §q§lPACK" . Util::IARROW);
            Main::getInstance()->getScheduler()->scheduleRepeatingTask(new PackAnimationTask($player, $animationBlock), 1);
        } else {
            $player->getWorld()->addSound($player->getPosition()->add(0.5, 0.5, 0.5), new ChestOpenSound());

            $randomItems = self::chooseRandomItems(self::ITEMS_AMOUNT);
            $player->sendMessage(Util::PREFIX . "Grâce à votre §qPACK §fvous venez de gagner:");

            $prizeList = [];

            foreach ($randomItems as $randomItem) {
                $player->sendMessage(Util::PREFIX . $randomItem["name"]);

                Util::addItem($player, $randomItem["item"]);
                $prizeList[] = $randomItem["name"];
            }

            $player->sendMessage(Util::PREFIX . "Vos lots ont été mis dans votre inventaire");

            Main::getInstance()->getLogger()->info("Le joueur " . $player->getName() . " vient d'ouvrir un pack (ses lots: " . implode(", ", $prizeList) . ")");
            Main::getInstance()->getServer()->broadcastTip(Util::PREFIX . "Le joueur §q" . $player->getName() . " §fvient d'ouvrir un §qPACK §f!");
        }
    }

    public static function getItems(): array
    {
        $items = [];

        foreach (Cache::$config["pack"]["rewards"] as $data) {
            $items[$data[1]] = Util::parseItem($data[2]);
        }

        return $items;
    }

    private static function buyPack(Player $player): void
    {
        $session = Session::get($player);

        $form = new CustomForm(function (Player $player, mixed $data) use ($session) {
            if (!is_array($data) || !isset($data[1]) || !isset($data[2]) || !is_bool($data[2]) || !$data[2]) {
                return;
            }

            switch ($data[1]) {
                case 0:
                    if (Cache::$config["pack"]["gem"] > $session->data["gem"]) {
                        $player->sendMessage(Util::PREFIX . "Vous ne possedez pas assez de gemmes pour acheter un §qpack");
                        return;
                    }

                    $session->addValue("gem", Cache::$config["pack"]["gem"], true);
                    $player->sendMessage(Util::PREFIX . "Vous venez d'acheter un pack avec §q" . Util::formatNumberWithSuffix(Cache::$config["pack"]["gem"]) . " §fgemmes");

                    Main::getInstance()->getLogger()->info("Le joueur " . $player->getName() . " vient d'acheter un pack avec des gemmes");
                    break;
                case 1:
                    if (Cache::$config["pack"]["money"] > $session->data["money"]) {
                        $player->sendMessage(Util::PREFIX . "Vous ne possedez pas assez de pièces pour acheter un pack");
                        return;
                    }

                    $session->addValue("money", Cache::$config["pack"]["money"], true);
                    $player->sendMessage(Util::PREFIX . "Vous venez d'acheter un pack avec §q" . Util::formatNumberWithSuffix(Cache::$config["pack"]["money"]) . " §fpièces");

                    Main::getInstance()->getLogger()->info("Le joueur " . $player->getName() . " vient d'acheter un pack avec des pièces");
                    break;
                default:
                    return;
            }

            $session->addValue("packs");
            self::openPackUI($player);
        });
        $form->setTitle("Pack");
        $form->addLabel(Util::PREFIX . "Êtes vous sur d'acheter un §qpack §f?\nPrix d'un pack: §q" . Util::formatNumberWithSuffix(Cache::$config["pack"]["money"]) . " §fpièces ou §a" . Util::formatNumberWithSuffix(Cache::$config["pack"]["gem"]) . " §fgemmes\n\nVous possedez §q" . $session->data["gem"] . " §fgemme(s)\nVous possedez §q" . $session->data["money"] . " §fpièces(s)\n");
        $form->addDropdown("Méthode de payement", ["gemmes", "pièces"]);
        $form->addToggle("Acheter un pack?", true);
        $player->sendForm($form);
    }

    private static function previsualizePack(Player $player): void
    {
        $length = count(Cache::$config["pack"]["rewards"]);

        if ($length > 27) {
            $menu = InvMenu::create(InvMenuTypeIds::TYPE_DOUBLE_CHEST);
        } else {
            $menu = InvMenu::create(InvMenuTypeIds::TYPE_CHEST);
        }

        $menu->setName("Lots possible d'un pack");
        $menu->setListener(InvMenu::readonly());

        foreach (Cache::$config["pack"]["rewards"] as $data) {
            $item = Util::parseItem($data[2]);
            $item->setCustomName("§r§q" . $data[0] . "% §f- " . ucfirst($data[1]));
            $item->getNamedTag()->setInt("menu_item", 0);
            $menu->getInventory()->addItem($item);
        }

        $menu->send($player);
    }

    public static function executeInteractPackItem(Player $player, PlayerItemUseEvent $event): bool
    {
        $item = $player->getInventory()->getItemInHand();
        if (is_null($item->getNamedTag()->getTag("type")) || is_null($item->getNamedTag()->getTag("data"))) {
            return false;
        }

        $type = $item->getNamedTag()->getInt("type");
        $data = $item->getNamedTag()->getInt("data");

        $session = Session::get($player);

        switch ($type) {
            case 0:
                $session->addValue("money", $data);

                Main::getInstance()->getLogger()->info("Le joueur " . $player->getName() . " vient d'utiliser un billet de " . $data . " pièces");
                $player->sendMessage(Util::PREFIX . "Vous venez d'utiliser un billet et recevoir §q" . $data . " §fpièces");
                break;
            case 1:
                $name = match ($data) {
                    1 => "champion",
                    2 => "prince",
                    3 => "elite",
                    4 => "roi",
                    default => "joueur"
                };

                Util::executeCommand("givekit \"" . $player->getName() . "\" " . $name);
                break;
            case 3:
                $session->addValue("gem", $data);

                Main::getInstance()->getLogger()->info("Le joueur " . $player->getName() . " vient d'utiliser un billet de " . $data . " gemmes");
                $player->sendMessage(Util::PREFIX . "Vous venez d'utiliser un billet et recevoir §q" . $data . " §fgemmes");
                break;
        }

        $item->pop();
        $player->getInventory()->setItemInHand($item->isNull() ? VanillaItems::AIR() : $item);

        $event->cancel();
        return true;
    }

    public static function chooseRandomItems(int $amount): array
    {
        $rewards = Cache::$config["pack"]["rewards"];

        $weightedRewards = [];

        foreach ($rewards as $reward) {
            $weight = intval($reward[0]);
            $name = $reward[1];

            $item = Util::parseItem($reward[2]);

            $weightedRewards[] = [
                "weight" => $weight,
                "name" => $name,
                "item" => $item
            ];
        }

        $selectedItems = [];
        $availableRewards = $weightedRewards;

        for ($i = 0; $i < $amount; $i++) {
            if (empty($availableRewards)) {
                $availableRewards = $weightedRewards;
            }

            $totalWeight = array_sum(array_column($availableRewards, "weight"));
            $randomWeight = mt_rand(0, $totalWeight - 1);
            $currentWeight = 0;

            foreach ($availableRewards as $key => $reward) {
                $currentWeight += $reward["weight"];

                if ($randomWeight < $currentWeight) {
                    $selectedItems[] = [
                        "name" => $reward["name"],
                        "item" => $reward["item"]
                    ];

                    unset($availableRewards[$key]);
                    break;
                }
            }
        }

        return $selectedItems;
    }
}