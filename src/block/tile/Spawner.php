<?php

namespace Kitmap\block\tile;

use pocketmine\block\tile\Spawnable;
use pocketmine\nbt\tag\CompoundTag;

abstract class Spawner extends Spawnable
{
    protected const TAG_ENTITY_TYPE_ID = "EntityIdentifier"; //TAG_String
    protected const TAG_SPAWN_DELAY = "Delay"; //TAG_Short
    protected const TAG_MIN_SPAWN_DELAY = "MinSpawnDelay"; //TAG_Short
    protected const TAG_MAX_SPAWN_DELAY = "MaxSpawnDelay"; //TAG_Short
    protected const TAG_REQUIRED_PLAYER_RANGE = "RequiredPlayerRange"; //TAG_Short
    protected const TAG_SPAWN_RANGE = "SpawnRange"; //TAG_Short
    protected const TAG_SPAWN_ATTEMPTS = "SpawnAttempts";

    public const DEFAULT_MIN_SPAWN_DELAY = 200; //ticks
    public const DEFAULT_MAX_SPAWN_DELAY = 800;

    public const DEFAULT_SPAWN_RANGE = 4; //blocks
    public const DEFAULT_REQUIRED_PLAYER_RANGE = 16;

    protected const DEFAULT_SPAWN_ATTEMPTS = 5;

    protected string $entityTypeId = ":";

    protected int $spawnDelay = self::DEFAULT_MIN_SPAWN_DELAY;
    protected int $minSpawnDelay = self::DEFAULT_MIN_SPAWN_DELAY;
    protected int $maxSpawnDelay = self::DEFAULT_MAX_SPAWN_DELAY;
    protected int $spawnRange = self::DEFAULT_SPAWN_RANGE;
    protected int $requiredPlayerRange = self::DEFAULT_REQUIRED_PLAYER_RANGE;
    protected int $spawnAttempts = self::DEFAULT_SPAWN_ATTEMPTS;

    public function readSaveData(CompoundTag $nbt): void
    {
        $this->entityTypeId = $nbt->getString(self::TAG_ENTITY_TYPE_ID, ":");
        $this->spawnDelay = $nbt->getShort(self::TAG_SPAWN_DELAY, self::DEFAULT_MIN_SPAWN_DELAY);
        $this->minSpawnDelay = $nbt->getShort(self::TAG_MIN_SPAWN_DELAY, self::DEFAULT_MIN_SPAWN_DELAY);
        $this->maxSpawnDelay = $nbt->getShort(self::TAG_MAX_SPAWN_DELAY, self::DEFAULT_MAX_SPAWN_DELAY);
        $this->spawnRange = $nbt->getShort(self::TAG_SPAWN_RANGE, self::DEFAULT_SPAWN_RANGE);
        $this->spawnAttempts = $nbt->getShort(self::TAG_SPAWN_ATTEMPTS, self::DEFAULT_SPAWN_ATTEMPTS);
        $this->requiredPlayerRange = $nbt->getShort(self::TAG_REQUIRED_PLAYER_RANGE, self::DEFAULT_REQUIRED_PLAYER_RANGE);
    }

    abstract public function canUpdate(): bool;

    abstract public function onUpdate(): bool;

    public function setRandomSpawnDelay(): void
    {
        $this->spawnDelay = mt_rand($this->minSpawnDelay, $this->maxSpawnDelay);
    }

    protected function writeSaveData(CompoundTag $nbt): void
    {
        $nbt->setString(self::TAG_ENTITY_TYPE_ID, $this->entityTypeId);
        $nbt->setShort(self::TAG_SPAWN_DELAY, $this->spawnDelay);
        $nbt->setShort(self::TAG_MIN_SPAWN_DELAY, $this->minSpawnDelay);
        $nbt->setShort(self::TAG_MAX_SPAWN_DELAY, $this->maxSpawnDelay);
        $nbt->setShort(self::TAG_SPAWN_RANGE, $this->spawnRange);
        $nbt->setShort(self::TAG_SPAWN_ATTEMPTS, $this->spawnAttempts);
        $nbt->setShort(self::TAG_REQUIRED_PLAYER_RANGE, $this->requiredPlayerRange);
    }

    protected function addAdditionalSpawnData(CompoundTag $nbt): void
    {
        $nbt->setString(self::TAG_ENTITY_TYPE_ID, $this->entityTypeId);
    }
}