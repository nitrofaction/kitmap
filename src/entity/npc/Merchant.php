<?php

namespace Kitmap\entity\npc;

use cosmicpe\npcdialogue\dialogue\texture\DefaultNpcDialogueTexture;
use cosmicpe\npcdialogue\NpcDialogueBuilder;
use cosmicpe\npcdialogue\NpcDialogueManager;
use Kitmap\Util;
use pocketmine\entity\Villager;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\item\VanillaItems;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\network\mcpe\protocol\types\entity\EntityIds;
use pocketmine\player\Player;

class Merchant extends Villager
{
    public static function getNetworkTypeId(): string
    {
        return EntityIds::VILLAGER_V2;
    }

    public function attack(EntityDamageEvent $source): void
    {
        $player = null;

        if ($source instanceof EntityDamageByEntityEvent) {
            $player = $source->getDamager();
        }

        if (!$player instanceof Player) {
            return;
        }

        $prefix = "§f[§qMarchant§f] " . Util::PREFIX;

        $dialogue = NpcDialogueBuilder::create()
            ->setName("Marchant")
            ->setText("Salut, je suis le marchant ! C'est moi qui transforme ton §qémeraude §ren §qémeraude renforcé §r!\n\nPour cela il me faudra:\n" . Util::PREFIX . "§r16 lingots d'émeraude\n" . Util::PREFIX . "§r32 chairs putréfiées\n" . Util::PREFIX . "§r25 lingots d'or")
            ->setDefaultNpcTexture(DefaultNpcDialogueTexture::TEXTURE_AGRICULTURE_8);

        $dialogue = $dialogue->addSimpleButton("Je reviens", function (Player $player) use ($prefix) {
            NpcDialogueManager::remove($player);
            $player->sendMessage($prefix . "Merci et reviens vite !");
        });

        $dialogue = $dialogue->addSimpleButton("J'ai cela", function (Player $player) use ($prefix) {
            NpcDialogueManager::remove($player);

            $requirements = [
                "emerald:16",
                "rotten_flesh:32",
                "gold_ingot:25"
            ];

            foreach ($requirements as $requirement) {
                [$name, $count] = explode(":", $requirement);

                $count = intval($count);
                $item = Util::getItemByName($name);

                if ($count > Util::getItemCount($player, $item)) {
                    $player->sendMessage($prefix . "Tu n'as pas tout ce qu'il faut... Reviens quand tu auras tout !");
                    return;
                }
            }

            foreach ($requirements as $requirement) {
                [$name, $count] = explode(":", $requirement);
                $count = intval($count);
                $item = Util::getItemByName($name);
                $player->getInventory()->removeItem($item->setCount($count));
            }

            Util::addItem($player, VanillaItems::DRAGON_BREATH());

            $player->sendMessage($prefix . "Et hop ! Je t'ai donné ton lingot d'émeraude renforcé, merci pour ton or et ta chair putréfiée !!");
        });

        NpcDialogueManager::send($player, $dialogue->build(), true);
    }

    public function knockBack(float $x, float $z, float $force = 0.4, ?float $verticalLimit = 0.4): void
    {
    }

    protected function initEntity(CompoundTag $nbt): void
    {
        parent::initEntity($nbt);

        $this->setNameTag("Marchant");
        $this->setNameTagAlwaysVisible();
    }
}