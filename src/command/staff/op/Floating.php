<?php /** @noinspection PhpUnused */

namespace Kitmap\command\staff\op;

use CortexPE\Commando\args\OptionArgument;
use CortexPE\Commando\BaseCommand;
use Kitmap\entity\animation\Pack;
use Kitmap\entity\Entities;
use Kitmap\entity\floating\DynamicFloatingText;
use Kitmap\entity\floating\FloatingText;
use Kitmap\entity\floating\LeaderboardFloatingText;
use Kitmap\entity\npc\BlackSmith;
use Kitmap\entity\npc\CmdEntity;
use Kitmap\entity\npc\Merchant;
use Kitmap\entity\npc\Quest;
use Kitmap\entity\npc\TopEntity;
use Kitmap\handler\Cache;
use Kitmap\handler\Cosmetic;
use Kitmap\Main;
use Kitmap\Util;
use pocketmine\command\CommandSender;
use pocketmine\entity\Location;
use pocketmine\entity\Villager;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\permission\DefaultPermissions;
use pocketmine\plugin\PluginBase;
use pocketmine\world\Position;

class Floating extends BaseCommand
{
    public function __construct(PluginBase $plugin)
    {
        parent::__construct(
            $plugin,
            "floating",
            "Fait disparaitre ou apparaitre les floatings texts"
        );

        $this->setPermissions([DefaultPermissions::ROOT_OPERATOR]);
    }

    public function onRun(CommandSender $sender, string $aliasUsed, array $args): void
    {
        switch ($args["opt"]) {
            case "spawn":
                foreach (Cache::$config["pos"]["floating"] as $key => $value) {
                    [$x, $y, $z, $world] = explode(":", $key);

                    if ($value === "leaderboard") {
                        $entity = LeaderboardFloatingText::class;
                    } else {
                        $entity = DynamicFloatingText::class;
                    }

                    $entity = new $entity(new Location(floatval($x), floatval($y), floatval($z), Main::getInstance()->getServer()->getWorldManager()->getWorldByName($world), 0, 0));
                    $entity->spawnToAll();
                }

                foreach (Cache::$config["pos"]["quest"] as $value) {
                    [$x, $y, $z, $yaw, $world] = explode(":", $value);

                    $entity = new Quest(new Location(floatval($x), floatval($y), floatval($z), Main::getInstance()->getServer()->getWorldManager()->getWorldByName($world), intval($yaw), 0));
                    $entity->setProfession(Villager::PROFESSION_BLACKSMITH);
                    $entity->spawnToAll();
                }

                foreach (Cache::$config["pos"]["merchant"] as $value) {
                    [$x, $y, $z, $yaw, $world] = explode(":", $value);

                    $entity = new Merchant(new Location(floatval($x), floatval($y), floatval($z), Main::getInstance()->getServer()->getWorldManager()->getWorldByName($world), intval($yaw), 0));
                    $entity->setProfession(Villager::PROFESSION_BUTCHER);
                    $entity->spawnToAll();
                }

                foreach (Cache::$config["pos"]["top"] as $data => $_) {
                    [$x, $y, $z, $yaw] = explode(":", $data);

                    $entity = new TopEntity(
                        new Location(floatval($x), floatval($y) + 1, floatval($z), Main::getInstance()->getServer()->getWorldManager()->getDefaultWorld(), intval($yaw), 0),
                        Cosmetic::getSkinFromName("", "steve"),
                    );

                    $entity->spawnToAll();
                }

                foreach (Cache::$config["npc"] as $identifier => $values) {
                    foreach ($values as $data) {
                        [$x, $y, $z, $yaw, , , $skin] = explode(":", $data);

                        $entity = new CmdEntity(
                            new Location(floatval($x), floatval($y), floatval($z), Main::getInstance()->getServer()->getWorldManager()->getDefaultWorld(), intval($yaw), 0),
                            Cosmetic::getSkinFromName("skins", $skin),
                            CompoundTag::create()->setString(Entities::NPC_TAG, $identifier)
                        );

                        $entity->spawnToAll();
                    }
                }

                [$x, $y, $z, $yaw] = explode(":", Cache::$data["forgeron-position"] ?? Cache::$config["pos"]["forgeron"][0]);

                $entity = new BlackSmith(new Location(floatval($x), floatval($y), floatval($z), Main::getInstance()->getServer()->getWorldManager()->getDefaultWorld(), intval($yaw), 0));
                $entity->spawnToAll();

                [$x, $y, $z, $yaw] = explode(":", Cache::$config["pack"]["pos"]);

                $pos = new Position(intval($x), intval($y), intval($z), Main::getInstance()->getServer()->getWorldManager()->getDefaultWorld());

                $entity = new Pack(
                    Location::fromObject($pos->add(0.5, 0, 0.5), $pos->getWorld(), intval($yaw)),
                    Cosmetic::getSkinFromName("skins", "pack")
                );

                $entity->spawnToAll();

                $sender->sendMessage(Util::PREFIX . "Vous venez de faire apparaitre les floatings texts");
                break;
            case "despawn":
                foreach (Main::getInstance()->getServer()->getWorldManager()->getWorlds() as $world) {
                    foreach ($world->getEntities() as $entity) {
                        if (
                            $entity instanceof Pack ||
                            $entity instanceof FloatingText ||
                            $entity instanceof BlackSmith ||
                            $entity instanceof CmdEntity ||
                            $entity instanceof TopEntity ||
                            $entity instanceof Quest ||
                            $entity instanceof Merchant
                        ) {
                            $entity->flagForDespawn();
                        }
                    }
                }

                $sender->sendMessage(Util::PREFIX . "Vous venez de supprimer les floatings texts");
                break;
        }
    }

    protected function prepare(): void
    {
        $this->registerArgument(0, new OptionArgument("opt", ["spawn", "despawn"]));
    }
}