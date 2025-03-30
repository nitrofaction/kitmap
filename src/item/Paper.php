<?php

namespace Kitmap\item;

use Kitmap\handler\Cache;
use Kitmap\handler\PartnerItem;
use Kitmap\Session;
use Kitmap\Util;
use pocketmine\event\player\PlayerItemUseEvent;

class Paper extends Item
{
    public function onUse(PlayerItemUseEvent $event): bool
    {
        $player = $event->getPlayer();
        $item = $event->getItem();

        if (!is_null($item->getNamedTag()->getTag("money"))) {
            $money = $item->getNamedTag()->getInt("money");

            Session::get($player)->addValue("money", $money);
            $player->sendMessage(Util::PREFIX . "§fVous venez de recevoir §n" . $money . "$ §f!");

            $this->projectileSucces($player, $item);
            $event->cancel();

            return true;
        } else if (!is_null($item->getNamedTag()->getTag("pack"))) {
            $packs = $item->getNamedTag()->getInt("pack");

            Session::get($player)->addValue("packs", $packs);
            $player->sendMessage(Util::PREFIX . "§fVous venez de recevoir §n" . $packs . " PACKS §f!");

            $this->projectileSucces($player, $item);
            $event->cancel();

            return true;
        } else if (!is_null($item->getNamedTag()->getTag("partneritem"))) {
            $name = $item->getNamedTag()->getTag("partneritem");
            $val = $name->getValue();

            if (!is_int($val)) {
                return false;
            }

            $amount = intval($val);
            $keys = array_keys(Cache::$config["partneritem"]);

            $selectedKeys = [];

            for ($i = 0; $i < $amount; $i++) {
                $selectedKeys[] = $keys[array_rand($keys)];
            }

            foreach ($selectedKeys as $key) {
                $partneritem = PartnerItem::createItem($key);
                Util::addItem($player, $partneritem);
            }

            $player->sendMessage(Util::PREFIX . "§fVous venez de recevoir §n" . $amount . " partneritems §fdivers !");

            $this->projectileSucces($player, $item);
            $event->cancel();

            return true;
        } else if (!is_null($item->getNamedTag()->getTag("kit"))) {
            $kit = $item->getNamedTag()->getString("kit");

            Util::executeCommand("givekit \"" . $player->getName() . "\" " . $kit);

            $this->projectileSucces($player, $item);
            $event->cancel();

            return true;
        } else if (!is_null($item->getNamedTag()->getTag("xp_boost"))) {
            $boost = $item->getNamedTag()->getInt("xp_boost");
            $duration = $item->getNamedTag()->getInt("duration");

            $session = Session::get($player);

            if ($session->inCooldown("xp_boost")) {
                $current = $session->getCooldownData("xp_boost")[1];
                $remaining = $session->getCooldownData("xp_boost")[0];

                $player->sendMessage(Util::PREFIX . "Vous possèdez déjà un boost de §n" . $current . "%§f, pour utiliser un nouveau boost veuillez attendre: §n" . Util::formatDurationFromSeconds($remaining));
                return true;
            }

            $session->setCooldown("xp_boost", $duration, [$boost]);
            $player->sendMessage(Util::PREFIX . "§fVous venez de recevoir un boost de §n" . $boost . "% §fsur l'xp de vos jobs pendant §n" . Util::formatDurationFromSeconds($duration));

            $this->projectileSucces($player, $item);
            $event->cancel();

            return true;
        }

        return false;
    }

    public function isRare(): bool
    {
        return true;
    }
}