<?php

namespace Kitmap\block;

use Kitmap\block\tile\SpawnerTile;
use Kitmap\handler\Cache;
use Kitmap\item\ExtraVanillaItems;
use Kitmap\Main;
use pocketmine\block\BlockIdentifier;
use pocketmine\block\BlockTypeInfo;
use pocketmine\block\MonsterSpawner as PmMonsterSpawner;
use pocketmine\block\VanillaBlocks;
use pocketmine\event\block\BlockBreakEvent;
use pocketmine\event\block\BlockPlaceEvent;
use pocketmine\inventory\CreativeInventory;
use pocketmine\item\enchantment\VanillaEnchantments;
use pocketmine\item\Item as PmItem;
use pocketmine\item\StringToItemParser;
use pocketmine\scheduler\ClosureTask;

class MonsterSpawner extends Block
{
    public static function override(): PmMonsterSpawner
    {
        $i = 0;

        $block = new PmMonsterSpawner(
            new BlockIdentifier(VanillaBlocks::MONSTER_SPAWNER()->getIdInfo()->getBlockTypeId(), SpawnerTile::class),
            "Monster Spawner",
            new BlockTypeInfo(VanillaBlocks::MONSTER_SPAWNER()->getBreakInfo())
        );

        CreativeInventory::getInstance()->remove(VanillaBlocks::MONSTER_SPAWNER()->asItem());

        foreach (Cache::$config["entities"] as $entity) {
            $name = "§r§fGénérateur de " . ucfirst($entity["french"]);
            $item = $block->asItem()->setCustomName($name);

            ExtraVanillaItems::registerItem(
                "monster_spawner_" . str_replace("", "_", strtolower($entity["name"])),
                $item,
                $name,
                $i === 0
            );

            $i++;
        }

        return $block;
    }

    public function onBreak(BlockBreakEvent $event): bool
    {
        $item = $event->getItem();
        $block = $event->getBlock();

        if ($item->hasEnchantment(VanillaEnchantments::SILK_TOUCH())) {
            $tile = $block->getPosition()->getWorld()->getTile($block->getPosition());

            if ($tile instanceof SpawnerTile && $tile->getEntityId() !== ":") {
                $event->setXpDropAmount(0);

                $event->setDrops([
                    StringToItemParser::getInstance()->parse("monster_spawner_" . str_replace("minecraft:", "", $tile->getEntityId()))
                ]);
            }
        }

        return parent::onBreak($event);
    }

    public function onPlace(BlockPlaceEvent $event): bool
    {
        $player = $event->getPlayer();

        foreach ($event->getTransaction()->getBlocks() as [$x, $y, $z, $block]) {
            $item = clone $event->getItem();
            $event->getTransaction()->addBlock($block->getPosition(), ExtraVanillaBlocks::MONSTER_SPAWNER());

            Main::getInstance()->getScheduler()->scheduleDelayedTask(new ClosureTask(
                function () use ($player, $item, $block) {
                    $tile = $player->getWorld()->getTile($block->getPosition());

                    if ($block instanceof PmMonsterSpawner && $tile instanceof SpawnerTile) {
                        $entityId = $this->getSpawnEntityIdByItem($item);

                        if (!is_null($entityId)) {
                            $tile->setEntityId($entityId);
                        }
                    }
                }
            ), 1);
        }

        return false;
    }

    public function getSpawnEntityIdByItem(PmItem $item): ?string
    {
        foreach (Cache::$config["entities"] as $entityId => $data) {
            if (str_contains(strtolower($item->getCustomName()), strtolower($data["french"]))) {
                return $entityId;
            }
        }
        return null;
    }
}