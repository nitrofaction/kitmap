<?php

namespace Kitmap\handler;

use Kitmap\entity\LogoutNpc;
use Kitmap\Main;
use Kitmap\Util;
use pocketmine\player\Player;
use pocketmine\utils\SingletonTrait;
use Symfony\Component\Filesystem\Path;
use WeakMap;

class Cache
{
    use SingletonTrait;

    public static array $oldconfig;
    public static array $players;
    public static array $data;
    public static array $config;
    public static array $market;
    public static array $bans;
    public static array $claims;
    public static array $factions;

    /* @var array<string, LogoutNpc> */
    public static array $logouts;

    /* @var WeakMap<Player, boolean> */
    public static WeakMap $scoreboardPlayers;
    /* @var WeakMap<Player, boolean> */
    public static WeakMap $borderPlayers;
    /* @var WeakMap<Player, boolean> */
    public static WeakMap $combatPlayers;

    public function __construct()
    {
        $this->setInstance($this);

        self::$scoreboardPlayers ??= new WeakMap();
        self::$borderPlayers ??= new WeakMap();
        self::$combatPlayers ??= new WeakMap();

        @mkdir(Main::getInstance()->getDataFolder() . "data/");
        @mkdir(Main::getInstance()->getDataFolder() . "data/players");
        @mkdir(Main::getInstance()->getDataFolder() . "data/inventories/");

        self::$config = $this->makeConfig();

        self::$data = Util::getFile("data/data")->getAll();
        self::$market = Util::getFile("data/market")->getAll();
        self::$bans = Util::getFile("data/bans")->getAll();
        self::$claims = Util::getFile("data/claims")->getAll();
        self::$factions = Util::getFile("data/factions")->getAll();

        Cache::$config["enderchest"] = [];

        foreach (Cache::$config["pack"] as $name => $arr) {
            [$x, $y, $z] = explode(":", $arr["enderchest"]);

            Cache::$config["enderchest"][$arr["enderchest"]] = $name;
            Cache::$config["pos"]["floating"][(intval($x) + 0.5) . ":" . (intval($y) + 1) . ":" . (intval($z) + 0.5) . ":map"] = "#" . Util::PREFIX . "Pack " . $name . " §q§l«";
        }

        foreach (Util::listAllFiles(Main::getInstance()->getDataFolder() . "data/players") as $file) {
            $path = pathinfo($file);
            $username = $path["filename"];

            $file = Util::getFile("data/players/" . $username);

            self::$players["money"][$username] = $file->get("money", 0);
            self::$players["kill"][$username] = $file->get("kill", 0);
            self::$players["death"][$username] = $file->get("death", 0);
            self::$players["bounty"][$username] = $file->get("bounty", 0);
            self::$players["killstreak"][$username] = $file->get("killstreak", 0);
            self::$players["played_time"][$username] = $file->get("played_time", 0);
            self::$players["upper_name"][strtolower($username)] = $file->get("upper_name", $username);

            foreach (Cache::$config["save-data"] as $column) {
                self::$players[$column][$username] = $file->get($column, []);
            }
        }
    }

    private function makeConfig(): array
    {
        $config = [];

        foreach (Util::listAllFiles(Path::join(Main::getInstance()->getFile(), "resources", "config")) as $file) {
            $data = pathinfo($file);

            $dirs = explode(DIRECTORY_SEPARATOR, $data["dirname"]);
            $elements = array_slice($dirs, -2);

            $parent = $elements[1];
            $name = $data["filename"];

            $data = file_get_contents($file);
            $json = json_decode($data, true);

            switch ($parent) {
                case "util":
                case "config":
                case "interface":
                    if ($name === "main" || $name === "var") {
                        $config = array_merge_recursive($config, $json);
                    } else {
                        $config[$name] = $json;
                    }
                    break;
                default:
                    $config[$parent][$name] = $json;
            }
        }

        return $config;
    }

    public function saveAll(): void
    {
        $this->save(self::$data, "data");
        $this->save(self::$market, "market");
        $this->save(self::$bans, "bans");
        $this->save(self::$claims, "claims");
        $this->save(self::$factions, "factions");
    }

    private function save(array $array, string $file): void
    {
        $file = Util::getFile("data/" . $file);

        $file->setAll($array);
        $file->save();
    }
}