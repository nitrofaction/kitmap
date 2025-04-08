<?php

namespace Kitmap\block;

use Kitmap\block\tile\SpawnerTile;
use pocketmine\block\Block as PmBlock;
use pocketmine\block\MonsterSpawner as PmMonsterSpawner;
use pocketmine\block\tile\TileFactory;
use pocketmine\block\VanillaBlocks;
use pocketmine\utils\CloningRegistryTrait;

/**
 *
 * @method static PmMonsterSpawner MONSTER_SPAWNER()
 *
 */
class ExtraVanillaBlocks
{
    use CloningRegistryTrait;

    private static array $blocks = [];

    public function __construct()
    {
        self::addBlock(VanillaBlocks::ANVIL(), new Anvil());
        self::addBlock(VanillaBlocks::COCOA_POD(), new CocoaPod());
        self::addBlock(VanillaBlocks::DEEPSLATE_EMERALD_ORE(), new EmeraldOre());
        self::addBlock(VanillaBlocks::EMERALD_ORE(), new EmeraldOre());
        self::addBlock(VanillaBlocks::ENCHANTING_TABLE(), new EnchantingTable());
        self::addBlock(VanillaBlocks::TRAPPED_CHEST(), new FarmingChest());
        self::addBlock(VanillaBlocks::NETHER_WART(), new NetherWartPlant());
        self::addBlock(VanillaBlocks::SMOKER(), new ChunkBuster());
        self::addBlock(VanillaBlocks::STONE(), new Stone());
        self::addBlock(VanillaBlocks::COBBLESTONE(), new Stone());
        self::addBlock(VanillaBlocks::LAPIS_LAZULI(), new Elevator());
        self::addBlock(ExtraVanillaBlocks::MONSTER_SPAWNER(), new MonsterSpawner());
        self::addBlock(VanillaBlocks::MELON_STEM(), new MelonStem());
        self::addBlock(VanillaBlocks::BAMBOO(), new Bamboo());
        self::addBlock(VanillaBlocks::SUGARCANE(), new Sugarcane());
        self::addBlock(VanillaBlocks::LAVA_CAULDRON(), new Cauldron());
        self::addBlock(VanillaBlocks::WATER_CAULDRON(), new Cauldron());
        self::addBlock(VanillaBlocks::POTION_CAULDRON(), new Cauldron());
        self::addBlock(VanillaBlocks::CAULDRON(), new Cauldron());

        TileFactory::getInstance()->register(SpawnerTile::class, ["MobSpawner", "minecraft:mob_spawner"]);

        new World();
    }

    public static function addBlock(PmBlock $block, Block $replace): void
    {
        self::$blocks[$block->getTypeId()] = $replace;
    }

    public static function getBlock(PmBlock $block): Block
    {
        return self::$blocks[$block->getTypeId()] ?? new Block();
    }

    protected static function setup(): void
    {
        self::_registryRegister("monster_spawner", MonsterSpawner::override());
    }
}