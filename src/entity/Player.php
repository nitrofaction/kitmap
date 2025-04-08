<?php

namespace Kitmap\entity;

use Kitmap\handler\Faction;
use Kitmap\item\ExtraVanillaItems;
use Kitmap\item\StrawArmor;
use Kitmap\Main;
use Kitmap\Session;
use Kitmap\Util;
use pocketmine\block\VanillaBlocks;
use pocketmine\entity\effect\EffectInstance;
use pocketmine\entity\effect\VanillaEffects;
use pocketmine\permission\DefaultPermissions;
use pocketmine\player\GameMode;
use pocketmine\player\Player as PmPlayer;
use pocketmine\world\Position;
use pocketmine\world\World;

class Player extends PmPlayer
{
    private array $lastPositions = [];

    private ?string $claim = null;

    private int $bedrockTicks = 0;
    private int $teleportCooldown = 0;

    private bool $strawArmor = false;

    protected function entityBaseTick(int $tickDiff = 1): bool
    {
        $tick = parent::entityBaseTick($tickDiff);
        $gamemode = $this->getGamemode();

        if ($gamemode === GameMode::CREATIVE() && !$this->hasPermission(DefaultPermissions::ROOT_OPERATOR)) {
            $this->setGamemode(GameMode::SURVIVAL());
        }

        if ($this->ticksLived % 5 == 0) {
            $claim = Faction::inClaim($this->getPosition()->getX(), $this->getPosition()->getZ());

            if ($this->claim !== $claim[1]) {
                $old = $this->claim;
                $this->claim = $faction = $claim[1];

                $this->sendTip("§8§l| " . $this->getFactionColor($old) . " §8§l» " . $this->getFactionColor($faction) . " §8§l|");
            }
        }

        if ($this->ticksLived % 20 == 0) {
            $actual = $this->strawArmor;
            $strawArmor = true;

            foreach ($this->getArmorInventory()->getContents(true) as $targetItem) {
                if (!ExtraVanillaItems::getItem($targetItem) instanceof StrawArmor) {
                    $strawArmor = false;
                }
            }

            if ($strawArmor) {
                $this->getEffects()->add(new EffectInstance(VanillaEffects::WATER_BREATHING(), 60 * 20, 0, false));
                $this->getEffects()->add(new EffectInstance(VanillaEffects::HASTE(), 60 * 20, 0, false));
                $this->getEffects()->add(new EffectInstance(VanillaEffects::SPEED(), 60 * 20, 2, false));
                $this->getEffects()->add(new EffectInstance(VanillaEffects::JUMP_BOOST(), 60 * 20, 2, false));

                $this->strawArmor = true;
            } else {
                if ($actual) {
                    $this->strawArmor = false;

                    $this->getEffects()->remove(VanillaEffects::WATER_BREATHING());
                    $this->getEffects()->remove(VanillaEffects::HASTE());
                    $this->getEffects()->remove(VanillaEffects::SPEED());
                    $this->getEffects()->remove(VanillaEffects::JUMP_BOOST());
                }
            }
        }

        $this->getHungerManager()->setFood(18);

        if ($this->bedrockTicks === 0 && $gamemode === GameMode::SURVIVAL()) {
            $this->lastPositions[] = $this->getPosition();

            if (count($this->lastPositions) > 20) {
                array_shift($this->lastPositions);
            }
        }

        foreach ($this->getBlocksIntersected(0.001) as $block) {
            if ($gamemode === GameMode::SURVIVAL() && $block->hasSameTypeId(VanillaBlocks::BEDROCK())) {
                $this->bedrockTicks++;

                if ($this->bedrockTicks > 4) {
                    array_pop($this->lastPositions);
                    $last = array_pop($this->lastPositions);

                    if ($last instanceof Position && $last->isValid()) {
                        $this->bedrockTicks = 0;

                        $this->sendMessage(Util::PREFIX . "Vous n'avez pas le droit de suffoquer dans la bedrock, vous avez été téléporté à votre derniere position");
                        $this->teleport($last);
                    }
                }

                break;
            } else if (!$block->hasSameTypeId(VanillaBlocks::BEDROCK())) {
                $this->bedrockTicks = 0;
            }

            if (time() > $this->teleportCooldown && in_array($block->getName(), ["End Portal", "Water", "Nether Portal"])) {
                if (Util::insideZone($block->getPosition(), "mine-tp")) {
                    $world = Main::getInstance()->getServer()->getWorldManager()->getWorldByName("mine");

                    if (!$world instanceof World) {
                        break;
                    }

                    $this->teleport($world->getSpawnLocation());
                } else if (Util::insideZone($block->getPosition(), "island-tp")) {
                    $this->chat("/f island tp");
                } else if (Util::insideZone($block->getPosition(), "claim-tp")) {
                    $this->chat("/f home");
                }

                $this->teleportCooldown = time() + 1;
                break;
            }
        }

        return $tick;
    }

    private function getFactionColor(?string $faction): string
    {
        if (is_null($faction)) {
            return "§r§qWarzone";
        } else {
            $playerFaction = Session::get($this)->data["faction"];
            $ally = Faction::getAlly($playerFaction);

            if ($playerFaction === $faction) {
                return "§r§a" . Faction::getFactionUpperName($faction);
            } else if ($ally === $faction) {
                return "§r§e" . Faction::getFactionUpperName($faction);
            } else {
                return "§r§c" . Faction::getFactionUpperName($faction);
            }
        }
    }
}