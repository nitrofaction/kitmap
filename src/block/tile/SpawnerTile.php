<?php

namespace Kitmap\block\tile;

use Kitmap\entity\SpawnerEntity;
use Kitmap\Main;
use pocketmine\entity\Location;
use pocketmine\math\Vector3;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\player\Player;
use pocketmine\scheduler\ClosureTask;
use pocketmine\scheduler\TaskHandler;
use pocketmine\world\format\Chunk;
use pocketmine\world\particle\MobSpawnParticle;
use pocketmine\world\Position;
use pocketmine\world\World;

final class SpawnerTile extends Spawner
{
    protected int $stackRange = 0;

    private ?TaskHandler $handler;

    public function __construct(World $world, Vector3 $pos)
    {
        parent::__construct($world, $pos);

        $this->spawnRange = 5;
        $this->stackRange = 5;

        $tile = $this;

        $this->handler = Main::getInstance()->getScheduler()->scheduleRepeatingTask(new ClosureTask(
            function () use ($tile) {
                if ($tile->canUpdate()) {
                    $tile->onUpdate();
                }
            }
        ), 1);
    }

    public function canUpdate(): bool
    {
        return $this->entityTypeId !== ":" && $this->getPosition()->getWorld()->getNearestEntity($this->getPosition(), $this->requiredPlayerRange, Player::class) !== null;
    }

    public function onUpdate(): bool
    {
        if ($this->closed) {
            $this->handler->cancel();
            return false;
        }

        $this->timings->startTiming();

        if ($this->canUpdate()) {
            if ($this->spawnDelay <= 0) {
                $this->spawnEntity();
                $this->setRandomSpawnDelay();
            } else {
                $this->spawnDelay--;
            }
        }

        $this->timings->stopTiming();
        return true;
    }

    protected function spawnEntity(): void
    {
        $entity = $this->getNearestSameEntity();

        if ($entity instanceof SpawnerEntity) {
            $entity->addStackSize(1);
            return;
        }

        $world = ($position = $this->getPosition())->getWorld();

        for ($attempts = 0; $attempts < $this->spawnAttempts; $attempts++) {
            $pos = $position->add(mt_rand(-$this->spawnRange, $this->spawnRange), mt_rand(-1, 1), mt_rand(-$this->spawnRange, $this->spawnRange));

            if (
                $world->getBlock($pos)->isSolid() and
                !$world->getBlock($pos)->canBeFlowedInto() or
                !$world->getBlock($pos->subtract(0, 1, 0))->isSolid()
            ) {
                continue;
            }

            $entity = new SpawnerEntity(
                Location::fromObject($pos, $world),
                CompoundTag::create()->setString("id", $this->getEntityId())
            );

            $entity->spawnToAll();

            $world->addParticle($pos, new MobSpawnParticle(round($entity->getSize()->getWidth()), round($entity->getSize()->getHeight())));
            break;
        }
    }

    protected function getNearestSameEntity(?Position $pos = null): ?SpawnerEntity
    {
        if ($pos === null) {
            $pos = $this->getPosition();
        }

        $world = $pos->getWorld();

        $minX = floor($pos->x - $this->stackRange) >> Chunk::COORD_BIT_SIZE;
        $maxX = floor($pos->x + $this->stackRange) >> Chunk::COORD_BIT_SIZE;
        $minZ = floor($pos->z - $this->stackRange) >> Chunk::COORD_BIT_SIZE;
        $maxZ = floor($pos->z + $this->stackRange) >> Chunk::COORD_BIT_SIZE;

        for ($x = $minX; $x <= $maxX; $x++) {
            for ($z = $minZ; $z <= $maxZ; $z++) {
                if (!$world->isChunkLoaded($x, $z)) {
                    continue;
                }

                foreach ($world->getChunkEntities($x, $z) as $entity) {
                    if (
                        !$entity instanceof SpawnerEntity ||
                        !$entity->isAlive() ||
                        $entity->isFlaggedForDespawn()
                    ) {
                        continue;
                    }

                    $maxY = floor($pos->y - $entity->getPosition()->y);

                    if (
                        $this->getEntityId() !== $entity->getCustomNetworkTypeId() ||
                        $this->stackRange < $maxY
                    ) {
                        continue;
                    }

                    return $entity;
                }
            }
        }

        return null;
    }

    public function getEntityId(): string
    {
        return $this->entityTypeId;
    }

    public function setEntityId(string $id): void
    {
        $this->entityTypeId = $id;
    }
}