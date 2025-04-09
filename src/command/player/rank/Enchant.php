<?php /* @noinspection PhpUnused */

namespace Kitmap\command\player\rank;

use CortexPE\Commando\BaseCommand;
use Kitmap\block\EnchantingTable;
use Kitmap\handler\Rank;
use Kitmap\Util;
use pocketmine\command\CommandSender;
use pocketmine\permission\DefaultPermissions;
use pocketmine\player\Player;
use pocketmine\plugin\PluginBase;

class Enchant extends BaseCommand
{
    public function __construct(PluginBase $plugin)
    {
        parent::__construct(
            $plugin,
            "enchant",
            "Permet l'ouverture de la table d'enchant à distance"
        );

        $this->setAliases(["table", "enchantment"]);
        $this->setPermissions([DefaultPermissions::ROOT_USER]);
    }

    public function onRun(CommandSender $sender, string $aliasUsed, array $args): void
    {
        if ($sender instanceof Player) {
            if (!Rank::hasRank($sender, "vip")) {
                $sender->sendMessage(Util::PREFIX . "Pour accèder à la commande §n/enchant§f, vous devez avoir au minimum le grade §nVIP §f! Pour cela, achetez un grade sur la boutique: §nstore.nitrofaction.fr");
                return;
            }

            EnchantingTable::openEnchantTable($sender);
        }
    }

    protected function prepare(): void
    {
    }
}