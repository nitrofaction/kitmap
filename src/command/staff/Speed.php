<?php /** @noinspection PhpUnused */

namespace Kitmap\command\staff;

use CortexPE\Commando\args\FloatArgument;
use CortexPE\Commando\BaseCommand;
use Kitmap\handler\Rank;
use Kitmap\Util;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;
use pocketmine\plugin\PluginBase;

class Speed extends BaseCommand
{
    public function __construct(PluginBase $plugin)
    {
        parent::__construct(
            $plugin,
            "speed",
            "Permet de multiplier sa vitesse dans les airs §e(S)"
        );

        $this->setPermissions([Rank::GROUP_STAFF]);
    }

    public function onRun(CommandSender $sender, string $aliasUsed, array $args): void
    {
        $speed = $args["vitesse"];

        if ($sender instanceof Player) {
            if (0.001 >= $speed || $speed > 99) {
                $sender->sendMessage(Util::PREFIX . "La vitesse indiqué n'est pas correcte elle doit être entre §n0.001 §fet §n99");
                return;
            }

            $sender->setFlightSpeedMultiplier($speed);
            $sender->sendMessage(Util::PREFIX . "Votre vitesse en vol a été multiplé par §n" . $speed);
        }
    }

    protected function prepare(): void
    {
        $this->registerArgument(0, new FloatArgument("vitesse"));
    }
}