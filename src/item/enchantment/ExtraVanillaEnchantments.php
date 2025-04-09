<?php /** @noinspection PhpInternalEntityUsedInspection */

namespace Kitmap\item\enchantment;

use Kitmap\Util;
use pocketmine\data\bedrock\EnchantmentIdMap;
use pocketmine\data\bedrock\EnchantmentIds;
use pocketmine\item\enchantment\Enchantment as PmEnchantment;

class ExtraVanillaEnchantments
{
    public const GLOW = -1;

    public const DOUBLE_PICKAXE = EnchantmentIds::FIRE_PROTECTION;
    public const HAMMER = EnchantmentIds::THORNS;
    public const KILL_TRACKER = EnchantmentIds::RESPIRATION;
    public const LIGHTNING = EnchantmentIds::FROST_WALKER;
    public const INFINITE = EnchantmentIds::PROJECTILE_PROTECTION;

    private static array $enchantments = [];

    public function __construct()
    {
        self::addEnchantment(EnchantmentIdMap::getInstance()->fromId(self::DOUBLE_PICKAXE), new DoublePickaxe());
        self::addEnchantment(EnchantmentIdMap::getInstance()->fromId(self::HAMMER), new Hammer());
        self::addEnchantment(EnchantmentIdMap::getInstance()->fromId(self::KILL_TRACKER), new KillTracker());
        self::addEnchantment(EnchantmentIdMap::getInstance()->fromId(self::LIGHTNING), new Lightning());
        self::addEnchantment(EnchantmentIdMap::getInstance()->fromId(self::INFINITE), new Infinite());

        EnchantmentIdMap::getInstance()->register(self::GLOW, new Glow());
    }

    public static function addEnchantment(PmEnchantment $enchantment, Enchantment $replace): void
    {
        self::$enchantments[Util::reprocess($enchantment->getName())] = $replace;
    }

    public static function getEnchantment(PmEnchantment $enchantment): Enchantment
    {
        return self::$enchantments[Util::reprocess($enchantment->getName())] ?? new Enchantment();
    }
}