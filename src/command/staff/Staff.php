<?php /** @noinspection PhpUnused */

namespace Kitmap\command\staff;

use CortexPE\Commando\BaseCommand;
use Kitmap\handler\Rank;
use Kitmap\Main;
use Kitmap\Session;
use Kitmap\Util;
use pocketmine\block\utils\DyeColor;
use pocketmine\block\VanillaBlocks;
use pocketmine\command\CommandSender;
use pocketmine\item\enchantment\EnchantmentInstance;
use pocketmine\item\enchantment\VanillaEnchantments;
use pocketmine\item\VanillaItems;
use pocketmine\player\GameMode;
use pocketmine\player\Player;
use pocketmine\plugin\PluginBase;

class Staff extends BaseCommand
{
    public function __construct(PluginBase $plugin)
    {
        parent::__construct(
            $plugin,
            "staff",
            "Active ou désactive le mode staff"
        );

        $this->setPermissions([Rank::GROUP_STAFF]);
    }

    public function onRun(CommandSender $sender, string $aliasUsed, array $args): void
    {
        if ($sender instanceof Player) {
            $session = Session::get($sender);
            $data = $session->data["staff_mod"];

            if (!$data[0]) {
                if ($sender->getGamemode() === GameMode::SPECTATOR()) {
                    $sender->setGamemode(GameMode::SURVIVAL());
                }

                $session->data["staff_mod"] = [true, Util::savePlayerData($sender)];

                $sender->sendMessage(Util::PREFIX . "Vous venez d'activer le staff mod");
                $this->sendItems($sender);
            } else {
                Util::restorePlayer($sender, $data[1]);

                $session->data["staff_mod"] = [false, []];
                $sender->sendMessage(Util::PREFIX . "Vous venez de désactiver le staff mod");

                if (in_array($sender->getName(), Vanish::$vanish)) {
                    foreach (Main::getInstance()->getServer()->getOnlinePlayers() as $player) {
                        $player->showPlayer($sender);
                    }

                    unset(Vanish::$vanish[array_search($sender->getName(), Vanish::$vanish)]);
                }

                if (!$sender->isCreative()) {
                    $sender->setAllowFlight(false);
                    $sender->setFlying(false);
                }
            }
        }
    }

    private function sendItems(Player $player): void
    {
        $player->setAllowFlight(true);
        $player->getArmorInventory()->clearAll();

        $player->getInventory()->clearAll();
        $player->getXpManager()->setXpLevel(0);

        $knockback = new EnchantmentInstance(VanillaEnchantments::KNOCKBACK(), 2);

        $player->getInventory()->setItem(0, VanillaItems::BANNER()->setColor(DyeColor::LIGHT_GRAY())->setCustomName("§r" . Util::PREFIX . "Spectateur" . Util::IARROW));
        $player->getInventory()->setItem(2, VanillaItems::SLIMEBALL()->setCustomName("§r" . Util::PREFIX . "Knockback 2" . Util::IARROW)->addEnchantment($knockback));
        $player->getInventory()->setItem(3, VanillaItems::PAPER()->setCustomName("§r" . Util::PREFIX . "Alias" . Util::IARROW));
        $player->getInventory()->setItem(4, VanillaItems::SPIDER_EYE()->setCustomName("§r" . Util::PREFIX . "Random Tp" . Util::IARROW));
        $player->getInventory()->setItem(5, VanillaItems::BLAZE_ROD()->setCustomName("§r" . Util::PREFIX . "Freeze" . Util::IARROW));
        $player->getInventory()->setItem(6, VanillaItems::STICK()->setCustomName("§r" . Util::PREFIX . "Sanction" . Util::IARROW));
        $player->getInventory()->setItem(7, VanillaBlocks::CHEST()->asItem()->setCustomName("§r" . Util::PREFIX . "Invsee" . Util::IARROW));
        $player->getInventory()->setItem(8, VanillaBlocks::ENDER_CHEST()->asItem()->setCustomName("§r" . Util::PREFIX . "Ecsee" . Util::IARROW));

        if (in_array($player->getName(), Vanish::$vanish)) {
            $player->getInventory()->setItem(1, VanillaItems::DYE()->setColor(DyeColor::GREEN())->setCustomName("§r" . Util::PREFIX . "Vanish" . Util::IARROW));
        } else {
            $player->getInventory()->setItem(1, VanillaItems::DYE()->setColor(DyeColor::LIGHT_GRAY())->setCustomName("§r" . Util::PREFIX . "Vanish" . Util::IARROW));
        }
    }

    protected function prepare(): void
    {
    }
}