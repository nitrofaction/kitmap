<?php /** @noinspection PhpUnused */

namespace Kitmap\command\staff\op;

use CortexPE\Commando\args\OptionArgument;
use CortexPE\Commando\BaseCommand;
use Kitmap\entity\animation\Pack;
use Kitmap\entity\floating\DefaultFloatingText;
use Kitmap\entity\floating\DynamicFloatingText;
use Kitmap\entity\floating\LeaderboardFloatingText;
use Kitmap\entity\npc\BlackSmith;
use Kitmap\entity\npc\ElevatorPhantom;
use Kitmap\entity\npc\CommandNpc;
use Kitmap\entity\npc\TopNpc;
use Kitmap\handler\Cache;
use Kitmap\handler\Cosmetic;
use Kitmap\Main;
use Kitmap\Util;
use pocketmine\command\CommandSender;
use pocketmine\entity\Location;
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

                foreach (Cache::$config["pos"]["top"] as $data => $_) {
                    [$x, $y, $z, $yaw] = explode(":", $data);

                    $entity = new TopNpc(
                        new Location(floatval($x), floatval($y), floatval($z), Main::getInstance()->getServer()->getWorldManager()->getDefaultWorld(), intval($yaw), 0),
                        Cosmetic::getSkinFromName("", "steve"),
                    );

                    $entity->spawnToAll();
                }

                foreach (Cache::$config["npc"] as $identifier => $data) {
                    [$x, $y, $z, $yaw, , , $skin] = explode(":", $data);

                    $entity = new CommandNpc(
                        new Location(floatval($x), floatval($y), floatval($z), Main::getInstance()->getServer()->getWorldManager()->getDefaultWorld(), intval($yaw), 0),
                        Cosmetic::getSkinFromName("skins", $skin),
                        CompoundTag::create()->setString("npc", $identifier)
                    );

                    $entity->spawnToAll();
                }

                foreach (Cache::$config["pos"]["elevator-npc"] as $elevator) {
                    [$x, $y, $z, $yaw] = explode(":", $elevator);

                    $entity = new ElevatorPhantom(new Location(floatval($x), floatval($y), floatval($z), Main::getInstance()->getServer()->getWorldManager()->getWorldByName("mine"), intval($yaw), 0));
                    $entity->spawnToAll();
                }

                [$x, $y, $z, $yaw] = explode(":", Cache::$data["forgeron-position"] ?? Cache::$config["pos"]["forgeron"][0]);

                $entity = new BlackSmith(new Location(floatval($x), floatval($y), floatval($z), Main::getInstance()->getServer()->getWorldManager()->getDefaultWorld(), intval($yaw), 0));
                $entity->spawnToAll();

                [$x, $y, $z, $yaw] = explode(":", Cache::$config["pack"]["pos"]);

                $pos = new Position(intval($x), intval($y), intval($z), Main::getInstance()->getServer()->getWorldManager()->getDefaultWorld());

                $entity = new Pack(
                    Location::fromObject($pos->add(0.5, 0, 0.5), $pos->getWorld(), intval($yaw)),
                    CompoundTag::create()->setString("id", "nitro:pack")
                );

                $entity->spawnToAll();

                $sender->sendMessage(Util::PREFIX . "Vous venez de faire apparaitre les floatings texts");
                break;
            case "despawn":
                foreach (Main::getInstance()->getServer()->getWorldManager()->getWorlds() as $world) {
                    foreach ($world->getEntities() as $entity) {
                        if (
                            $entity instanceof DefaultFloatingText ||
                            $entity instanceof DynamicFloatingText ||
                            $entity instanceof ElevatorPhantom ||
                            $entity instanceof BlackSmith ||
                            $entity instanceof Pack ||
                            $entity instanceof CommandNpc
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