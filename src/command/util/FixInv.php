<?php /** @noinspection PhpUnused */

namespace Kitmap\command\util;

use CortexPE\Commando\BaseCommand;
use pocketmine\command\CommandSender;
use pocketmine\permission\DefaultPermissions;
use pocketmine\player\Player;
use pocketmine\plugin\PluginBase;

class FixInv extends BaseCommand
{
    public function __construct(PluginBase $plugin)
    {
        parent::__construct(
            $plugin,
            "fixinv",
            "En cas de problème URGENT, la commande /fixinv permet de régler le bug de son inventaire"
        );

        $this->setPermissions([DefaultPermissions::ROOT_USER]);
    }

    public function onRun(CommandSender $sender, string $aliasUsed, array $args): void
    {
        if ($sender instanceof Player) {
            // TODO: faire que ça transfère sur un serveur qui RE-TRANSFERE sur le kitmap
            // TODO: (un serveur dédié à ça en gros, genre un serveur qui fait que patienter que tout les servs soient là)

            $sender->transfer("kitmap");
        }
    }

    protected function prepare(): void
    {
    }
}