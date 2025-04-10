<?php

namespace Kitmap\handler;

use DateTime;
use DateTimeZone;
use jojoe77777\FormAPI\CustomForm;
use jojoe77777\FormAPI\SimpleForm;
use Kitmap\Main;
use Kitmap\Session;
use Kitmap\task\PackAnimationTask;
use Kitmap\Util;
use muqsit\invmenu\InvMenu;
use muqsit\invmenu\type\InvMenuTypeIds;
use pocketmine\block\Block;
use pocketmine\data\bedrock\EnchantmentIdMap;
use pocketmine\item\enchantment\EnchantmentInstance;
use pocketmine\item\Item;
use pocketmine\item\VanillaItems;
use pocketmine\player\Player;
use pocketmine\world\sound\ChestOpenSound;

class Pack
{
    public static int $itemsAmount = 3;

    public static function openPackUI(Player $player, ?Block $animationBlock = null): void
    {
        $session = Session::get($player);
        $primeTime = self::getPrimeTime();

        $form = new SimpleForm(function (Player $player, mixed $data) use ($session, $animationBlock) {
            if (!is_int($data)) {
                return;
            }

            switch ($data) {
                case 0:
                    if (0 >= $session->data["pack"]) {
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
        $form->setTitle("§nPack ");
        $form->setContent(Util::PREFIX . "Vous possedez actuellement §n" . $session->data["pack"] . " §fpack(s)");
        if ($primeTime[0]) {
            $form->addButton("Ouvrir un pack\n§8(§aPrime time pendant encore: " . Util::formatDurationFromSeconds($primeTime[1], 1) . "§8)");
        } else {
            $form->addButton("Ouvrir un pack\n(§8§cPrime time dans: " . Util::formatDurationFromSeconds($primeTime[1], 1) . "§8)");
        }
        $form->addButton("Acheter un pack");
        $form->addButton("Visualiser les lots");
        $player->sendForm($form);
    }

    private static function getPrimeTime(): array
    {
        $tz = new DateTimeZone("Europe/Paris");

        $now = new DateTime("now", $tz);
        $primeTime = new DateTime("today 20:00:00", $tz);

        if ($now > $primeTime) {
            $latePrimeTime = new DateTime("today 21:00:00", $tz);

            if ($now > $latePrimeTime) {
                $primeTime = new DateTime("tomorrow 20:00:00", $tz);
                return [false, $primeTime->getTimestamp() - $now->getTimestamp()];
            } else {
                $primeTime = $latePrimeTime;
                return [true, $primeTime->getTimestamp() - $now->getTimestamp()];
            }
        }

        return [false, $primeTime->getTimestamp() - $now->getTimestamp()];
    }

    public static function openPack(Player $player, ?Block $animationBlock): void
    {
        $session = Session::get($player);

        if (0 >= $session->data["pack"]) {
            $player->sendMessage(Util::PREFIX . "Vous ne possedez pas de pack actuellement");
            return;
        }

        $session->addValue("pack", 1, true);

        if ($animationBlock instanceof Block) {
            if ($session->inCooldown("pack")) {
                $session->addValue("pack");
                $player->sendMessage(Util::PREFIX . "Veuillez attendre un peu avant de ré-ouvrir un §npack §f(commande /pack pour éviter l'animation)..");
                return;
            }

            $player->getWorld()->addSound($animationBlock->getPosition()->add(0.5, 0.5, 0.5), new ChestOpenSound());
            $player->sendMessage(Util::PREFIX . "Vous ouvrez un §n§lPACK§r§f...");

            Main::getInstance()->getServer()->broadcastPopup(Util::PREFIX . "§n" . $player->getName() . " §fouvre un §n§lPACK" . Util::IARROW);
            Main::getInstance()->getScheduler()->scheduleRepeatingTask(new PackAnimationTask($player, $animationBlock), 1);
        } else {
            $player->getWorld()->addSound($player->getPosition()->add(0.5, 0.5, 0.5), new ChestOpenSound());

            $randomItems = self::chooseRandomItems(self::$itemsAmount);
            $player->sendMessage(Util::PREFIX . "Grâce à votre §nPACK §fvous venez de gagner:");

            $prizeList = [];

            foreach ($randomItems as $randomItem) {
                $player->sendMessage(Util::PREFIX . $randomItem["name"]);

                Util::addItem($player, $randomItem["item"]);
                $prizeList[] = $randomItem["name"];
            }

            $player->sendMessage(Util::PREFIX . "Vos lots ont été mis dans votre inventaire");

            Main::getInstance()->getLogger()->info("Le joueur " . $player->getName() . " vient d'ouvrir un pack (ses lots: " . implode(", ", $prizeList) . ")");
            Main::getInstance()->getServer()->broadcastTip(Util::PREFIX . "Le joueur §n" . $player->getName() . " §fvient d'ouvrir un §nPACK §f!");
        }
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
                        $player->sendMessage(Util::PREFIX . "Vous ne possedez pas assez de gemmes pour acheter un §npack");
                        return;
                    }

                    $session->addValue("gem", Cache::$config["pack"]["gem"], true);
                    $player->sendMessage(Util::PREFIX . "Vous venez d'acheter un pack avec §n" . Util::formatNumberWithSuffix(Cache::$config["pack"]["gem"]) . " §fgemmes");

                    Main::getInstance()->getLogger()->info("Le joueur " . $player->getName() . " vient d'acheter un pack avec des gemmes");
                    break;
                case 1:
                    if (Cache::$config["pack"]["money"] > $session->data["money"]) {
                        $player->sendMessage(Util::PREFIX . "Vous ne possedez pas assez de pièces pour acheter un pack");
                        return;
                    }

                    $session->addValue("money", Cache::$config["pack"]["money"], true);
                    $player->sendMessage(Util::PREFIX . "Vous venez d'acheter un pack avec §n" . Util::formatNumberWithSuffix(Cache::$config["pack"]["money"]) . "$");

                    Main::getInstance()->getLogger()->info("Le joueur " . $player->getName() . " vient d'acheter un pack avec des pièces");
                    break;
                default:
                    return;
            }

            $session->addValue("pack");
            self::openPackUI($player);
        });
        $form->setTitle("§nPack");
        $form->addLabel(Util::PREFIX . "Êtes vous sur d'acheter un §npack §f?\nPrix d'un pack: §n" . Util::formatNumberWithSuffix(Cache::$config["pack"]["money"]) . " §fpièces ou §a" . Util::formatNumberWithSuffix(Cache::$config["pack"]["gem"]) . " §fgemmes\n\nVous possedez §n" . $session->data["gem"] . " §fgemme(s)\nVous possedez §n" . $session->data["money"] . " §fpièces(s)\n");
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

        $i = 0;

        foreach (Cache::$config["pack"]["rewards"] as $data) {
            $item = Util::parseItem($data[2]);
            $item->setCustomName("§r" . Util::PREFIX . ucfirst($data[1]) . Util::IARROW);
            $item->getNamedTag()->setInt("menu_item", 0);
            $menu->getInventory()->setItem($i, $item);
            $i++;
        }

        $menu->send($player);
    }

    public static function getItems(): array
    {
        $items = [];

        foreach (Cache::$config["pack"]["rewards"] as $data) {
            $items[$data[1]] = Util::parseItem($data[2]);
        }

        return $items;
    }


    public static function createPackPaper(int $amount): Item
    {
        $item = VanillaItems::PAPER();
        $item->getNamedTag()->setInt("pack", $amount);
        $item->addEnchantment(new EnchantmentInstance(EnchantmentIdMap::getInstance()->fromId(-1), 255));
        $item->setCustomName("§r§n" . $amount . " §fpack(s)");
        return $item;
    }
}