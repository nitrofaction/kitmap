<?php /** @noinspection PhpUnused */

namespace Kitmap\command\player\rank;

use CortexPE\Commando\args\OptionArgument;
use CortexPE\Commando\BaseCommand;
use Kitmap\handler\Rank;
use Kitmap\item\Durable as CustomDurable;
use Kitmap\Session;
use Kitmap\Util;
use pocketmine\command\CommandSender;
use pocketmine\item\Durable;
use pocketmine\item\Item;
use pocketmine\permission\DefaultPermissions;
use pocketmine\player\Player;
use pocketmine\plugin\PluginBase;

class Repair extends BaseCommand
{
    public function __construct(PluginBase $plugin)
    {
        parent::__construct(
            $plugin,
            "repair",
            "Permet de réparer les items dans sa main ou son inventaire"
        );

        $this->setPermissions([DefaultPermissions::ROOT_USER]);
    }

    public function onRun(CommandSender $sender, string $aliasUsed, array $args): void
    {
        if ($sender instanceof Player) {
            $session = Session::get($sender);

            if (!Rank::hasRank($sender, "vip-plus")) {
                $sender->sendMessage(Util::PREFIX . "Pour accèder à la commande §n/repair§f, vous devez avoir au minimum le grade §nVIP+ §f! Pour cela, achetez un grade sur la boutique: §nstore.nitrofaction.fr");
                return;
            } else if ($session->inCooldown("repair")) {
                $format = Util::formatDurationFromSeconds($session->getCooldownData("repair")[0] - time());
                $sender->sendMessage(Util::PREFIX . "Vous ne pourrez ré-utiliser la commande §n/repair §fque dans: §n" . $format);
                return;
            } else if ($session->inCooldown("combat")) {
                $sender->sendMessage(Util::PREFIX . "Cette commande est interdite en combat");
                return;
            }

            if (isset($args["opt"]) && strtolower($args["opt"]) === "all") {
                if (!Rank::hasRank($sender, "ultra")) {
                    $sender->sendMessage(Util::PREFIX . "Pour accèder à la commande §n/repair all§f, vous devez avoir au minimum le grade §nUltra §f! Pour cela, achetez un grade sur la boutique: §nstore.nitrofaction.fr");
                    return;
                }

                foreach ($sender->getInventory()->getContents() as $index => $item) $this->repairItem($item, $index, $sender->getInventory());
                foreach ($sender->getArmorInventory()->getContents() as $index => $item) $this->repairItem($item, $index, $sender->getArmorInventory());

                $session->setCooldown("repair", 60 * 10);
                $sender->sendMessage(Util::PREFIX . "Vous venez de réparer tous les items dans votre inventaire");

                return;
            }

            $index = $sender->getInventory()->getHeldItemIndex();
            $item = $sender->getInventory()->getItem($index);

            $repair = $this->repairItem($item, $index, $sender->getInventory());

            if (!$repair) {
                $sender->sendMessage(Util::PREFIX . "L'item dans votre main ne peut pas être réparé");
            } else {
                $sender->sendMessage(Util::PREFIX . "L'item dans votre main a été réparé");
                $session->setCooldown("repair", 60);
            }
        }
    }

    private function repairItem(Item $item, int $index, $inventory): bool
    {
        if ($item instanceof Durable) {
            $item->setDamage(0);

            if (!is_null($item->getNamedTag()->getTag(CustomDurable::DAMAGE_TAG))) {
                $item->getNamedTag()->removeTag(CustomDurable::DAMAGE_TAG);
            }

            $inventory->setItem($index, $item);
            return true;
        }
        return false;
    }

    protected function prepare(): void
    {
        $this->registerArgument(0, new OptionArgument("opt", ["all"], true));
    }
}