<?php /** @noinspection PhpUnused */

namespace Kitmap\command\player;

use CortexPE\Commando\args\IntegerArgument;
use CortexPE\Commando\BaseCommand;
use Kitmap\Main;
use Kitmap\Session;
use Kitmap\Util;
use pocketmine\command\CommandSender;
use pocketmine\item\ExperienceBottle;
use pocketmine\item\VanillaItems;
use pocketmine\permission\DefaultPermissions;
use pocketmine\player\Player;

class XpBottle extends BaseCommand
{
    public function __construct()
    {
        parent::__construct(
            Main::getInstance(),
            "xpbottle",
            "Transforme ses niveaux en une seul bouteille d'expérience"
        );

        $this->setPermissions([DefaultPermissions::ROOT_USER]);
    }

    public function onRun(CommandSender $sender, string $aliasUsed, array $args): void
    {
        if ($sender instanceof Player) {
            $amount = $args["montant"] ?? $sender->getXpManager()->getXpLevel();
            $amount = intval($amount);

            $session = Session::get($sender);

            if ($amount < 1 || $amount > 1000) {
                $sender->sendMessage(Util::PREFIX . "Le montant indiqué est invalide");
                return;
            } else if ($amount > $sender->getXpManager()->getXpLevel()) {
                $sender->sendMessage(Util::PREFIX . "Vous n'avez pas assez de niveaux");
                return;
            } else if ($session->inCooldown("xp_bottle")) {
                $format = Util::formatDurationFromSeconds($session->getCooldownData("xp_bottle")[0] - time());
                $sender->sendMessage(Util::PREFIX . "Vous ne pourrez ré-utiliser la commande §q/xpbottle §fque dans: §q" . $format);
                return;
            }

            Util::addItem($sender, self::createXpBottle($amount));

            $sender->getXpManager()->setXpLevel($sender->getXpManager()->getXpLevel() - $amount);
            $sender->sendMessage(Util::PREFIX . "Vous avez crée une bouteille d'expérience avec §q" . $amount . " niveaux §fà l'intérieur");

            $session->setCooldown("xp_bottle", 60 * 5);
        }
    }

    public static function createXpBottle(int $level): ExperienceBottle
    {
        $item = VanillaItems::EXPERIENCE_BOTTLE();
        $item->getNamedTag()->setInt("xp_bottle", $level);
        $item->setCustomName("§r§fBouteille d'expérience §a(" . $level . ")");
        return $item;
    }

    protected function prepare(): void
    {
        $this->registerArgument(0, new IntegerArgument("montant", true));
    }
}