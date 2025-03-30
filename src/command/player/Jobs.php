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
                $form->addButton("§8" . $name . "§n: §8" . Api::getProgressBar($sender, $name, 1) . "\n" . Api::getProgressBar($sender, $name), -1, "", $name);
            }
            $sender->sendForm($form);
        }
    }

    private function jobInformation(Player $player, string $job): void
    {
        $form = new SimpleForm(null);
        $form->setTitle("Métiers");

        $label = Util::PREFIX . "§nMétier de " . ucfirst($job) . "\n\n";

        switch ($job) {
            case "Mineur":
                $label .= "§fPierre: §n1xp\n§fPierre taillée: §n1xp\n§fMinerai d'émeraude: §n5xp";
                break;
            case "Farmeur":
                $label .= "§fBlé: §n1-3xp\n§fCarrote: §n1-3xp\n§fBetterave: §n1-3xp\n§fPatate: §n1-3xp\n§fMelon: §n1-3xp\n§fCacao: §n1-3xp\n§fBambou: §n1xp\n§fCanne à sucre: §n1xp";
                break;
            case "Hunteur":
                $label .= "§fKill: §n50xp\n§fZombie: §n1-6xp\n§fPiglin: §n1-6xp";
                break;
        }

        $label .= "\n\n" . Util::PREFIX . "§nRécomponses:\n\n";

        foreach (Cache::$config["job"][$job] as $level => $data) {
            $label .= "\n§nNiveau " . intval($level + 1) . " §f: " . ucfirst($data["reward"]["name"]);
        }

        $form->setContent($label);
        $player->sendForm($form);
    }

    protected function prepare(): void
    {
    }
}