<?php

namespace Kitmap\handler;

use Kitmap\Main;
use Kitmap\Session;
use Kitmap\Util;
use pocketmine\permission\DefaultPermissionNames;
use pocketmine\permission\DefaultPermissions;
use pocketmine\permission\Permission;
use pocketmine\permission\PermissionManager;
use pocketmine\player\Player;

class Rank
{
    public const GROUP_STAFF = "nitro.staff";

    public function __construct()
    {
        $permManager = PermissionManager::getInstance();
        $opRoot = $permManager->getPermission(DefaultPermissions::ROOT_OPERATOR);

        $permManager->addPermission(new Permission(Rank::GROUP_STAFF));
        $opRoot->addChild(Rank::GROUP_STAFF, true);
    }

    public static function existRank(string $rank): bool
    {
        return isset(Cache::$config["rank"][$rank]);
    }

    public static function getEqualRankBySession(Session $session): string
    {
        $rank = $session->data["rank"];
        return Rank::getEqualRankByString($rank);
    }

    public static function getEqualRankByString(string $rank): string
    {
        if (Rank::isStaff($rank)) {
            return "custom";
        } else {
            if ($rank === "createur") {
                return "ultra";
            }
            return $rank;
        }
    }

    public static function isStaff(?string $rank): bool
    {
        return in_array($rank, ["guide", "moderateur", "sm", "administrateur", "fondateur"]);
    }

    public static function getEqualRankByName(string $name): string
    {
        $rank = Rank::getRank($name);
        return Rank::getEqualRankByString($rank);
    }

    public static function getRank(string $name): ?string
    {
        $name = strtolower($name);

        /** @noinspection PhpDeprecationInspection */
        $player = Main::getInstance()->getServer()->getPlayerByPrefix($name);

        if ($player instanceof Player) {
            $session = Session::get($player);
            $rank = Rank::getRankBySession($session);
        } else {
            $file = Util::getFile("data/players/" . $name);
            $rank = $file->get("rank", "joueur");
        }
        return $rank;
    }

    public static function getRankBySession(Session $session): string
    {
        return $session->data["rank"];
    }

    public static function hasRank(Player $player, string $rank): bool
    {
        return Rank::hasRankOffline(Rank::getRank($player->getName()), $rank);
    }

    public static function hasRankOffline(string $rank, string $needle): bool
    {
        $ranks = array_keys(Cache::$config["rank"]);
        return array_search($rank, $ranks) >= array_search($needle, $ranks);
    }

    public static function setRank(string $name, string $rank): void
    {
        $name = strtolower($name);
        $player = Main::getInstance()->getServer()->getPlayerExact($name);

        if ($player instanceof Player) {
            $session = Session::get($player);

            $session->removeCooldown("kit");
            $session->data["rank"] = $rank;

            Rank::updateNameTag($player);
            Rank::addPermissions($player);

            Rank::saveRank($name, $rank);
        } else {
            $file = Util::getFile("data/players/" . $name);

            if ($file->getAll() !== []) {
                $file->set("rank", $rank);
                $file->save();

                Rank::saveRank($name, $rank);
            }
        }
    }

    public static function updateNameTag(Player $player): void
    {
        $name = $player->getName();
        $rank = ($name === $player->getDisplayName()) ? Rank::getRank($name) : "joueur";

        $prefix = Rank::getRankValue($rank, "gamertag");
        $replace = Rank::setReplace($prefix, $player);

        $player->setNameTag($replace);
        $player->setNameTagAlwaysVisible();
    }

    public static function getRankValue(string $rank, string $value): mixed
    {
        return Cache::$config["rank"][$rank][$value] ?? "joueur";
    }

    public static function setReplace(string $replace, Player $player, string $msg = ""): string
    {
        $session = Session::get($player);
        Faction::hasFaction($player);

        $rank = Rank::getrankBySession($session);

        $faction = $session->data["faction"];
        $faction = (is_null($faction)) ? "..." : Cache::$factions[$faction]["upper_name"];

        $color = !is_string($session->data["format"]["color"]) ? Rank::getRankValue($rank, "color") : $session->data["format"]["color"];
        $custom = is_null($session->data["format"]["custom"]) ? Rank::getRankValue($rank, "name") : $session->data["format"]["custom"];

        return str_replace(
            ["{name}", "{fac}", "{msg}", "{color}", "{custom}"],
            [$player->getDisplayName(), $faction, $msg, $color, $custom],
            $replace
        );
    }

    public static function addPermissions(Player $player): void
    {
        $session = Session::get($player);

        if (Rank::isStaff(Rank::getRankBySession($session)) || $player->hasPermission(DefaultPermissions::ROOT_OPERATOR)) {
            $player->addAttachment(Main::getInstance(), Rank::GROUP_STAFF, true);

            $player->addAttachment(Main::getInstance(), DefaultPermissionNames::COMMAND_TELEPORT_OTHER, true);
            $player->addAttachment(Main::getInstance(), DefaultPermissionNames::COMMAND_TELEPORT_SELF, true);

            if (!in_array(Rank::getRankBySession($session), ["guide", "moderateur"])) {
                $player->addAttachment(Main::getInstance(), DefaultPermissionNames::COMMAND_GAMEMODE_SELF, true);
            }
        }
    }

    public static function saveRank(string $value, string $key): void
    {
        $ownings = Util::getFile("ownings");

        $ownings->set($value, $key);
        $ownings->save();
    }
}