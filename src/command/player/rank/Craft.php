<?php /** @noinspection PhpUnused */

namespace Kitmap\command\player\rank;

use CortexPE\Commando\BaseCommand;
use Kitmap\handler\Rank;
use Kitmap\Session;
use Kitmap\Util;
use muqsit\invmenu\InvMenu;
use pocketmine\command\CommandSender;
use pocketmine\permission\DefaultPermissions;
use pocketmine\player\GameMode;
use pocketmine\player\Player;
use pocketmine\plugin\PluginBase;

class Craft extends BaseCommand
{
    public const INV_MENU_TYPE_WORKBENCH = "nitro:workbench";

    public function __construct(PluginBase $plugin)
    {
        parent::__construct(
            $plugin,
            "craft",
            "Permet l'ouverture de l'établi à distance"
        );

        $this->setPermissions([DefaultPermissions::ROOT_USER]);
    }

    public function onRun(CommandSender $sender, string $aliasUsed, array $args): void
    {
        if ($sender instanceof Player) {
            $session = Session::get($sender);

            if ($session->data["staff_mod"][0] || $sender->getGamemode() === GameMode::SPECTATOR()) {
                $sender->sendMessage(Util::PREFIX . "Vous ne pouvez pas accèder à votre enderchest en étant en staff mod");
                return;
            } else if (!Rank::hasRank($sender, "vip")) {
                $sender->sendMessage(Util::PREFIX . "Pour accèder à la commande §n/craft§f, vous devez avoir au minimum le grade §nVIP §f! Pour cela, achetez un grade sur la boutique: §nstore.nitrofaction.fr");
                return;
            }

            InvMenu::create(self::INV_MENU_TYPE_WORKBENCH)->send($sender);
        }
    }

    protected function prepare(): void
    {
    }
}