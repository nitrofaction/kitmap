<?php /** @noinspection PhpUnused */

namespace Kitmap\command\util;

use CortexPE\Commando\BaseCommand;
use Kitmap\handler\Cache;
use Kitmap\Session;
use Kitmap\Util;
use muqsit\invmenu\InvMenu;
use muqsit\invmenu\transaction\DeterministicInvMenuTransaction;
use muqsit\invmenu\type\InvMenuTypeIds;
use pocketmine\block\Air;
use pocketmine\block\BlockTypeIds;
use pocketmine\block\Crops;
use pocketmine\block\MobHead;
use pocketmine\block\ShulkerBox;
use pocketmine\command\CommandSender;
use pocketmine\inventory\CreativeInventory;
use pocketmine\item\Item;
use pocketmine\item\ItemBlock;
use pocketmine\item\StringToItemParser;
use pocketmine\item\VanillaItems;
use pocketmine\permission\DefaultPermissions;
use pocketmine\player\Player;
use pocketmine\plugin\PluginBase;

class Blocks extends BaseCommand
{
    /* @var ItemBlock[] $items */
    private array $items;

    public function __construct(PluginBase $plugin)
    {
        parent::__construct(
            $plugin,
            "blocs",
            "Ouvre l'inventaire créatif",
            ["blocks"]
        );

        $this->items = array_filter(CreativeInventory::getInstance()->getAll(), function (Item $item): bool {
            if (!$item instanceof ItemBlock) {
                return false;
            }

            $block = $item->getBlock();

            foreach (Cache::$config["block"] as $removedBlock) {
                $typeId = constant(BlockTypeIds::class . "::" . $removedBlock);;

                if ($item->getTypeId() === $typeId || $block->getTypeId() === $typeId) {
                    return false;
                }
            }

            foreach (Cache::$config["craft"]["remove"] as $itemName) {
                $itemToDelete = StringToItemParser::getInstance()->parse($itemName);

                if ($itemToDelete !== null && $item->equals($itemToDelete, true, false)) {
                    return false;
                }
            }

            return $block->getBreakInfo()->isBreakable();
        });

        $this->setPermissions([DefaultPermissions::ROOT_USER]);
    }

    public function onRun(CommandSender $sender, string $aliasUsed, array $args): void
    {
        if ($sender instanceof Player) {
            $session = Session::get($sender);

            if ($session->inCooldown("combat")) {
                $sender->sendMessage(Util::PREFIX . "Cette commande est interdite en combat");
                return;
            } else if ($session->inCooldown("teleportation")) {
                $sender->sendMessage(Util::PREFIX . "Vous ne pouvez pas executer cette commande en teleportation");
                return;
            }

            $menu = InvMenu::create(InvMenuTypeIds::TYPE_DOUBLE_CHEST);
            $menu->setName("Blocs");

            $page = 1;

            $menu->setListener(InvMenu::readonly(function (DeterministicInvMenuTransaction $transaction) use ($menu, $page): void {
                $item = $transaction->getItemClicked();
                $page = $menu->getInventory()->getItem(45)->getCount();

                if (is_null($item->getNamedTag()->getTag("block"))) {
                    if ($item->getCustomName() === "§r§nPage Suivante") {
                        $this->addItems($menu, ($page + 1));
                    } else if ($item->getCustomName() === "§r§nPage Précédente" && $page > 1) {
                        $this->addItems($menu, ($page - 1));
                    }

                    return;
                }

                $player = $transaction->getPlayer();

                $session = Session::get($player);

                if (2500 > $session->data["money"]) {
                    Util::removeCurrentWindow($player);
                    $player->sendMessage(Util::PREFIX . "Un stack coute §n2k5$ §fet vous n'avez plus assez d'argent pour ça !");
                    return;
                }

                $session->addValue("money", 2500, true);
                $transaction->getPlayer()->getInventory()->addItem($item->setCount(64));

                $player->sendMessage(Util::PREFIX . "Vous venez acheter un stack de blocs pour §n2k5$");
            }));

            $this->addItems($menu, $page);
            $menu->send($sender);
        }
    }

    private function addItems(InvMenu $menu, int $page): void
    {
        $menu->getInventory()->clearAll();

        foreach (Util::arrayToPage($this->items, $page, 45)[1] as $item) {
            if ($item instanceof ItemBlock) {
                $block = $item->getBlock();

                if ($block instanceof MobHead || $block instanceof ShulkerBox || $block instanceof Crops || $block instanceof Air) {
                    continue;
                }

                $item->getNamedTag()->setInt("block", 0);

                $item->setLore([
                    "§r ",
                    Util::PREFIX . "Le cout d'un stack de ce bloc vous coutera §n2k5$" . Util::IARROW,
                    "§l§r ",
                    "§r§fDès que vous transférez un stack dans votre inventaire, l'argent",
                    "§r§fsera retiré de votre compte sans remboursement possible",
                    "§l "
                ]);

                $menu->getInventory()->addItem($item);
            }
        }

        $item = VanillaItems::DIAMOND()->setCount($page)->setCustomName("§r§nPage Actuel");
        $menu->getInventory()->setItem(45, $item);

        $item = VanillaItems::PAPER()->setCustomName("§r§nPage Précédente");
        $menu->getInventory()->setItem(48, $item);

        $item = VanillaItems::PAPER()->setCustomName("§r§nPage Suivante");
        $menu->getInventory()->setItem(50, $item);
    }

    protected function prepare(): void
    {
    }
}