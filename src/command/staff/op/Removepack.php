<?php /** @noinspection PhpUnused */

namespace Kitmap\command\staff\op;

use CortexPE\Commando\args\IntegerArgument;
use CortexPE\Commando\args\RawStringArgument;
use CortexPE\Commando\args\TargetPlayerArgument;
use CortexPE\Commando\BaseCommand;
use Kitmap\Util;
use pocketmine\command\CommandSender;
use pocketmine\permission\DefaultPermissions;
use pocketmine\plugin\PluginBase;

class Removepack extends BaseCommand
{
    public function __construct(PluginBase $plugin)
    {
        parent::__construct(
            $plugin,
            "removepack",
            "Retire des packs à un ou des joueurs"
        );

        $this->setPermissions([DefaultPermissions::ROOT_OPERATOR]);
    }

    public function onRun(CommandSender $sender, string $aliasUsed, array $args): void
    {
        $amount = intval($args["montant"]);

        $player = Addvalue::addValue($sender, $this->getName(), $args);

        if (is_null($player)) {
            return;
        }

        $sender->sendMessage(Util::PREFIX . "Vous venez de retirer §q" . $amount . " §fpacks au joueur §q" . $player);
        Util::addValue($sender->getName(), $player, ["packs"], $amount, true);
    }

    protected function prepare(): void
    {
        $this->registerArgument(0, new TargetPlayerArgument(false, "joueur"));
        $this->registerArgument(0, new RawStringArgument("joueur"));
        $this->registerArgument(1, new IntegerArgument("montant"));
    }
}