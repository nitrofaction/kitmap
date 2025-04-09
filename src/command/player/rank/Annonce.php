<?php /** @noinspection PhpUnused */

namespace Kitmap\command\player\rank;

use CortexPE\Commando\args\TextArgument;
use CortexPE\Commando\BaseCommand;
use Kitmap\handler\Rank;
use Kitmap\Main;
use Kitmap\Session;
use Kitmap\Util;
use pocketmine\command\CommandSender;
use pocketmine\permission\DefaultPermissions;
use pocketmine\player\Player;
use pocketmine\plugin\PluginBase;

class Annonce extends BaseCommand
{
    public function __construct(PluginBase $plugin)
    {
        parent::__construct(
            $plugin,
            "annonce",
            "Permet de diffuser une annonce à tous les joueurs connectés"
        );

        $this->setPermissions([DefaultPermissions::ROOT_USER]);
    }

    public function onRun(CommandSender $sender, string $aliasUsed, array $args): void
    {
        if ($sender instanceof Player) {
            $session = Session::get($sender);

            if (!Rank::hasRank($sender, "ultra")) {
                $sender->sendMessage(Util::PREFIX . "Pour accèder à la commande §n/annonce§f, vous devez avoir au minimum le grade §nUltra §f! Pour cela, achetez un grade sur la boutique: §nstore.nitrofaction.fr");
                return;
            } else if ($session->inCooldown("mute")) {
                $sender->sendMessage(Util::PREFIX . "Vous êtes mute, temps restant: §n" . Util::formatDurationFromSeconds($session->getCooldownData("mute")[0] - time()));
                return;
            }

            if ($session->inCooldown("annonce")) {
                $format = Util::formatDurationFromSeconds($session->getCooldownData("annonce")[0] - time());
                $sender->sendMessage(Util::PREFIX . "Vous ne pourrez ré-utiliser la commande §n/annonce §fque dans: §n" . $format);
                return;
            }

            $session->setCooldown("annonce", 60 * 120);
            Main::getInstance()->getServer()->broadcastMessage("§n§lANNONCE§r §f" . $sender->getName() . " " . Util::PREFIX . implode(" ", $args));
        }
    }

    protected function prepare(): void
    {
        $this->registerArgument(0, new TextArgument("message"));
    }
}