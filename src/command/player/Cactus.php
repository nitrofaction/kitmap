<?php /** @noinspection PhpUnused */

namespace Kitmap\command\player;

use CortexPE\Commando\BaseCommand;
use Kitmap\Session;
use Kitmap\Util;
use pocketmine\command\CommandSender;
use pocketmine\permission\DefaultPermissions;
use pocketmine\player\Player;
use pocketmine\plugin\PluginBase;

class Cactus extends BaseCommand
{
    public function __construct(PluginBase $plugin)
    {
        parent::__construct(
            $plugin,
            "cactus",
            "Active les alertes quand un cactus a poussé"
        );

        $this->setPermissions([DefaultPermissions::ROOT_USER]);
    }

    public function onRun(CommandSender $sender, string $aliasUsed, array $args): void
    {
        if ($sender instanceof Player) {
            $session = Session::get($sender);

            if ($session->data["cactus"]) {
                $session->data["cactus"] = false;
                $sender->sendMessage(Util::PREFIX . "Vous n'avez plus les alertes quand un cactus pousse");
            } else {
                $session->data["cactus"] = true;
                $sender->sendMessage(Util::PREFIX . "Vous avez desormais les alertes quand un cactus pousse");
            }
        }
    }

    protected function prepare(): void
    {
    }
}