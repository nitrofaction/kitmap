<?php

namespace Kitmap\handler;

use Kitmap\Session;
use Kitmap\Util;
use pocketmine\item\enchantment\EnchantmentInstance;
use pocketmine\item\enchantment\VanillaEnchantments;
use pocketmine\item\Item;
use pocketmine\item\VanillaItems;
use pocketmine\player\Player;
use pocketmine\world\sound\BlazeShootSound;
use pocketmine\world\sound\XpCollectSound;

class Job
{
    public static function getProgressBar(Player $player, string $job, int $option = 100): string
    {
        $levelIndex = self::getLevelIndex($player, $job);
        $levelData = self::getLevelDataByIndex($job, $levelIndex);

        $xp = self::getXp($player, $job);

        $nextXp = $levelData["xp"];
        $lastLevelIndex = self::getLastLevelIndex($job);

        if ($option === 1) {
            if ($levelIndex >= $lastLevelIndex) {
                return "0§q/§8-1 §q- §8Level: §q" . $levelIndex + 1;
            } else {
                return $xp . "§q/§8" . $nextXp . " §q- §8Level: §q" . $levelIndex + 1;
            }
        }

        if ($levelIndex >= $lastLevelIndex) {
            return "§qNiveau maximum atteint";
        } else {
            $progress = intval(max(1, round((($xp / $nextXp) * $option) / 2, 2)));
            return "§q" . str_repeat("|", $progress) . "§8" . str_repeat("|", ($option/2) - $progress);
        }
    }

    public static function getLevelIndex(Player $player, string $job): int
    {
        return Session::get($player)->data["jobs"][$job]["lvl"] ?? 0;
    }

    public static function getLevelDataByIndex(string $job, int $index): array
    {
        $key = array_keys(Cache::$config["job"][$job])[$index];
        return Cache::$config["job"][$job][$key];
    }

    public static function getXp(Player $player, string $job): int|float
    {
        return Session::get($player)->data["jobs"][$job]["xp"] ?? 0;
    }

    public static function getLastLevelIndex(string $job): int
    {
        return count(array_keys(Cache::$config["job"][$job])) - 1;
    }

    public static function addXp(Player $player, string $job, int|float $xp, bool $tip = true): void
    {
        if ($player->isCreative()) {
            return;
        }

        $session = Session::get($player);
        $xp = round($xp * (1 + (self::getBoost($session) / 100)));

        $levelIndex = self::getLevelIndex($player, $job);
        $levelData = self::getLevelDataByIndex($job, $levelIndex);

        $nextXp = $levelData["xp"];
        $totalXp = self::getXp($player, $job) + $xp;

        if ($tip) {
            $player->sendTip(Util::PREFIX . "+ §q" . $xp . " §f" . $job . " §8(" . self::getProgressBar($player, $job, 50) . "§8)" . Util::IARROW);
        }

        if ($totalXp > $nextXp) {
            $session->data["jobs"][$job]["lvl"]++;
            $session->data["jobs"][$job]["xp"] = 0;

            $rewards = $levelData["reward"];

            foreach ($rewards["items"] as $reward) {
                Util::addItem($player, Util::parseItem($reward));
            }

            $player->sendMessage(Util::PREFIX . "Vous venez de passer niveau §q" . $levelIndex + 2 . " §fdu métier de §q" . $job . " §f!");
            $player->sendMessage(Util::PREFIX . "Vous venez de recevoir " . $rewards["name"] . " §fen récompense de métier !");

            $player->broadcastSound(new BlazeShootSound());
        } else {
            $session->data["jobs"][$job]["xp"] += $xp;
            $player->broadcastSound(new XpCollectSound());
        }
    }

    public static function getBoost(Session $session): int
    {
        $init = Rank::getRankValue(Rank::getEqualRankBySession($session), "boost");

        if ($session->inCooldown("xp_boost")) {
            $init += $session->getCooldownData("xp_boost")[1];
        }
        return $init;
    }

    public static function createBoostPaper(int $boost, int $duration): Item
    {
        $item = VanillaItems::PAPER();
        $item->getNamedTag()->setInt("xp_boost", $boost);
        $item->getNamedTag()->setInt("duration", $duration);
        $item->addEnchantment(new EnchantmentInstance(VanillaEnchantments::FORTUNE()));
        $item->setCustomName("§r§fBoost d'xp de §q" . $boost . "% §7(" . Util::formatDurationFromSeconds($duration, 1) . ")");
        return $item;
    }
}