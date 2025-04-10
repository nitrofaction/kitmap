<?php /** @noinspection PhpUnused */

namespace Kitmap\command\util;

use CortexPE\Commando\BaseCommand;
use Kitmap\handler\Cache;
use Kitmap\Util;
use pocketmine\command\CommandSender;
use pocketmine\permission\DefaultPermissions;
use pocketmine\plugin\PluginBase;

class Bourse extends BaseCommand
{
    public function __construct(PluginBase $plugin)
    {
        parent::__construct(
            $plugin,
            "bourse",
            "Affiche le prix des agricultures actuel"
        );

        $this->setPermissions([DefaultPermissions::ROOT_USER]);
    }

    public static function floating(): string
    {
        $text = Util::PREFIX . "Bourse" . Util::IARROW;

        foreach (Util::getBourse() as $item) {
            [$name, , , $sell] = explode(":", $item);
            $text .= "\n" . Util::caracterToUnicode("|") . " " . $name . ": §n" . $sell . "$ §8(§7" . Util::formatNumberWithSuffix(Cache::$data["bourse"][$name]) . "§8)" . Util::caracterToUnicode("|");
        }

        return $text;
    }

    public function onRun(CommandSender $sender, string $aliasUsed, array $args): void
    {
        $items = Util::getBourse();
        $bar = Util::stringToIcon("dark-bar");

        $sender->sendMessage($bar);

        foreach ($items as $item) {
            [$name, , , $sell] = explode(":", $item);
            $sender->sendMessage(Util::caracterToUnicode("|") . " " . $name . " " . Util::PREFIX . "Prix: §n" . $sell . "$ §8(§7" . Util::formatNumberWithSuffix(Cache::$data["bourse"][$name]) . "§8)");
        }

        $sender->sendMessage($bar);
    }

    protected function prepare(): void
    {
    }
}