<?php /** @noinspection PhpUnused */

namespace Kitmap\command\staff\op\dev;

use CortexPE\Commando\BaseCommand;
use Kitmap\Main;
use Kitmap\Util;
use pocketmine\command\CommandSender;
use pocketmine\permission\DefaultPermissions;
use pocketmine\player\Player;

class ItemInfo extends BaseCommand
{
    public function __construct()
    {
        parent::__construct(
            Main::getInstance(),
            "iteminfo",
            "À utiliser seulement si on connait l'usage /!\ §4(D)"
        );

        $this->setPermissions([DefaultPermissions::ROOT_OPERATOR]);
    }

    public function onRun(CommandSender $sender, string $aliasUsed, array $args): void
    {
        if ($sender instanceof Player) {
            $item = $sender->getInventory()->getItemInHand();

            $name = $item->getVanillaName();
            $formatted = Util::reprocess($name);

            $sender->sendMessage(Util::PREFIX . "Name: " . $name);
            $sender->sendMessage(Util::PREFIX . "Processed Name: " . $formatted);

            var_dump($formatted);
        }
    }

    protected function prepare(): void
    {
    }
}