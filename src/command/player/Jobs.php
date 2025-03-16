<?php /** @noinspection PhpUnused */

namespace Kitmap\command\player;

use CortexPE\Commando\BaseCommand;
use jojoe77777\FormAPI\SimpleForm;
use Kitmap\handler\Cache;
use Kitmap\handler\Job as Api;
use Kitmap\Util;
use pocketmine\command\CommandSender;
use pocketmine\permission\DefaultPermissions;
use pocketmine\player\Player;
use pocketmine\plugin\PluginBase;

class Jobs extends BaseCommand
{
    public function __construct(PluginBase $plugin)
    {
        parent::__construct(
            $plugin,
            "jobs",
            "Ouvre le menu des jobs"
        );

        $this->setAliases(["job", "metier", "metiers"]);
        $this->setPermissions([DefaultPermissions::ROOT_USER]);
    }

    public function onRun(CommandSender $sender, string $aliasUsed, array $args): void
    {
        if ($sender instanceof Player) {
            $form = new SimpleForm(function (Player $player, mixed $data) {
                if (!is_string($data) || !isset(Cache::$config["job"][$data])) {
                    return;
                }

                $this->jobInformation($player, $data);
            });
            $form->setTitle("Métiers");
            $form->setContent(Util::PREFIX . "Cliquez sur un métier pour avoir plus d'informations sur son propos");
            foreach (Cache::$config["job"] as $name => $data) {
                $form->addButton("§8" . $name . "§q: §8" . Api::getProgressBar($sender, $name, 1) . "\n" . Api::getProgressBar($sender, $name), -1, "", $name);
            }
            $sender->sendForm($form);
        }
    }

    private function jobInformation(Player $player, string $job): void
    {
        $form = new SimpleForm(null);
        $form->setTitle("Métiers");

        $label = Util::PREFIX . "§qMétier de " . ucfirst($job) . "\n\n";

        switch ($job) {
            case "Mineur":
                $label .= "§fPierre: §q1xp\n§fPierre taillée: §q1xp\n§fMinerai d'émeraude: §q5xp";
                break;
            case "Farmeur":
                $label .= "§fBlé: §q1-3xp\n§fCarrote: §q1-3xp\n§fBetterave: §q1-3xp\n§fPatate: §q1-3xp\n§fMelon: §q1-3xp\n§fBambou: §q1xp";
                break;
            case "Hunteur":
                $label .= "§fKill: §q50xp\n§fZombie: §q1-6xp\n§fPiglin: §q1-6xp";
                break;
        }

        $label .= "\n\n" . Util::PREFIX . "§qRécomponses:\n\n";

        foreach (Cache::$config["job"][$job] as $level => $data) {
            $label .= "\n§qNiveau " . intval($level + 1) . " §f: " . ucfirst($data["reward"]["name"]);
        }

        $form->setContent($label);
        $player->sendForm($form);
    }

    protected function prepare(): void
    {
    }
}