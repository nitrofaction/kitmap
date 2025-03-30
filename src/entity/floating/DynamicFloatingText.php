<?php

namespace Kitmap\entity\floating;

use Kitmap\command\player\Gambling;
use Kitmap\handler\Cache;
use Kitmap\handler\Faction;
use Kitmap\task\repeat\child\DominationTask;
use Kitmap\task\repeat\child\GamblingTask;
use Kitmap\task\repeat\child\KothTask;
use Kitmap\task\repeat\child\OutpostTask;
use Kitmap\Util;

class DynamicFloatingText extends FloatingText
{
    protected function getPeriod(): ?int
    {
        return $this->period;
    }

    protected function getUpdate(): string
    {
        $floatings = Cache::$config["pos"]["floating"];

        $position = $this->getLocation();
        $text = $position->getX() . ":" . $position->getY() . ":" . $position->getZ() . ":" . $position->getWorld()->getFolderName();

        $name = $floatings[$text] ?? false;

        if (is_bool($name)) {
            return "";
        }

        switch ($name) {
            case "domination":
                foreach (array_keys(Cache::$config["pos"]["domination"]) as $zone) {
                    if (DominationTask::insideZone($zone, $this->getPosition())) {
                        if (!DominationTask::$currentDomination) {
                            DominationTask::updateZoneBlocks($zone);
                            return Util::PREFIX . "Domination" . Util::IARROW . "\n§fAucun event §ndomination §fn'est en cours";
                        }

                        $status = DominationTask::$zones[$zone][1][0] ?? "uncaptured";
                        DominationTask::updateZoneBlocks($zone, $status);

                        $status = match ($status) {
                            "captured" => "§aCapturé",
                            "uncaptured" => "§7Libre",
                            "contested" => "§cContesté"
                        };

                        $actual = DominationTask::$zones[$zone][0] ?? null;
                        $actual = is_null($actual) ? false : Faction::getFactionUpperName($actual);

                        if ($status === "§7Libre") {
                            $actual = false;
                        }

                        $actual = match (true) {
                            is_bool($actual) => "§fAucune faction contrôle le point",
                            default => "§fLa faction §n" . $actual . " §fcontrôle le point"
                        };

                        return Util::PREFIX . "Point " . $zone . Util::IARROW . "\n" . $actual . "\n§fStatus du point: " . $status;
                    }
                }
                break;
            case "koth":
                if (is_numeric(KothTask::$currentKoth)) {
                    $player = KothTask::$currentPlayer;
                    $player = is_null($player) ? "Aucun joueur" : $player;

                    $remaining = Util::formatDurationFromSeconds(KothTask::$currentKoth);
                    return Util::PREFIX . "Koth" . Util::IARROW . "\n§n" . $player . " §fcontrôle le koth actuellement\n§fTemps restant : §n" . $remaining;
                } else {
                    return Util::PREFIX . "Koth" . Util::IARROW . "\n§fAucun event §nkoth §fn'est en cours";
                }
            case "outpost":
                if (!is_null(Cache::$data["outpost"])) {
                    $remaining = Util::formatDurationFromSeconds(OutpostTask::$nextReward);
                    $faction = Faction::getFactionUpperName(Cache::$data["outpost"]);

                    return Util::PREFIX . "Outpost" . Util::IARROW . "\n§fLa faction §n" . $faction . " §fcontrôle l'outpost\n§fRécompense dans §n" . $remaining . "\n§fPlus controlé dans §n" . OutpostTask::$currentOutpost . " §fsecondes";
                } else {
                    $remaining = Util::formatDurationFromSeconds(OutpostTask::$currentOutpost);
                    return Util::PREFIX . "Outpost" . Util::IARROW . "\n§nAucune §ffaction ne contrôle l'outpost\n§fOutpost contrôlé dans §n" . $remaining;
                }
            case "gambling":
                if (GamblingTask::$currently) {
                    return Util::PREFIX . "Gambling" . Util::IARROW . "\nUn gambling est actuellement en cours depuis §n" . Util::formatDurationFromSeconds(GamblingTask::$since, 1) . "\nLe gambling actuel oppose §n" . GamblingTask::$players[0] . " §fet §n" . GamblingTask::$players[1] . "\n\n§n" . count(Gambling::$gamblings) . " §fautre(s) §ngambling(s) §fsont en attente d'adversaire";
                } else {
                    return Util::PREFIX . "Gambling" . Util::IARROW . "\nAucun gambling n'est actuellement en cours\n§n" . count(Gambling::$gamblings) . " gambling(s) §fsont en attente d'adversaire\nPour rejoindre un gambling utilisez la commande §n/gambling";
                }
            case "money-zone":
                $this->period = null;
                return Util::PREFIX . "Zone Money" . Util::IARROW . "\nReste ici et gagne §n50§f$ toutes les §n3 §fsecondes\n§fATTENTION ! Tu dois être §nseul §fsur la plateforme";
            case "jump":
                $this->period = null;
                return Util::PREFIX . "Jump" . Util::IARROW . "\nLe niveau §nROUGE §fcontient une récompense §n!!";
        }

        if ($name[0] === "#") {
            $text = substr($name, 1);
        } else {
            $text = "§r   \n  " . Util::stringToUnicode($name, 1) . "  \n§r   ";
        }

        $this->period = null;
        return $text;
    }
}