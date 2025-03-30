<?php

namespace Kitmap\entity\npc;

use cosmicpe\npcdialogue\dialogue\texture\DefaultNpcDialogueTexture;
use cosmicpe\npcdialogue\NpcDialogueBuilder;
use cosmicpe\npcdialogue\NpcDialogueManager;
use Kitmap\handler\Cache;
use Kitmap\Main;
use Kitmap\Session;
use Kitmap\Util;
use pocketmine\entity\Villager;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\network\mcpe\protocol\types\entity\EntityIds;
use pocketmine\player\Player;
use pocketmine\scheduler\ClosureTask;

class Quest extends Villager
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

        $dialogue = NpcDialogueBuilder::create()
            ->setName("Quête")
            ->setText("Bienvenue dans le menu des quêtes ! Je te proposerai ici les quêtes qui m'ont été confiées.\n\nDonne-moi les items demandés et tu recevras des récompenses, comme des pioches ou des houes en ilvaïte.")
            ->setDefaultNpcTexture(DefaultNpcDialogueTexture::TEXTURE_NPC_1);

        foreach (Cache::$config["quest"] as $id => $data) {
            $dialogue = $dialogue->addSimpleButton($id, function (Player $player) use ($id): void {
                NpcDialogueManager::remove($player);

                Main::getInstance()->getScheduler()->scheduleDelayedTask(new ClosureTask(function () use ($player, $id) {
                    if ($player->isOnline()) {
                        $this->openQuestForm($player, $id);
                    }
                }), 5);
            });
        }

        NpcDialogueManager::send($player, $dialogue->build(), true);
    }

    private function openQuestForm(Player $player, string $id): void
    {
        $data = Cache::$config["quest"][$id];
        $session = Session::get($player);

        $dialogue = NpcDialogueBuilder::create()
            ->setName(ucfirst($id))
            ->setDefaultNpcTexture($data["texture"]);

        $prefix = "§f[§n" . ucfirst($id) . "§f] " . Util::PREFIX;

        if ($session->inCooldown("quest_" . $id)) {
            $dialogue = $dialogue->setText($data["in-cooldown"]);

            $dialogue = $dialogue->addSimpleButton("Merci quand même", function (Player $player) use ($prefix) {
                NpcDialogueManager::remove($player);
                $player->sendMessage($prefix . "Sans soucis ! Reviens quand tu veux !");
            });
        } else {
            $dialogue = $dialogue->addSimpleButton("Je reviens", function (Player $player) use ($prefix) {
                NpcDialogueManager::remove($player);
                $player->sendMessage($prefix . "Merci et reviens vite !");
            });

            $dialogue = $dialogue->addSimpleButton("J'ai cela !", function (Player $player) use ($session, $prefix, $id, $data) {
                NpcDialogueManager::remove($player);

                foreach ($data["needs"] as $value) {
                    [$name, $count] = explode(":", $value);

                    $count = intval($count);
                    $item = Util::getItemByName($name);

                    if ($count > Util::getItemCount($player, $item)) {
                        $player->sendMessage($prefix . "Tu n'as pas ce que je demande... Reviens quand tu auras tout !");
                        return;
                    }
                }

                foreach ($data["needs"] as $value) {
                    [$name, $count] = explode(":", $value);

                    $count = intval($count);
                    $item = Util::getItemByName($name);

                    $player->getInventory()->removeItem($item->setCount($count));
                }

                foreach ($data["rewards"] as $value) {
                    Util::addItem($player, Util::parseItem($value));
                }

                $player->sendMessage($prefix . $data["completed"]);
                $session->setCooldown("quest_" . $id, 60 * 60 * 6);
            });

            $dialogue = $dialogue->setText($data["description"]);
        }

        NpcDialogueManager::send($player, $dialogue->build(), true);
    }

    public function knockBack(float $x, float $z, float $force = 0.4, ?float $verticalLimit = 0.4): void
    {
    }

    protected function initEntity(CompoundTag $nbt): void
    {
        parent::initEntity($nbt);

        $this->setNameTag("Quête");
        $this->setNameTagAlwaysVisible();
    }
}