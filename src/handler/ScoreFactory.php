<?php

namespace Kitmap\handler;

use Kitmap\Main;
use Kitmap\Session;
use Kitmap\task\repeat\child\DominationTask;
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
                self::setScore($player, "§qDomination (§7" . date("H:i") . " #8§q)");
                $lines = DominationTask::getScoreboardLines();
            } else {
                self::setScore($player, "§8§l» §r§qNitro §l§8«");

                $faction = Faction::hasFaction($player) ? Faction::getFactionUpperName($session->data["faction"]) : "Aucune";

                $money = Util::formatNumberWithSuffix($session->data["money"]);
                $gem = Util::formatNumberWithSuffix($session->data["gem"]);

                $voteparty = Cache::$data["voteparty"] ?? 0;

                $lines = [
                    "§r",
                    "§l§q" . $player->getDisplayName(),
                    "  §7| §fFaction: §q" . $faction,
                    "  §7| §fPieces: §q" . $money,
                    "  §7| §fGemmes: §q" . $gem,
                    "§r ",
                    "§l§qINFOS §r§8(§7" . date("H:i") . "§8)",
                    "  §7| §fJoueurs: §q" . count(Main::getInstance()->getServer()->getOnlinePlayers()),
                    "  §7| §fVote: §q" . $voteparty . "§f/§q100",
                    "§f",
                    "    §7nitrofaction.fr    "
                ];
            }

            foreach ($lines as $key => $value) {
                self::setScoreLine($player, $key + 1, $value);
            }
        } else {
            self::setScore($player, "§8§l» §r§qNitro §l§8«");
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