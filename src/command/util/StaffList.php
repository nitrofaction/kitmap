<?php /** @noinspection PhpUnused */

namespace Kitmap\command\util;

use CortexPE\Commando\BaseCommand;
use Kitmap\handler\Rank;
use Kitmap\Main;
use Kitmap\Util;
use pocketmine\command\CommandSender;
use pocketmine\permission\DefaultPermissions;
use pocketmine\player\Player;
use pocketmine\plugin\PluginBase;

class StaffList extends BaseCommand
{
    public function __construct(PluginBase $plugin)
    {
        parent::__construct(
            $plugin,
            "stafflist",
            "Récupère la liste des staffs connectés"
        );

        $this->setAliases(["sl"]);
        $this->setPermissions([DefaultPermissions::ROOT_USER]);
    }

    public function onRun(CommandSender $sender, string $aliasUsed, array $args): void
    {
        if ($sender instanceof Player && !$sender->hasPlayedBefore()) {
            $sender->sendMessage(Util::PREFIX . "Par mesure de securité vous ne pouvez actuellement pas voir les staffs en ligne");
            return;
        }

        $players = Main::getInstance()->getServer()->getOnlinePlayers();

        $staffs = array_filter($players, function (Player $player): bool {
            return Rank::isStaff(Rank::getRank($player->getName()));
        });

        $staffNames = array_map(function (Player $player): string {
            $rank = Rank::getRank($player->getName());

            return str_replace(
                ["{name}", "{fac}"],
                [$player->getName(), Rank::getRankValue($rank, "name")],
                Rank::getRankValue($rank, "gamertag")
            );
        }, $staffs);

        $list = implode("§f, ", $staffNames);
        $sender->sendMessage(Util::PREFIX . "Voici la liste des staffs connectés sur le serveur actuellement (§q" . count($staffNames) . "§f)\n" . $list);
    }

    protected function prepare(): void
    {
    }
}