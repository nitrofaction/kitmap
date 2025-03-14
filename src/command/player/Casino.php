<?php /** @noinspection PhpUnused */

namespace Kitmap\command\player;

use CortexPE\Commando\BaseCommand;
use Kitmap\handler\Casino as Api;
use pocketmine\command\CommandSender;
use pocketmine\permission\DefaultPermissions;
use pocketmine\player\Player;
use pocketmine\plugin\PluginBase;

class Casino extends BaseCommand
{
    public static array $coinflip = [];

    public function __construct(PluginBase $plugin)
    {
        parent::__construct(
            $plugin,
            "casino",
            "Ouvre le menu du casino"
        );

        $this->setAliases(["bet"]);
        $this->setPermissions([DefaultPermissions::ROOT_USER]);
    }

    public function onRun(CommandSender $sender, string $aliasUsed, array $args): void
    {
        if ($sender instanceof Player) {
            $sender->sendForm(Api::openCasinoForm());
        }
    }

    protected function prepare(): void
    {
    }
}