<?php

namespace Kitmap\handler;

use Kitmap\Main;
use Kitmap\Session;
use Kitmap\task\repeat\child\DominationTask;
use Kitmap\task\repeat\child\FarmWarsTask;
use Kitmap\Util;
use pocketmine\network\mcpe\protocol\RemoveObjectivePacket;
use pocketmine\network\mcpe\protocol\SetDisplayObjectivePacket;
use pocketmine\network\mcpe\protocol\SetScorePacket;
use pocketmine\network\mcpe\protocol\types\ScorePacketEntry;
use pocketmine\player\Player;
use function mb_strtolower;

class ScoreFactory
{
    private const OBJECTIVE_NAME = "objective";
    private const CRITERIA_NAME = "dummy";

    private static array $scoreboards = [];

    public static function updateScoreboard(Player $player): void
    {
        $session = Session::get($player);

        if (!$session->data["scoreboard"]) {
            return;
        }

        if (self::hasScore($player)) {
            if (DominationTask::$currentDomination) {
                self::setScore($player, "§8§l» §r§nDomination §l§8«");
                $lines = DominationTask::getScoreboardLines();

                goto set;
            }

            self::setScore($player, "§8§l» §r§nNitro §l§8«");

            if (is_int(FarmWarsTask::$current)) {
                $lines = [
                    "§r",
                    "§l§nFARM WARS",
                    "  §7| §fTemps: §n" . Util::formatDurationFromSeconds(FarmWarsTask::$current, 1),
                    "§n",
                    ...FarmWarsTask::getScoreboardLines($player)
                ];
            } else {
                $faction = Faction::hasFaction($player) ? Faction::getFactionUpperName($session->data["faction"]) : "Aucune";

                $money = Util::formatNumberWithSuffix($session->data["money"]);
                $gem = Util::formatNumberWithSuffix($session->data["gem"]);

                $lines = [
                    "§r",
                    "§l§n" . $player->getDisplayName(),
                    "  §7| §fFaction: §n" . $faction,
                    "  §7| §fPièces: §n" . $money,
                    "  §7| §fGemmes: §n" . $gem,
                ];
            }

            $voteparty = Cache::$data["voteparty"] ?? 0;

            $lines = [
                ...$lines,
                "§r ",
                "§l§nINFOS §r§8(§7" . date("H:i") . "§8)",
                "  §7| §fJoueurs: §n" . count(Main::getInstance()->getServer()->getOnlinePlayers()),
                "  §7| §fVote: §n" . $voteparty . "§f/§n100",
                "§f",
                "    §7nitrofaction.fr    "
            ];

            set:

            foreach ($lines as $key => $value) {
                self::setScoreLine($player, $key + 1, $value);
            }
        } else {
            self::setScore($player, "§8§l» §r§nNitro §l§8«");
            self::updateScoreboard($player);
        }
    }

    public static function hasScore(Player $player): bool
    {
        return isset(self::$scoreboards[mb_strtolower($player->getXuid())]);
    }

    public static function setScore(Player $player, string $displayName, int $slotOrder = SetDisplayObjectivePacket::SORT_ORDER_ASCENDING, string $displaySlot = SetDisplayObjectivePacket::DISPLAY_SLOT_SIDEBAR, string $objectiveName = self::OBJECTIVE_NAME, string $criteriaName = self::CRITERIA_NAME): void
    {
        if (isset(self::$scoreboards[mb_strtolower($player->getXuid())])) {
            self::removeScore($player);
        }

        $pk = new SetDisplayObjectivePacket();
        $pk->displaySlot = $displaySlot;

        $pk->objectiveName = $objectiveName;
        $pk->displayName = $displayName;

        $pk->criteriaName = $criteriaName;
        $pk->sortOrder = $slotOrder;

        $player->getNetworkSession()->sendDataPacket($pk);
        self::$scoreboards[mb_strtolower($player->getXuid())] = $objectiveName;
    }

    public static function removeScore(Player $player): void
    {
        $objectiveName = self::$scoreboards[mb_strtolower($player->getXuid())] ?? self::OBJECTIVE_NAME;
        $pk = new RemoveObjectivePacket();
        $pk->objectiveName = $objectiveName;
        $player->getNetworkSession()->sendDataPacket($pk);
        unset(self::$scoreboards[mb_strtolower($player->getXuid())]);
    }

    public static function setScoreLine(Player $player, int $line, string $message, int $type = ScorePacketEntry::TYPE_FAKE_PLAYER): void
    {
        $entry = new ScorePacketEntry();

        $entry->objectiveName = self::$scoreboards[mb_strtolower($player->getXuid())] ?? self::OBJECTIVE_NAME;
        $entry->type = $type;
        $entry->customName = $message;
        $entry->score = $line;
        $entry->scoreboardId = $line;

        $pk = new SetScorePacket();

        $pk->type = $pk::TYPE_CHANGE;
        $pk->entries[] = $entry;

        $player->getNetworkSession()->sendDataPacket($pk);
    }
}