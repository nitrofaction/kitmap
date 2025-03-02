<?php

namespace Kitmap\task;

use Kitmap\entity\animation\DefaultFloatingText;
use Kitmap\entity\animation\PackItem as Entity;
use Kitmap\handler\Pack as Api;
use Kitmap\Main;
use Kitmap\Session;
use Kitmap\Util;
use pocketmine\block\Block;
use pocketmine\color\Color;
use pocketmine\entity\Location;
use pocketmine\math\Vector3;
use pocketmine\player\Player;
use pocketmine\scheduler\Task;
use pocketmine\world\particle\DustParticle;
use pocketmine\world\Position;
use pocketmine\world\sound\BlazeShootSound;
use pocketmine\world\sound\ClickSound;
use pocketmine\world\sound\XpCollectSound;

class PackAnimationTask extends Task
{
    private int $ticks = 200;
    private int $waitCancelTicks = -1;

    /** @var ?Entity[] */
    private array $lastEntities = [];

    public function __construct(
        private readonly Player $player,
        private readonly Block  $block
    )
    {
    }

    public function onRun(): void
    {
        $this->ticks--;
        $this->waitCancelTicks--;

        $spawnOffsets = [
            [1.5, 1.5, 1.5],
            [-0.5, 1.5, 1.5],
            [0.5, 1.5, -0.2],

            [0.5, 2.5, -0.2],
            [1.5, 2.5, 1.5],
        ];

        if ($this->waitCancelTicks === 1 || !$this->player->isConnected()) {
            foreach ($this->lastEntities as $lastEntity) {
                if ($lastEntity instanceof Entity) {
                    $lastEntity->close();
                }
            }

            $this->getHandler()->cancel();

            if ($this->player->isConnected()) {
                $this->updateFloating(true);
            }

            return;
        } else if ($this->waitCancelTicks > 1) {
            return;
        }

        $i = (200 - $this->ticks);
        $freq = min(20, intval(1 + ($i - 1) / 10));

        $position = $this->block->getPosition();
        $items = $this->getItems();

        Session::get($this->player)->setCooldown("pack", 5);

        if ($i % max(1, $freq) == 0) {
            $this->spawnCircleParticles($position, $i);

            $this->updateFloating(false);
            $position->getWorld()->addSound($position->add(0.5, 0.5, 0.5), new ClickSound(), [$this->player]);

            foreach ($this->lastEntities as $lastEntity) {
                if ($lastEntity instanceof Entity) {
                    $lastEntity->close();
                }
            }

            foreach ($items as $index => $item) {
                $offset = $spawnOffsets[$index] ?? [0, 1, 0];

                $entity = new Entity(Location::fromObject($position->add($offset[0], $offset[1], $offset[2]), $position->getWorld()), $item["item"]);
                $entity->setNameTag($item["name"]);
                $entity->spawnTo($this->player);

                $entity->setGravity(0.0);
                $entity->setHasGravity(false);

                $this->lastEntities[] = $entity;
            }
        }

        if ($this->ticks === 0) {
            $this->spawnCircleParticles($position, $i);

           $this->updateFloating(false);
            $this->waitCancelTicks = 90;

            $position->getWorld()->addSound($position->add(0.5, 0.5, 0.5), new BlazeShootSound(), [$this->player]);
            $position->getWorld()->addSound($position->add(0.5, 0.5, 0.5), new XpCollectSound(), [$this->player]);

            foreach ($this->lastEntities as $lastEntity) {
                if ($lastEntity instanceof Entity) {
                    $lastEntity->close();
                }
            }

            $randomItems = Api::chooseRandomItems(Api::$itemsAmount);
            $prizeList = [];

            $this->player->sendMessage(Util::PREFIX . "Grace à votre §qPACK §fvous venez de gagner:");

            foreach ($randomItems as $index => $randomItem) {
                $offset = $spawnOffsets[$index] ?? [0, 1, 0];

                $entity = new Entity(Location::fromObject($position->add($offset[0], $offset[1], $offset[2]), $this->player->getWorld()), $randomItem["item"]);
                $entity->setNameTag(Util::PREFIX . $randomItem["name"] . Util::IARROW);
                $entity->spawnTo($this->player);

                $entity->setGravity(0.0);
                $entity->setHasGravity(false);

                $this->lastEntities[] = $entity;
                $this->player->sendMessage(Util::PREFIX . $randomItem["name"]);

                Util::addItem($this->player, $randomItem["item"]);
                $prizeList[] = $randomItem["name"];
            }

            Main::getInstance()->getLogger()->info("Le joueur " . $this->player->getName() . " vient d'ouvrir un pack (ses lots: " . implode(", ", $prizeList) . ")");
            Main::getInstance()->getServer()->broadcastTip(Util::PREFIX . "Le joueur §q" . $this->player->getName() . " §fvient d'ouvrir un §qPACK §f!");

            $this->player->sendMessage(Util::PREFIX . "Vos lots ont été mis dans votre inventaire");
        }
    }

    private function spawnCircleParticles(Position $position, int $tick): void
    {
        $world = $position->getWorld();

        $radius = 2.5;
        $height = 0.5;
        $particleCount = 15;

        $angleOffset = $tick * 0.1;

        for ($i = 0; $i < $particleCount; $i++) {
            $angle = (2 * M_PI * $i / $particleCount) + $angleOffset;
            $x = $position->x + 0.5 + $radius * cos($angle);
            $z = $position->z + 0.5 + $radius * sin($angle);
            $y = $position->y + $height;

            if ($i % 2 === 0) {
                $particle = new DustParticle(new Color(0, 0, 0));
            } else {
                $particle = new DustParticle(new Color(34, 139, 34));
            }

            $world->addParticle(new Vector3($x, $y, $z), $particle, [$this->player]);
        }
    }

    public function updateFloating(bool $spawn): void
    {
        $pos = $this->block->getPosition();
        $entity = Main::getInstance()->getServer()->getWorldManager()->getDefaultWorld()->getNearestEntity($pos, 2, DefaultFloatingText::class);

        if ($entity instanceof DefaultFloatingText && $this->player->isConnected()) {
            if ($spawn) {
                $entity->spawnTo($this->player);
            } else {
                /** @noinspection PhpDeprecationInspection */
                $entity->despawnFrom($this->player);
            }
        }
    }

    public function getItems(): array
    {
        $items = Api::getItems();

        if (empty($items)) {
            return [];
        }

        $amount = min(Api::$itemsAmount, count($items));
        $randomKeys = array_rand($items, $amount);

        return array_map(fn($key) => [
            "name" => Util::PREFIX . ucfirst($key) . Util::IARROW,
            "item" => $items[$key]
        ], (array)$randomKeys);
    }
}