<?php

namespace Kitmap\task\repeat\child;

use Kitmap\Main;
use Kitmap\Session;
use Kitmap\Util;
use pocketmine\player\Player;

class FarmWarsTask
{
    public static ?int $current = null;
    public static array $players = [];

    public static function run(): void
    {
        if (!is_numeric(self::$current)) {
            return;
        } else if (self::$current % 30 == 0) {
            Main::getInstance()->getServer()->broadcastMessage(Util::PREFIX . "L'event §nFARM WARS §fest en cours ! Cassez le §nMAXIMUM §fd'agriculture pour augmenter en §nscore §fafin de gagner des packs !");
        }

        self::$current--;

        if (self::$current <= 0) {
            self::endEvent();
        }
    }

    private static function endEvent(): void
    {
        if (empty(self::$players)) {
            Main::getInstance()->getServer()->broadcastMessage(Util::PREFIX . "L'event §nFARM WARS §fs'est terminé, mais personne n'a participé...");
            self::$current = null;
            self::$players = [];
            return;
        }

        arsort(self::$players);

        $winners = array_slice(self::$players, 0, 3, true);
        $packs = [3, 2, 1];
        $i = 0;

        Main::getInstance()->getServer()->broadcastMessage(Util::PREFIX . "L'event §nFARM WARS §fest terminé ! Voici les §n3 §fmeilleurs joueurs:");

        foreach ($winners as $name => $score) {
            $packReward = $packs[$i] ?? 1;
            $moneyReward = 25000;

            $player = Main::getInstance()->getServer()->getPlayerExact($name);

            if ($player instanceof Player) {
                $session = Session::get($player);

                $session->addValue("pack", $packReward);
                $session->addValue("money", $moneyReward);
            }

            Main::getInstance()->getServer()->broadcastMessage(Util::PREFIX . "Le joueur §n" . $name . " §fa terminé §n#" . ($i + 1) . " §favec §n" . $score . " §fpoints et a gagné §n" . $packReward . " §fpacks et §n25k$ §f!");
            $i++;
        }

        Main::getInstance()->getServer()->broadcastMessage(Util::PREFIX . "À bientôt pour un prochain event !");

        // Réinitialisation de l'event
        self::$current = null;
        self::$players = [];
    }


    public static function incrementScore(Player $player): void
    {
        if (is_null(FarmWarsTask::$current)) {
            return;
        }

        self::$players[$player->getName()] = (self::$players[$player->getName()] ?? 0) + 1;
    }

    public static function getScoreboardLines(Player $player): array
    {
        arsort(self::$players);
        $top = array_slice(self::$players, 0, 3, true);

        $lines = [];

        if (empty($top)) {
            for ($i = 1; $i <= 3; $i++) {
                $lines[] = "  §7| §fAucun joueur";
            }
        } else {
            foreach ($top as $name => $score) {
                if ($name === $player->getName()) {
                    $name = "§9" . $name . "§f";
                }

                $lines[] = "  §7| §f" . $name . ": §n" . $score;
            }
        }

        $playerRank = array_search($player->getName(), array_keys(self::$players)) + 1;

        if ($playerRank > 3) {
            $lines[] = "  §7| §9" . $player->getName() . "§f: §n" . (self::$players[$player->getName()] ?? 0);
        }

        return $lines;
    }
}