<?php /** @noinspection PhpUnused */

namespace Kitmap\command\staff\op;

use CortexPE\Commando\args\OptionArgument;
use CortexPE\Commando\BaseCommand;
use Kitmap\handler\Cache;
use Kitmap\Main;
use Kitmap\Util;
use pocketmine\command\CommandSender;
use pocketmine\permission\DefaultPermissions;
use pocketmine\plugin\PluginBase;

class Chat extends BaseCommand
{
    public function __construct(PluginBase $plugin)
    {
        parent::__construct(
            $plugin,
            "chat",
            "Permet d'activer ou désactiver le chat §c(O)"
        );

        $this->setPermissions([DefaultPermissions::ROOT_OPERATOR]);
    }

    public function onRun(CommandSender $sender, string $aliasUsed, array $args): void
    {
        switch ($args["opt"]) {
            case "disable":
                Cache::$data["chat"] = false;
                Main::getInstance()->getServer()->broadcastMessage(Util::PREFIX . "Le chat a été désactivé !");
                break;
            case "enable":
                Cache::$data["chat"] = true;
                Main::getInstance()->getServer()->broadcastMessage(Util::PREFIX . "Le chat a été réactivé !");
                break;
        }
    }

    protected function prepare(): void
    {
        $this->registerArgument(0, new OptionArgument("opt", ["disable", "enable"]));
    }
}