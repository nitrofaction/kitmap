<?php /* @noinspection PhpDeprecationInspection */

namespace Kitmap\item\enchantment;

use Kitmap\entity\animation\LightningBolt;
use Kitmap\Util;
use pocketmine\entity\animation\HurtAnimation;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\item\enchantment\EnchantmentInstance;
use pocketmine\network\mcpe\NetworkBroadcastUtils;
use pocketmine\network\mcpe\protocol\LevelSoundEventPacket;
use pocketmine\network\mcpe\protocol\types\LevelSoundEvent;
use pocketmine\player\Player;

class Lightning extends Enchantment
{
    public function onAttack(EntityDamageEvent $event, EnchantmentInstance $enchantmentInstance, Player $player): void
    {
        $level = $enchantmentInstance->getLevel();
        $entity = $event->getEntity();

        // 1 => 200
        // TODO

        $chance = match ($level) {
            1 => 5,
            2 => 150,
            3 => 100
        };

        if (
            mt_rand(0, $chance) < 1 &&
            $entity instanceof Player
        ) {
            $lightning = new LightningBolt($entity->getLocation());
            $lightning->spawnToAll();

            $entity->setLastDamageCause(new EntityDamageByEntityEvent($player, $entity, $event::CAUSE_CUSTOM, 2));
            $entity->setHealth(max($entity->getHealth() - 2, 0));

            $hurtAnimation = new HurtAnimation($entity);
            $viewers = array_merge($entity->getViewers(), $player->getViewers());

            NetworkBroadcastUtils::broadcastPackets(array_unique($viewers), $hurtAnimation->encode());
            $entity->getWorld()->broadcastPacketToViewers($entity->getPosition()->asVector3(), LevelSoundEventPacket::create(LevelSoundEvent::THUNDER, $entity->getLocation(), -1, "minecraft:lightning_bolt", false, false));

            $entity->sendMessage(Util::PREFIX . "§n" . $player->getDisplayName() . " §fvient de vous envoyer un éclair dessus grâce à son enchantement §nFoudroiement §f!");
            $player->sendMessage(Util::PREFIX . "Vous venez d'envoyer un éclair dessus grâce à votre enchantement §nFoudroiement §fau joueur §n" . $entity->getDisplayName() . " §f!");
        }
    }
}