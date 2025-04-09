<?php /** @noinspection PhpUnused */

namespace Kitmap\command\staff\op;

use CortexPE\Commando\args\OptionArgument;
use CortexPE\Commando\BaseCommand;
use Kitmap\handler\Pack;
use Kitmap\Main;
use Kitmap\Util;
use pocketmine\command\CommandSender;
use pocketmine\permission\DefaultPermissions;
use pocketmine\plugin\PluginBase;

class PrimeTime extends BaseCommand
{
    public function __construct(PluginBase $plugin)
    {
        parent::__construct(
            $plugin,
            "primetime",
            "Commande pour gérer le primetime §c(O)"
        );

        $this->setPermissions([DefaultPermissions::ROOT_OPERATOR]);
    }

    public function onRun(CommandSender $sender, string $aliasUsed, array $args): void
    {
        $opt = $args["opt"];

        switch ($opt) {
            case "start";
                Pack::$itemsAmount = 5;
                Main::getInstance()->getServer()->broadcastMessage(Util::PREFIX . "Le §nPRIME TIME §fcommence maintenant ! Jusqu'à §n21h§f, les packs donnent §n5 items §f!");
                break;
            case "end":
                Pack::$itemsAmount = 3;
                Main::getInstance()->getServer()->broadcastMessage(Util::PREFIX . "Le §nPRIME TIME §fest terminé ! Les packs donnent maintenant plus §n3 items §f! À demain !");
                break;
        }
    }

    protected function prepare(): void
    {
        $this->registerArgument(0, new OptionArgument("opt", ["start", "end"]));
    }
}