<?php

namespace Kitmap\handler;

use Kitmap\entity\npc\LogoutEntity;
use Kitmap\entity\npc\SubQuest;
use Kitmap\Main;
use Kitmap\Util;
use pocketmine\crafting\ExactRecipeIngredient;
use pocketmine\crafting\MetaWildcardRecipeIngredient;
use pocketmine\item\StringToItemParser;
use pocketmine\player\Player;
use pocketmine\utils\SingletonTrait;
use Symfony\Component\Filesystem\Path;
use WeakMap;

class Cache
{
    use SingletonTrait;

    public static array $players;
    public static array $data;
    public static array $config;
    public static array $market;
    public static array $bans;
    public static array $claims;
    public static array $factions;

    public static array $deathXp = [];
    public static array $condenseShapes = [];

    /* @var array<string, LogoutEntity> */
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
        @mkdir(Main::getInstance()->getDataFolder() . "data/skins/");

        $this->condenseShapes();
        $this->makeConfig();

        self::$data = Util::getFile("data/data")->getAll();
        self::$market = Util::getFile("data/market")->getAll();
        self::$bans = Util::getFile("data/bans")->getAll();
        self::$claims = Util::getFile("data/claims")->getAll();
        self::$factions = Util::getFile("data/factions")->getAll();

        foreach (Util::listAllFiles(Path::join(Main::getInstance()->getFile(), "resources", "cosmetic")) as $file) {
            $data = pathinfo($file);

            $dirs = explode(DIRECTORY_SEPARATOR, $data["dirname"]);
            $elements = array_slice($dirs, -2);

            $name = $elements[1];
            $type = $elements[0];

            switch ($data["extension"]) {
                case "json":
                    Cosmetic::$skins[$type][$name]["geometry"] = file_get_contents($file);
                    break;
                case "png":
                    Cosmetic::$skins[$type][$name]["texture"] = Cosmetic::getBytesFromImage($file);
                    break;
            }
        }

        foreach (Util::listAllFiles(Main::getInstance()->getDataFolder() . "data/players") as $file) {
            $path = pathinfo($file);
            $username = $path["filename"];

            $file = Util::getFile("data/players/" . $username);

            self::$players["money"][$username] = $file->get("money", 0);
            self::$players["kill"][$username] = $file->get("kill", 0);
            self::$players["death"][$username] = $file->get("death", 0);
            self::$players["elo"][$username] = $file->get("elo", 0);
            self::$players["bounty"][$username] = $file->get("bounty", 0);
            self::$players["killstreak"][$username] = $file->get("killstreak", 0);
            self::$players["played_time"][$username] = $file->get("played_time", 0);
            self::$players["upper_name"][strtolower($username)] = $file->get("upper_name", $username);

            foreach (Cache::$config["save-data"] as $column) {
                self::$players[$column][$username] = $file->get($column, []);
            }
        }
    }

    private function condenseShapes(): void
    {
        $craftMgr = Main::getInstance()->getServer()->getCraftingManager();

        $array1 = array_fill(0, 3, "AAA");
        $array2 = array_fill(0, 2, "AA");

        foreach ($craftMgr->getShapedRecipes() as $recipes) {
            foreach ($recipes as $recipe) {
                if ($recipe->getShape() === $array1 || $recipe->getShape() === $array2) {
                    $output = $recipe->getResults()[0];
                    $input = null;

                    $ingredients = $recipe->getIngredientList();
                    $ingredient = $ingredients[0];

                    if ($ingredient instanceof MetaWildcardRecipeIngredient) {
                        $input = StringToItemParser::getInstance()->parse($ingredient->getItemId());

                        if (is_null($input)) {
                            continue;
                        }
                    } else if ($ingredient instanceof ExactRecipeIngredient) {
                        $input = $ingredient->getItem();
                    }

                    if (is_null($input)) {
                        continue;
                    }

                    self::$condenseShapes[] = [
                        "input" => $input,
                        "output" => $output,
                        "count" => count($ingredients)
                    ];
                }
            }
        }
    }

    private function makeConfig(): void
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

        self::$config = $config;
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