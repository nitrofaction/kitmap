<?php

namespace Kitmap\command\faction\subcommands\island;

use CortexPE\Commando\BaseSubCommand;
use Kitmap\command\faction\subcommands\Island;
use Kitmap\Main;
use pocketmine\command\CommandSender;
use pocketmine\permission\DefaultPermissions;
use pocketmine\player\Player;

class Teleport extends BaseSubCommand
{
    public function __construct()
    {
        parent::__construct(
            Main::getInstance(),
            "teleport",
            "Permet de se téléporter directement à son île"
        );

        $this->setAliases(["tp"]);
        $this->setPermissions([DefaultPermissions::ROOT_USER]);
    }

    public function onRun(CommandSender $sender, string $aliasUsed, array $args): void
    {
        if ($sender instanceof Player) {
            Island::tpForm($sender, null, true);
        }
    }

    protected function prepare(): void
    {
    }
}