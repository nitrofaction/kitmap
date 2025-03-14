<?php /** @noinspection PhpUnused */

namespace Kitmap\command\util;

use CortexPE\Commando\BaseCommand;
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
use pocketmine\item\VanillaItems;
use pocketmine\permission\DefaultPermissions;
use pocketmine\player\Player;
use pocketmine\plugin\PluginBase;

class Blocks extends BaseCommand
{
    private array $removedBlocks = [
        BlockTypeIds::DRAGON_EGG,
        BlockTypeIds::BED,
        BlockTypeIds::FURNACE,
        BlockTypeIds::BLAST_FURNACE,
        BlockTypeIds::SMOKER,
        BlockTypeIds::SHULKER_BOX,
        BlockTypeIds::DYED_SHULKER_BOX,
        BlockTypeIds::CHEST,
        BlockTypeIds::TRAPPED_CHEST,
        BlockTypeIds::ITEM_FRAME,
        BlockTypeIds::GLOWING_ITEM_FRAME,
        BlockTypeIds::HAY_BALE,
        BlockTypeIds::NETHER_QUARTZ_ORE,
        BlockTypeIds::IRON_ORE,
        BlockTypeIds::LAPIS_LAZULI_ORE,
        BlockTypeIds::GOLD_ORE,
        BlockTypeIds::COAL_ORE,
        BlockTypeIds::DIAMOND_ORE,
        BlockTypeIds::EMERALD_ORE,
        BlockTypeIds::DEEPSLATE_COAL_ORE,
        BlockTypeIds::DEEPSLATE_DIAMOND_ORE,
        BlockTypeIds::DEEPSLATE_EMERALD_ORE,
        BlockTypeIds::DEEPSLATE_LAPIS_LAZULI_ORE,
        BlockTypeIds::DEEPSLATE_REDSTONE_ORE,
        BlockTypeIds::DEEPSLATE_IRON_ORE,
        BlockTypeIds::DEEPSLATE_GOLD_ORE,
        BlockTypeIds::DEEPSLATE_COPPER_ORE,
        BlockTypeIds::COPPER_ORE,
        BlockTypeIds::NETHER_GOLD_ORE,
        BlockTypeIds::SMOKER,
        BlockTypeIds::CHISELED_NETHER_BRICKS,
        BlockTypeIds::TRAPPED_CHEST,
        BlockTypeIds::WHEAT,
        BlockTypeIds::CACTUS,
        BlockTypeIds::MELON,
        BlockTypeIds::MONSTER_SPAWNER,
        BlockTypeIds::SUGARCANE,
        BlockTypeIds::LAPIS_LAZULI,
        BlockTypeIds::EMERALD,
        BlockTypeIds::DIAMOND,
        BlockTypeIds::COAL,
        BlockTypeIds::TNT,
        BlockTypeIds::HOPPER,
        BlockTypeIds::BAMBOO,
        BlockTypeIds::GOLD,
        BlockTypeIds::RAW_GOLD,
        BlockTypeIds::RAW_IRON,
        BlockTypeIds::RAW_GOLD,
        BlockTypeIds::BAMBOO_SAPLING,
        BlockTypeIds::NETHER_WART,
        BlockTypeIds::NETHER_WART_BLOCK,
        BlockTypeIds::REDSTONE,
        BlockTypeIds::REDSTONE_ORE,
        BlockTypeIds::BEDROCK,
        BlockTypeIds::AIR,
        BlockTypeIds::GILDED_BLACKSTONE
    ];

    public function __construct(PluginBase $plugin)
    {
        parent::__construct(
            $plugin,
            "blocs",
            "Ouvre l'inventaire créatif",
            ["blocks"]
        );

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
                    if ($item->getCustomName() === "§r§qPage Suivante") {
                        $this->addItems($menu, ($page + 1));
                    } else if ($item->getCustomName() === "§r§qPage Précédente" && $page > 1) {
                        $this->addItems($menu, ($page - 1));
                    }

                    return;
                }

                $player = $transaction->getPlayer();

                $session = Session::get($player);

                if (3000 > $session->data["money"]) {
                    Util::removeCurrentWindow($player);
                    $player->sendMessage(Util::PREFIX . "Un stack coute §q3k$ §fet vous n'avez plus assez d'argent pour ça !");
                    return;
                }

                $session->addValue("money", 3000, true);
                $transaction->getPlayer()->getInventory()->addItem($item->setCount(64));

                $player->sendMessage(Util::PREFIX . "Vous venez acheter un stack de blocs pour §q3k$");
            }));

            $this->addItems($menu, $page);
            $menu->send($sender);
        }
    }

    private function addItems(InvMenu $menu, int $page): void
    {
        $menu->getInventory()->clearAll();

        /* @var ItemBlock[] $items */
        $items = array_filter(CreativeInventory::getInstance()->getAll(), function (Item $item): bool {
            return $item instanceof ItemBlock && !in_array($item->getTypeId(), $this->removedBlocks) && !in_array($item->getBlock()->getTypeId(), $this->removedBlocks);
        });

        foreach (Util::arrayToPage($items, $page, 45)[1] as $item) {
            if ($item instanceof ItemBlock) {
                $block = $item->getBlock();

                if ($block instanceof MobHead || $block instanceof ShulkerBox || $block instanceof Crops || $block instanceof Air) {
                    continue;
                }

                $item->getNamedTag()->setInt("block", 0);

                $item->setLore([
                    "§r ",
                    Util::PREFIX . "Le cout d'un stack de ce bloc vous coutera §q3k$" . Util::IARROW,
                    "§l§r ",
                    "§r§fDès que vous transférez un stack dans votre inventaire, l'argent",
                    "§r§fsera retiré de votre compte sans remboursement possible",
                    "§l "
                ]);

                $menu->getInventory()->addItem($item);
            }
        }

        $item = VanillaItems::DIAMOND()->setCount($page)->setCustomName("§r§qPage Actuel");
        $menu->getInventory()->setItem(45, $item);

        $item = VanillaItems::PAPER()->setCustomName("§r§qPage Précédente");
        $menu->getInventory()->setItem(48, $item);

        $item = VanillaItems::PAPER()->setCustomName("§r§qPage Suivante");
        $menu->getInventory()->setItem(50, $item);
    }

    protected function prepare(): void
    {
    }
}