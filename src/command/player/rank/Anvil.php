<?php /* @noinspection PhpUnused */

namespace Kitmap\command\player\rank;

use CortexPE\Commando\BaseCommand;
use Kitmap\block\Anvil as Api;
use Kitmap\handler\Rank;
use Kitmap\Util;
use pocketmine\command\CommandSender;
use pocketmine\permission\DefaultPermissions;
use pocketmine\player\Player;
use pocketmine\plugin\PluginBase;

class Anvil extends BaseCommand
{
    public function __construct(PluginBase $plugin)
    {
        parent::__construct(
            $plugin,
            "anvil",
            "Permet l'ouverture de l'enclume à distance"
        );

        $this->setAliases(["enclume"]);
        $this->setPermissions([DefaultPermissions::ROOT_USER]);
    }

    public function onRun(CommandSender $sender, string $aliasUsed, array $args): void
    {
        if ($sender instanceof Player) {
            if (!Rank::hasRank($sender, "vip-plus")) {
                $sender->sendMessage(Util::PREFIX . "Pour accèder à la commande §n/enclume§f, vous devez avoir au minimum le grade §nVIP+ §f! Pour cela, achetez un grade sur la boutique: §nstore.nitrofaction.fr");
                return;
            }

            Api::openAnvil($sender);
        }
    }

    protected function prepare(): void
    {
    }
}