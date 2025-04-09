<?php /** @noinspection PhpUnused */

namespace Kitmap\command\staff\op\dev;

use CortexPE\Commando\BaseCommand;
use Kitmap\handler\Cosmetic;
use Kitmap\handler\Rank;
use Kitmap\Util;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;
use pocketmine\plugin\PluginBase;

class SaveSkin extends BaseCommand
{
    public function __construct(PluginBase $plugin)
    {
        parent::__construct(
            $plugin,
            "saveskin",
            "À utiliser seulement si on connait l'usage /!\ §4(D)"
        );

        $this->setPermissions([Rank::GROUP_STAFF]);
    }

    public function onRun(CommandSender $sender, string $aliasUsed, array $args): void
    {
        if ($sender instanceof Player) {
            Cosmetic::saveSkin($sender, $sender->getSkin(), true);
            $sender->sendMessage(Util::PREFIX . "Skin saved");
        }
    }

    protected function prepare(): void
    {
    }
}