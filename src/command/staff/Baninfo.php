<?php /** @noinspection PhpUnused */

namespace Kitmap\command\staff;

use CortexPE\Commando\args\RawStringArgument;
use CortexPE\Commando\BaseCommand;
use Kitmap\handler\Cache;
use Kitmap\handler\Rank;
use Kitmap\Util;
use pocketmine\command\CommandSender;
use pocketmine\plugin\PluginBase;

class Baninfo extends BaseCommand
{
    public function __construct(PluginBase $plugin)
    {
        parent::__construct(
            $plugin,
            "baninfo",
            "Permet d'avoir les informations d'un joueur banni §e(S)"
        );

        $this->setPermissions([Rank::GROUP_STAFF]);
    }

    public function onRun(CommandSender $sender, string $aliasUsed, array $args): void
    {
        $player = strtolower($args["joueur"]);

        if (!isset(Cache::$bans[$player])) {
            $sender->sendMessage(Util::PREFIX . "Le joueur " . $player . " n'est pas banni (verifiez bien les caractères), le joueur peut être banni depuis une ip, un deviceId...");
            return;
        }

        $data = Cache::$bans[$player];
        $sender->sendMessage(Util::PREFIX . "Le joueur §n" . $player . " §fa été banni par §n" . $data[0] . "§f, raison: §n" . $data[2] . "§f, temps restant: §n" . Util::formatDurationFromSeconds($data[1] - time()));
    }

    protected function prepare(): void
    {
        $this->registerArgument(0, new RawStringArgument("joueur"));
    }
}