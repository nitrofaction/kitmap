<?php /** @noinspection PhpUnused */

namespace Kitmap\command\player\rank;

use CortexPE\Commando\args\OptionArgument;
use CortexPE\Commando\BaseCommand;
use Kitmap\handler\Cache;
use Kitmap\handler\Rank;
use Kitmap\Main;
use Kitmap\Util;
use pocketmine\command\CommandSender;
use pocketmine\item\Item;
use pocketmine\permission\DefaultPermissions;
use pocketmine\player\Player;

class Condense extends BaseCommand
{
    public function __construct()
    {
        parent::__construct(
            Main::getInstance(),
            "condense",
            "Permet de condenser les items dans sa main ou son inventaire"
        );

        $this->setPermissions([DefaultPermissions::ROOT_USER]);
    }

    public function onRun(CommandSender $sender, string $aliasUsed, array $args): void
    {
        if ($sender instanceof Player) {
            $opt = $args["opt"] ?? "hand";

            if ($opt === "all") {
                if (!Rank::hasRank($sender, "vip-plus")) {
                    $sender->sendMessage(Util::PREFIX . "Pour accèder à la commande §n/condense all§f, vous devez avoir au minimum le grade §nVIP+ §f! Pour cela, achetez un grade sur la boutique: §nstore.nitrofaction.fr");
                    return;
                }

                $items = $sender->getInventory()->getContents();

                foreach ($items as $item) {
                    $result = $this->condense($item);

                    if ($result === null) {
                        continue;
                    }

                    $sender->getInventory()->removeItem($item);

                    Util::addItem($sender, $result[0]);
                    Util::addItem($sender, $result[1]);
                }

                $sender->sendMessage(Util::PREFIX . "Vous avez condensé tous les items de votre inventaire");
            } else if ($opt === "hand") {
                if (!Rank::hasRank($sender, "vip")) {
                    $sender->sendMessage(Util::PREFIX . "Pour accèder à la commande §n/condense§f, vous devez avoir au minimum le grade §nVIP §f! Pour cela, achetez un grade sur la boutique: §nstore.nitrofaction.fr");
                    return;
                }

                $item = $sender->getInventory()->getItemInHand();

                if ($item->isNull()) {
                    $sender->sendMessage(Util::PREFIX . "Vous devez avoir un item en main");
                    return;
                }

                $result = $this->condense($item);

                if ($result === null) {
                    $sender->sendMessage(Util::PREFIX . "Vous ne pouvez pas condenser cet item");
                    return;
                }

                $sender->getInventory()->removeItem($item);

                Util::addItem($sender, $result[0]);
                Util::addItem($sender, $result[1]);

                $sender->sendMessage(Util::PREFIX . "Vous avez condensé l'item dans votre main");
            }
        }
    }

    private function condense(Item $item): ?array
    {
        foreach (Cache::$condenseShapes as $data) {
            $input = $data["input"];
            $output = $data["output"];

            if (!$input instanceof Item || !$output instanceof Item) {
                continue;
            }

            if ($input->equals($item)) {
                $itemCount = $item->getCount();

                $condensedCount = intval($itemCount / $data["count"]);
                $notCondensedCount = $itemCount - ($condensedCount * $data["count"]);

                $condensed = clone $output;
                $notCondensed = clone $input;

                return [
                    $condensed->setCount($condensedCount),
                    $notCondensed->setCount($notCondensedCount)
                ];
            }
        }

        return null;
    }

    protected function prepare(): void
    {
        $this->registerArgument(0, new OptionArgument("opt", ["all"], true));
    }
}