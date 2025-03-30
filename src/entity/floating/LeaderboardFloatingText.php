<?php

namespace Kitmap\entity\floating;

use Kitmap\command\player\Top;
use Kitmap\handler\Cache;
use Kitmap\Util;

class LeaderboardFloatingText extends FloatingText
{
    private int $currentCategory = 0;

    protected function getPeriod(): int
    {
        return 600;
    }

    protected function getUpdate(): string
    {
        $this->currentCategory++;
        $i = 1;

        if (!isset(Cache::$config["top"][$this->currentCategory])) {
            $this->currentCategory = 0;
        }

        $category = Cache::$config["top"][$this->currentCategory];

        $format = "§7{COUNT}. §n{KEY} §8(§f{VALUE}§8)";

        $top = Top::getTopByCategory($category);
        $response = Util::arrayToPage($top, 1, 10);

        $str = Util::PREFIX . Top::getTopName($category) . Util::IARROW;

        foreach ($response[1] as $key => $value) {
            if ($category === "nerd") {
                $value = Util::formatDurationFromSeconds(intval($value));
            }

            $str .= "\n§r" . str_replace(["{KEY}", "{VALUE}", "{COUNT}"], [$key, $value, $i], $format);
            $i++;
        }

        return $str;
    }
}