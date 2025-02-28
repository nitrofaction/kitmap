<?php

namespace Kitmap\block\generator;

use pocketmine\block\VanillaBlocks;
use pocketmine\data\bedrock\BiomeIds;
use pocketmine\world\ChunkManager;
use pocketmine\world\generator\Generator;

abstract class IslandGenerator extends Generator
{
    private array $config;

    public function __construct(int $seed, string $preset)
    {
        parent::__construct($seed, $preset);
        $this->config = json_decode($preset, true, flags: JSON_THROW_ON_ERROR);
    }

    public function generateChunk(ChunkManager $world, int $chunkX, int $chunkZ): void
    {
        if ($chunkX == 16 && $chunkZ == 16) {
            $this->generateChunkBlocks($world, $chunkX, $chunkZ);
        }
    }

    private function generateChunkBlocks(ChunkManager $world, int $chunkX, $chunkZ): void
    {
        foreach ($this->config as $data) {
            [$x, $y, $z, $blockType] = explode(":", $data);
            $world->setBlockAt(intval($x), intval($y), intval($z), VanillaBlocks::$blockType());
            $world->getChunk($chunkX, $chunkZ)->setBiomeId(intval($x), intval($y), intval($z), BiomeIds::BAMBOO_JUNGLE);
        }
    }

    public function populateChunk(ChunkManager $world, int $chunkX, int $chunkZ): void
    {
    }
}