<?php /** @noinspection PhpUnused */

namespace Kitmap\listener;

use Kitmap\block\ExtraVanillaBlocks;
use Kitmap\command\player\rank\Enderchest;
use Kitmap\command\staff\{Ban, LastInventory, op\Question, Vanish};
use Kitmap\command\util\Bienvenue;
use Kitmap\entity\{AntiBackBall, EggTrap, floating\StuffFloating, npc\LogoutEntity, SwitchBall};
use Kitmap\entity\Player as CustomPlayer;
use Kitmap\handler\{Cache, Cosmetic, Faction, Job, PartnerItem, Rank, Sanction, WaterdogPacketHandler};
use Kitmap\item\Armor;
use Kitmap\item\enchantment\ExtraVanillaEnchantments;
use Kitmap\item\enchantment\Hammer;
use Kitmap\item\ExtraVanillaItems;
use Kitmap\Main;
use Kitmap\Session;
use Kitmap\task\repeat\child\GamblingTask;
use Kitmap\task\repeat\PlayerTask;
use Kitmap\Util;
use pocketmine\block\{Air,
    Anvil,
    Barrel,
    Block,
    BlockTypeTags,
    Cactus,
    CartographyTable,
    CaveVines,
    Chest,
    CraftingTable,
    Crops,
    Door,
    EnchantingTable,
    FenceGate,
    Fire,
    Furnace,
    GlowLichen,
    Hopper,
    inventory\EnderChestInventory,
    Liquid,
    SweetBerryBush,
    Trapdoor,
    VanillaBlocks,
    Water};
use pocketmine\block\tile\Chest as ChestTile;
use pocketmine\data\bedrock\EnchantmentIdMap;
use pocketmine\entity\object\ItemEntity;
use pocketmine\event\block\{BlockBreakEvent,
    BlockGrowEvent,
    BlockPlaceEvent,
    BlockSpreadEvent,
    BlockUpdateEvent,
    BrewingFuelUseEvent,
    BrewItemEvent,
    LeavesDecayEvent,
    SignChangeEvent};
use pocketmine\event\entity\{EntityDamageByEntityEvent,
    EntityDamageEvent,
    EntityItemPickupEvent,
    EntityShootBowEvent,
    EntityTeleportEvent,
    EntityTrampleFarmlandEvent,
    ItemSpawnEvent,
    ProjectileHitEntityEvent};
use pocketmine\event\inventory\{CraftItemEvent, InventoryOpenEvent, InventoryTransactionEvent, ItemDamageEvent};
use pocketmine\event\Listener;
use pocketmine\event\player\{PlayerBucketEvent,
    PlayerChangeSkinEvent,
    PlayerChatEvent,
    PlayerCreationEvent,
    PlayerDataSaveEvent,
    PlayerDeathEvent,
    PlayerDropItemEvent,
    PlayerExhaustEvent,
    PlayerInteractEvent,
    PlayerItemConsumeEvent,
    PlayerItemUseEvent,
    PlayerJoinEvent,
    PlayerJumpEvent,
    PlayerPreLoginEvent,
    PlayerQuitEvent,
    PlayerRespawnEvent,
    PlayerToggleSneakEvent};
use pocketmine\event\server\CommandEvent;
use pocketmine\event\server\DataPacketReceiveEvent;
use pocketmine\event\server\DataPacketSendEvent;
use pocketmine\event\world\ChunkLoadEvent;
use pocketmine\inventory\transaction\action\SlotChangeAction;
use pocketmine\item\{Axe, Bucket, Hoe, Item, PaintingItem, PotionType, Shovel, Stick, VanillaItems};
use pocketmine\math\Facing;
use pocketmine\math\Vector3;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\network\mcpe\protocol\StartGamePacket;
use pocketmine\permission\DefaultPermissions;
use pocketmine\player\{GameMode, Player};
use pocketmine\player\chat\LegacyRawChatFormatter;
use pocketmine\scheduler\ClosureTask;
use pocketmine\utils\TextFormat;
use pocketmine\world\Position;
use pocketmine\world\sound\EndermanTeleportSound;
use Symfony\Component\Filesystem\Path;

class EventListener implements Listener
{
    public function onCreation(PlayerCreationEvent $event): void
    {
        $event->setPlayerClass(CustomPlayer::class);
    }

    public function onChunkLoad(ChunkLoadEvent $event): void
    {
        if (!$event->isNewChunk()) {
            return;
        }

        $chunkX = $event->getChunkX();
        $chunkZ = $event->getChunkZ();

        if ($chunkX !== 16 || $chunkZ !== 16 || !str_starts_with($event->getWorld()->getFolderName(), "island-")) {
            return;
        }

        [$x, $y, $z] = explode(":", Cache::$config["pos"]["island"][$event->getWorld()->getProvider()->getWorldData()->getGenerator()]["chest"]);

        $vector = new Vector3(intval($x), intval($y), intval($z));
        $tile = $event->getWorld()->getTile($vector);

        if ($tile instanceof ChestTile) {
            return;
        } else {
            $chest = new ChestTile($event->getWorld(), $vector);

            $chest->getInventory()->addItem(VanillaItems::WATER_BUCKET()->setCount(1));
            $chest->getInventory()->addItem(VanillaItems::LAVA_BUCKET()->setCount(1));
            $chest->getInventory()->addItem(VanillaBlocks::ICE()->asItem()->setCount(4));
            $chest->getInventory()->addItem(VanillaItems::BEETROOT_SEEDS()->setCount(7));
            $chest->getInventory()->addItem(VanillaItems::WHEAT_SEEDS()->setCount(9));
            $chest->getInventory()->addItem(VanillaItems::POTATO()->setCount(3));
            $chest->getInventory()->addItem(VanillaItems::BONE()->setCount(16));
            $chest->getInventory()->addItem(VanillaItems::BAMBOO()->setCount(25));
            $chest->getInventory()->addItem(VanillaBlocks::BARREL()->asItem()->setCount(2));

            $event->getWorld()->addTile($chest);
        }
    }

    public function onInteract(PlayerInteractEvent $event): void
    {
        $player = $event->getPlayer();

        $block = $event->getBlock();
        $item = $event->getItem();

        if (
            $event->getAction() === $event::RIGHT_CLICK_BLOCK &&
            (($block instanceof CaveVines || $block instanceof Door || $block instanceof Trapdoor || $block instanceof FenceGate || $block instanceof Furnace || $block instanceof SweetBerryBush || $block instanceof GlowLichen || $block instanceof CraftingTable || $block instanceof CartographyTable || $block instanceof Chest || $block instanceof Barrel || $block instanceof Hopper) || ($item instanceof Bucket || $item instanceof Hoe || $item instanceof Axe || $item instanceof Shovel || $item instanceof PaintingItem || $item instanceof Stick)) &&
            !Faction::canBuild($player, $block, "interact") &&
            !(Util::insideZone($player->getPosition(), "spawn") && ($block instanceof CraftingTable || $block instanceof Anvil || $block instanceof EnchantingTable))
        ) {
            $event->cancel();

            if ($block instanceof Door || $block instanceof Trapdoor || $block instanceof FenceGate) {
                Util::antiBlockGlitch($player);
            }

            return;
        } else if ($event->getAction() === $event::LEFT_CLICK_BLOCK) {
            Hammer::$faces[$event->getPlayer()->getXuid()] = $event->getFace();
        }

        if (!ExtraVanillaItems::getItem($item)->onInteract($event)) {
            ExtraVanillaBlocks::getBlock($block)->onInteract($event);
        }
    }

    public function onChat(PlayerChatEvent $event): void
    {
        $player = $event->getPlayer();
        $message = TextFormat::clean($event->getMessage());

        $session = Session::get($player);

        if (str_contains($message, "@here") && !$player->hasPermission(DefaultPermissions::ROOT_OPERATOR)) {
            $event->cancel();
            $player->sendMessage(Util::PREFIX . "Vous ne pouvez pas utiliser §n@here §fdans votre message");
            return;
        }

        if (Question::$currentEvent !== 0) {
            $valid = false;

            switch (Question::$currentEvent) {
                case 1:
                    if ($event->getMessage() === Question::$currentReply) {
                        Main::getInstance()->getServer()->broadcastMessage(Util::PREFIX . "§n" . $player->getDisplayName() . " §fa gagné §n5k$ §fen ayant réécrit le code §n" . Question::$currentReply . " §fen premier !");
                        $valid = true;
                    }
                    break;
                case 2:
                    if (strtolower($event->getMessage()) === Question::$currentReply) {
                        Main::getInstance()->getServer()->broadcastMessage(Util::PREFIX . "§n" . $player->getDisplayName() . " §fa gagné §n5k$ §fen ayant trouver le mot §n" . Question::$currentReply . " §fen premier !");
                        $valid = true;
                    }
                    break;
                case 3:
                    if ($event->getMessage() === strval(Question::$currentReply)) {
                        Main::getInstance()->getServer()->broadcastMessage(Util::PREFIX . "§n" . $player->getDisplayName() . " §fa gagné §n5k$ §fen ayant répondu au calcul §n" . Question::$currentReply . " §fen premier !");
                        $valid = true;
                    }
                    break;
            }

            if ($valid) {
                $event->cancel();
                $session->addValue("money", 5000);

                Question::$currentEvent = 0;
                Question::$currentReply = null;
            }
        }

        if ($session->inCooldown("chat")) {
            $event->cancel();
        } else {
            if (!$player->hasPermission(DefaultPermissions::ROOT_OPERATOR)) {
                $session->setCooldown("chat", 2);
            }
        }

        $rank = Rank::getRank($player->getName());

        if (($session->data["faction_chat"] || $event->getMessage()[0] === "-") && Faction::hasFaction($player)) {
            if (!$session->data["faction_chat"]) {
                $message = substr($message, 1);
            }

            $faction = $session->data["faction"];
            $event->cancel();

            Main::getInstance()->getLogger()->info("[F] [" . $faction . "] " . $player->getName() . " » " . $message);
            Faction::broadcastFactionMessage($faction, $player->getName() . " " . Util::PREFIX . $message, $session->data["ally_chat"]);

            return;
        } else if ($event->getMessage()[0] === "&" && Faction::hasFaction($player)) {
            $message = substr($message, 1);

            $faction = $session->data["faction"];
            $event->cancel();

            Main::getInstance()->getLogger()->info("[F] [" . $faction . "] " . $player->getName() . " » " . $message);
            Faction::broadcastFactionMessage($faction, $player->getName() . " " . Util::PREFIX . $message, true);

            return;
        } else if ($event->getMessage()[0] === "!" && Rank::isStaff($rank)) {
            $message = substr($message, 1);

            Main::getInstance()->getLogger()->info("[S] " . $player->getName() . " » " . $message);

            foreach (Main::getInstance()->getServer()->getOnlinePlayers() as $onlinePlayer) {
                if (Rank::isStaff(Rank::getRank($onlinePlayer->getName()))) {
                    $onlinePlayer->sendMessage("§n[§fS§n] §f" . $player->getName() . " " . Util::PREFIX . $message);
                }
            }

            $event->cancel();
            return;
        } else if ($session->inCooldown("mute")) {
            $format = Util::formatDurationFromSeconds($session->getCooldownData("mute")[0] - time());
            $player->sendMessage(Util::PREFIX . "Vous êtes mute, temps restant: §n" . $format);

            $event->cancel();
            return;
        } else if (!(Cache::$data["chat"] ?? true) && !$player->hasPermission(DefaultPermissions::ROOT_OPERATOR)) {
            $player->sendMessage(Util::PREFIX . "Le chat est actuellement désactivé !");
            return;
        }

        $rank = ($player->getName() === $player->getDisplayName()) ? $rank : "joueur";
        $message = Rank::setReplace(Rank::getRankValue($rank, "chat"), $player, $message);

        $event->setFormatter(new LegacyRawChatFormatter($message));
    }

    public function onJoin(PlayerJoinEvent $event): void
    {
        $player = $event->getPlayer();
        $session = Session::get($player);

        $event->setJoinMessage("");

        if (Ban::checkBan($event)) {
            return;
        }

        Main::getInstance()->getServer()->broadcastTip("§a+ " . $player->getName() . " +");

        if (Faction::hasFaction($player)) {
            Cache::$factions[$session->data["faction"]]["activity"][date("m-d")] = $player->getName();
            Faction::broadcastFactionMessage($session->data["faction"], "Le joueur de votre faction §n" . $player->getName() . " §fvient de se connecter");
        }

        foreach (Vanish::$vanish as $target) {
            $target = Main::getInstance()->getServer()->getPlayerExact($target);

            if ($target instanceof Player) {
                if ($target->hasPermission(Rank::GROUP_STAFF) || $target->getName() === $player->getName()) {
                    continue;
                }
                $target->hidePlayer($player);
            }
        }

        if (!$player->hasPlayedBefore()) {
            $path = Path::join(Main::getInstance()->getServer()->getDataPath(), "players");
            $count = count(glob($path . "/*")) + 1;

            Main::getInstance()->getServer()->broadcastMessage(Util::PREFIX . "§n" . $player->getName() . " §fa rejoint le serveur pour la §npremière §ffois ! Souhaitez lui la §nbienvenue §favec la commande §n/bvn §f(#§n" . $count . "§f)!");

            Bienvenue::$alreadyWished = [];
            Bienvenue::$lastJoin = $player->getName();
        }

        /*$player->getArmorInventory()->getListeners()->add(new CallbackInventoryListener(function (Inventory $inventory, int $slot, Item $oldItem): void {
            if ($inventory instanceof ArmorInventory) {
                $targetItem = $inventory->getItem($slot);

                ExtraVanillaItems::getItem($oldItem)->removeEffects($inventory, $oldItem);
                ExtraVanillaItems::getItem($targetItem)->addEffects($inventory, $targetItem);
            }
        }, null));*/

        $bar = Util::stringToIcon("dark-bar");
        $player->sendMessage($bar);
        $player->sendMessage("§fBienvenue, §n" . $player->getName() . "!");
        $player->sendMessage(Util::caracterToUnicode("down-right-arrow") . " §fUtilisez §n/lobby §fpour revenir au lobby");
        $player->sendMessage("§r ");
        $player->sendMessage(Util::PREFIX . "Discord: §ndiscord.gg/nitrofaction");
        $player->sendMessage(Util::PREFIX . "Boutique: §nstore.nitrofaction.fr");
        $player->sendMessage($bar);

        Util::givePlayerPreferences($player);

        Rank::updateNameTag($player);
        Rank::addPermissions($player);

        Cosmetic::checkSkin($player);
    }

    public function onChangeSkin(PlayerChangeSkinEvent $event): void
    {
        $skin = Cosmetic::checkSkin($event->getPlayer(), $event->getNewSkin());
        $event->setNewSkin($skin);
    }

    public function onEntityTeleport(EntityTeleportEvent $event): void
    {
        $entity = $event->getEntity();

        $from = $event->getFrom();
        $to = $event->getTo();

        if (!$entity instanceof Player) {
            return;
        }

        if (
            str_starts_with($from->getWorld()->getFolderName(), "island-") &&
            !str_starts_with($to->getWorld()->getFolderName(), "island-")
        ) {
            if (!Session::get($entity)->data["staff_mod"][0] && !$entity->isCreative()) {
                $entity->setFlying(false);
                $entity->setAllowFlight(false);
            }
        } else if (
            $from->getWorld()->getFolderName() !== $to->getWorld()->getFolderName() &&
            str_starts_with($to->getWorld()->getFolderName(), "island-")
        ) {
            $faction = substr($to->getWorld()->getFolderName(), 7);
            $bar = Util::stringToIcon("dark-bar");

            $entity->sendMessage($bar);
            $entity->sendMessage(Util::PREFIX . "Bienvenue sur l'ile de la §n" . Faction::getFactionUpperName($faction));
            $entity->sendMessage(Util::PREFIX . "Niveau de l'île : §n" . Faction::getIslandLevel($faction));
            $entity->sendMessage(Util::PREFIX . "Cactus stockés : §n" . Faction::getCactus($faction) . "§f / §n" . Faction::getCactusLimit($faction));
            $entity->sendMessage(Util::PREFIX . "Limite de mobs : §n" . Faction::getIslandMobsLimit($faction));
            $entity->sendMessage($bar);
        }
    }

    /*public function onMeltEvent(BlockMeltEvent $event): void
    {
        if ($event->getBlock()->getPosition()->getWorld() === Main::getInstance()->getServer()->getWorldManager()->getDefaultWorld()) {
            $event->cancel();
        }
    }*/

    public function onRespawn(PlayerRespawnEvent $event): void
    {
        $player = $event->getPlayer();

        $xp = Cache::$deathXp[$player->getName()] ?? 0;
        $blades = Cache::$infiniteBlade[$player->getXuid()] ?? [];

        $player->getXpManager()->setCurrentTotalXp(intval($xp));

        foreach ($blades as $item) {
            $player->getInventory()->addItem($item);
        }

        Cache::$deathXp[$player->getName()] = 0;
        Cache::$infiniteBlade[$player->getXuid()] = [];

        Util::givePlayerPreferences($event->getPlayer());
    }

    public function onQuit(PlayerQuitEvent $event): void
    {
        $player = $event->getPlayer();
        Util::removeCurrentWindow($player);

        Main::getInstance()->getServer()->broadcastTip("§c- " . $player->getName() . " -");
        $event->setQuitMessage("");

        if (in_array($player->getName(), GamblingTask::$players)) {
            $ev = new PlayerDeathEvent($player, [], 0, "");
            $ev->call();
        } else if (Util::getTpTime($player) > 0) {
            $entity = new LogoutEntity($player->getLocation(), $player->getSkin());
            $entity->initEntityB($player);
            $entity->spawnToAll();
        }

        Session::get($player)->saveSessionData();
    }

    public function onDeath(PlayerDeathEvent $event): void
    {
        $player = $event->getPlayer();
        $session = Session::get($player);

        $event->setDeathMessage("");
        $session->removeCooldown("combat");

        if (in_array($player->getName(), GamblingTask::$players)) {
            $otherPlayerName = (GamblingTask::$players[0] === $player->getName()) ? GamblingTask::$players[1] : GamblingTask::$players[0];
            GamblingTask::stop($otherPlayerName);

            if (Faction::hasFaction($player)) {
                Faction::addPower($session->data["faction"], -4);
            }

            $cause = $player->getLastDamageCause();

            if (!$cause instanceof EntityDamageByEntityEvent) {
                return;
            }

            $damager = $cause->getDamager();

            if ($damager instanceof Player && Faction::hasFaction($damager)) {
                $damagerSession = Session::get($damager);
                Faction::addPower($damagerSession->data["faction"], 6);
            }

            return;
        }

        $infiniteEnchant = EnchantmentIdMap::getInstance()->fromId(ExtraVanillaEnchantments::INFINITE);

        foreach ($event->getDrops() as $item) {
            if ($item->hasEnchantment($infiniteEnchant)) {
                Cache::$infiniteBlade[$player->getXuid()] ??= [];
                Cache::$infiniteBlade[$player->getXuid()][] = $item;
            }
        }

        $event->setDrops(array_filter($event->getDrops(), function (Item $item) use ($infiniteEnchant) {
            return !$item->hasEnchantment($infiniteEnchant);
        }));

        $session->addValue("death");
        $playerBounty = $session->data["bounty"];

        if ($playerBounty > 0) {
            $session->addValue("bounty", $playerBounty, true);
            Util::updateBounty($player);
        }

        $rank = Rank::getEqualRankBySession($session);
        $keepXp = Rank::getRankValue($rank, "xp");

        $killstreak = $session->data["killstreak"];
        $session->data["killstreak"] = 0;

        Cache::$deathXp[$player->getName()] = intval($player->getXpManager()->getCurrentTotalXp() * ($keepXp / 100));
        $event->setXpDropAmount(intval($event->getXpDropAmount() * ((100 - $keepXp) / 100)));

        $cause = $player->getLastDamageCause();

        if ($cause instanceof EntityDamageByEntityEvent) {
            $damager = $cause->getDamager();

            if ($damager instanceof Player) {
                LastInventory::saveOnlineInventory($player, $damager, $killstreak);
                StuffFloating::lockStuff($player, $damager, $event);

                $pot1 = Util::getItemCount($player, VanillaItems::SPLASH_POTION()->setType(PotionType::STRONG_HEALING()));
                $pot2 = Util::getItemCount($damager, VanillaItems::SPLASH_POTION()->setType(PotionType::STRONG_HEALING()));

                Main::getInstance()->getLogger()->info($player->getDisplayName() . " (" . $player->getName() . ") a été tué par " . $damager->getDisplayName() . " (" . $damager->getName() . ")");
                Main::getInstance()->getServer()->broadcastMessage(Util::PREFIX . "§n" . $player->getDisplayName() . "[§7" . $pot1 . "§n] §fa été tué par le joueur §n" . $damager->getDisplayName() . "[§7" . $pot2 . "§n]");

                $damagerSession = Session::get($damager);

                $damagerSession->addValue("kill");
                $damagerSession->addValue("killstreak");

                if (Faction::hasFaction($damager)) Faction::addPower($damagerSession->data["faction"], 6);
                if (Faction::hasFaction($player)) Faction::addPower($session->data["faction"], -4);

                $damagerKillstreak = $damagerSession->data["killstreak"];

                if ($playerBounty > 0) {
                    $damagerSession->addValue("money", $playerBounty);
                    Main::getInstance()->getServer()->broadcastMessage(Util::PREFIX . "§n" . $damager->getName() . " §fvient de remporter une prime de §n" . $playerBounty . " pièce(s) §fen tuant §n" . $player->getName() . " §f!");
                }

                if ($damagerKillstreak % 5 == 0) {
                    $amount = Cache::$config["bounties"][array_rand(Cache::$config["bounties"])];
                    $damagerSession->addValue("bounty", $amount);

                    Main::getInstance()->getServer()->broadcastMessage(Util::PREFIX . "§n" . $damager->getName() . " §fa fait §n" . $damagerSession->data["killstreak"] . " §fkills sans mourrir ! Sa mort est désormais mise à prix à §n" . Session::get($damager)->data["bounty"] . " pièce(s) §8(§7+" . $amount . "§8) §f!");
                }

                $lossElo = mt_rand(1, 5);
                $winElo = mt_rand(3, 8);

                $session->addValue("elo", $lossElo, true);
                $damagerSession->addValue("elo", $winElo);

                $player->sendMessage(Util::PREFIX . "Vous venez de perdre §c" . $lossElo . " §felo !");
                $damager->sendMessage(Util::PREFIX . "Vous venez de gagner §n" . $winElo . " §felo !");

                Job::addXp($damager, "Assassin", 50 + $damagerSession->data["killstreak"]);

                foreach ($damager->getInventory()->getItemInHand()->getEnchantments() as $enchant) {
                    ExtraVanillaEnchantments::getEnchantment($enchant->getType())->onKill($event, $enchant, $damager, $player);
                }
                return;
            }
        } else {
            Main::getInstance()->getLogger()->info($player->getDisplayName() . " (" . $player->getName() . ") est mort");
        }

        LastInventory::saveOnlineInventory($player, null, $killstreak);
    }

    public function onBow(EntityShootBowEvent $event): void
    {
        $event->cancel();
    }

    public function onItemDamage(ItemDamageEvent $event): void
    {
        ExtraVanillaItems::getItem($event->getItem())->onDamage($event);
    }

    public function onDamage(EntityDamageEvent $event): void
    {
        $entity = $event->getEntity();

        if ($entity instanceof ItemEntity) {
            if ($event->getCause() === $event::CAUSE_CONTACT && $entity->getItem()->getTypeId() === VanillaBlocks::CACTUS()->asItem()->getTypeId()) {
                $entity->setMotion(new Vector3(0.3, 0.3, 0.3));
                $event->cancel();
            } else if ($event->getCause() === $event::CAUSE_VOID) {
                $entity->teleport($entity->getWorld()->getSpawnLocation());
                $event->cancel();
            }

            return;
        } else if ($event->getModifier(EntityDamageEvent::MODIFIER_PREVIOUS_DAMAGE_COOLDOWN) < 0.0) {
            $event->cancel();
            return;
        }

        if ($event instanceof EntityDamageByEntityEvent) {
            $damager = $event->getDamager();

            if ($damager instanceof Player && ExtraVanillaItems::getItem($damager->getInventory()->getItemInHand())->onAttack($event)) {
                return;
            }
        }

        if (!$entity instanceof Player) {
            return;
        }

        Armor::applyDamageModifiers($event, $entity);

        $entitySession = Session::get($entity);

        if ($event->getCause() === EntityDamageEvent::CAUSE_VOID) {
            $entity->teleport($entity->getPosition()->getWorld()->getSpawnLocation());
            $event->cancel();

            return;
        } else if (
            $event->getCause() === EntityDamageEvent::CAUSE_FALL ||
            $event->getCause() === EntityDamageEvent::CAUSE_SUFFOCATION ||
            (Util::insideZone($entity->getPosition(), "spawn") && !in_array($entity->getName(), GamblingTask::$players)) ||
            $entitySession->data["staff_mod"][0] ||
            str_starts_with($entity->getPosition()->getWorld()->getFolderName(), "island-") ||
            $entity->getPosition()->getWorld()->getFolderName() === "mine"
        ) {
            $event->cancel();
        }

        if ($event instanceof EntityDamageByEntityEvent) {
            $damager = $event->getDamager();

            if ($damager instanceof Player) {
                if (Util::insideZone($damager->getPosition(), "spawn") && !in_array($damager->getName(), GamblingTask::$players)) {
                    $event->cancel();
                }

                $damagerSession = Session::get($damager);

                if ($damagerSession->data["staff_mod"][0]) {
                    $message = match ($damager->getInventory()->getItemInHand()->getCustomName()) {
                        "§r" . Util::PREFIX . "Sanction" . Util::IARROW => "custom",
                        "§r" . Util::PREFIX . "Alias" . Util::IARROW => "/alias \"" . $entity->getName() . "\"",
                        "§r" . Util::PREFIX . "Freeze" . Util::IARROW => "/freeze \"" . $entity->getName() . "\"",
                        "§r" . Util::PREFIX . "Invsee" . Util::IARROW => "/invsee \"" . $entity->getName() . "\"",
                        "§r" . Util::PREFIX . "Ecsee" . Util::IARROW => "/ecsee \"" . $entity->getName() . "\"",
                        default => null
                    };

                    if ($message === "custom") {
                        if ($damager->getInventory()->getItemInHand()->getCustomName() === "§r" . Util::PREFIX . "Knockback 2" . Util::IARROW) {
                            return;
                        }

                        Sanction::chooseSanction($damager, $entity->getName());
                    } else {
                        if (!is_null($message)) {
                            $damager->chat($message);
                        } else {
                            $damager->sendMessage("Vous venez de taper le joueur §n" . $entity->getName());
                        }
                    }

                    $event->cancel();
                    return;
                }

                if (
                    $event->isCancelled() ||
                    (
                        Faction::hasFaction($damager) && Faction::hasFaction($entity) &&
                        ($damagerSession->data["faction"] === $entitySession->data["faction"] || Faction::getAlly($damagerSession->data["faction"]) === $entitySession->data["faction"])
                    ) ||
                    $entity->isFlying() ||
                    $entity->getAllowFlight()
                ) {
                    $event->cancel();
                    return;
                }

                if ($entity->getGamemode() === GameMode::CREATIVE() || $damager->getGamemode() === GameMode::CREATIVE() || $entity->hasNoClientPredictions()) {
                    goto skip;
                }

                PartnerItem::executeHitPartnerItem($damager, $entity);

                $damagerSession->setCooldown("combat", 20, [$entity->getName()]);
                $entitySession->setCooldown("combat", 20, [$damager->getName()]);

                $event->setKnockback(0.38);
                $event->setAttackCooldown(8);

                $damagerSession->data["last_hit"] = [$entity->getName(), time()];

                if ($entitySession->inCooldown("_focusmode") && $damager->getName() === $entitySession->getCooldownData("_focusmode")[1]) {
                    $event->setBaseDamage($event->getBaseDamage() + (($event->getBaseDamage() / 100) * 10));
                }

                Util::updateBounty($entity);
                Util::updateBounty($damager);

                foreach ($damager->getInventory()->getItemInHand()->getEnchantments() as $enchant) {
                    ExtraVanillaEnchantments::getEnchantment($enchant->getType())->onAttack($event, $enchant, $damager);
                }
            }
        }

        skip:

        if (!$event->isCancelled() && in_array($entity->getName(), GamblingTask::$players) && $event->getFinalDamage() >= $entity->getHealth()) {
            $ev = new PlayerDeathEvent($entity, [], 0, "");
            $ev->call();

            $event->setBaseDamage(0);
        }
    }

    public function onJump(PlayerJumpEvent $event): void
    {
        $player = $event->getPlayer();

        $x = $player->getPosition()->getFloorX();
        $y = $player->getPosition()->getFloorY() - 1;
        $z = $player->getPosition()->getFloorZ();

        $block = $player->getPosition()->getWorld()->getBlockAt($x, $y, $z);
        ExtraVanillaBlocks::getBlock($block)->onJump($event);
    }

    public function onSneak(PlayerToggleSneakEvent $event): void
    {
        $player = $event->getPlayer();

        $x = $player->getPosition()->getFloorX();
        $y = $player->getPosition()->getFloorY() - 1;
        $z = $player->getPosition()->getFloorZ();

        $block = $player->getPosition()->getWorld()->getBlockAt($x, $y, $z);
        ExtraVanillaBlocks::getBlock($block)->onSneak($event);
    }

    public function onExhaust(PlayerExhaustEvent $event): void
    {
        $event->getPlayer()->getHungerManager()->setExhaustion(2.5);
        $event->getPlayer()->getHungerManager()->setFood(18);
    }

    /**
     * @handleCancelled
     */
    public function onUse(PlayerItemUseEvent $event): void
    {
        $player = $event->getPlayer();
        $item = $event->getItem();

        $session = Session::get($player);

        if ($session->data["staff_mod"][0]) {
            $command = match ($item->getCustomName()) {
                "§r" . Util::PREFIX . "Vanish" . Util::IARROW => "/vanish",
                "§r" . Util::PREFIX . "Random Tp" . Util::IARROW => "/randomtp",
                "§r" . Util::PREFIX . "Spectateur" . Util::IARROW => "/spec",
                default => null
            };

            if ($command !== null) {
                $player->chat($command);
            }
        }

        if ($event->isCancelled()) {
            return;
        }

        $executePp = PartnerItem::executeInteractPartnerItem($player, $event);

        if ($executePp) {
            return;
        } else if ($item->equals(VanillaItems::SNOWBALL())) {
            $event->cancel();
            return;
        }

        ExtraVanillaItems::getItem($item)->onUse($event);
    }

    public function onPick(EntityItemPickupEvent $event): void
    {
        $entity = $event->getEntity();
        $origin = $event->getOrigin();

        if ($entity instanceof Player) {
            if (Session::get($entity)->data["staff_mod"][0] || in_array($entity->getName(), Vanish::$vanish)) {
                $event->cancel();
                return;
            } else if (
                $origin instanceof ItemEntity &&
                $entity->getWorld() === Main::getInstance()->getServer()->getWorldManager()->getDefaultWorld() &&
                !ExtraVanillaItems::getItem($event->getItem())->isRare() &&
                30 > $origin->ticksLived
            ) {
                $event->cancel();
                return;
            }

            $item = $event->getItem();

            if (!is_null($item->getNamedTag()->getTag("menu_item"))) {
                $event->cancel();
                return;
            } else if (is_null($item->getNamedTag()->getTag("lockWhile"))) {
                return;
            }

            $lockBy = $item->getNamedTag()->getString("lockBy");
            $time = $item->getNamedTag()->getInt("lockWhile");

            if ($time > time() && $entity->getName() !== $lockBy) {
                $event->cancel();
            } else {
                $item->getNamedTag()->removeTag("lockBy");
                $item->getNamedTag()->removeTag("lockWhile");
            }
        }
    }

    public function onTransaction(InventoryTransactionEvent $event): void
    {
        $transaction = $event->getTransaction();
        $player = $transaction->getSource();

        $staff = Session::get($player)->data["staff_mod"][0];

        foreach ($transaction->getActions() as $action) {
            $sourceItem = $action->getSourceItem();
            $targetItem = $action->getTargetItem();

            if ($action instanceof SlotChangeAction && ($staff || $player->hasNoClientPredictions())) {
                $event->cancel();
                return;
            }

            $nbt = ($sourceItem->getNamedTag() ?? new CompoundTag());
            $_nbt = ($targetItem->getNamedTag() ?? new CompoundTag());

            foreach ($transaction->getInventories() as $inventory) {
                if ($inventory instanceof EnderChestInventory) {
                    if (($nbt->getTag("enderchest_slots") && $nbt->getString("enderchest_slots") === "restricted") || ($_nbt->getTag("enderchest_slots") && $_nbt->getString("enderchest_slots") === "restricted")) {
                        $event->cancel();
                        return;
                    }
                }
            }
        }
    }

    public function onOpenInventory(InventoryOpenEvent $event): void
    {
        $player = $event->getPlayer();
        $inventory = $event->getInventory();

        if ($inventory instanceof EnderChestInventory) {
            Enderchest::setEnderchestGlass($player, $inventory);
        }
    }

    public function onPlace(BlockPlaceEvent $event): void
    {
        $player = $event->getPlayer();
        $block = null;

        if (Session::get($player)->data["staff_mod"][0]) {
            $event->cancel();
            return;
        }

        foreach ($event->getTransaction()->getBlocks() as [$x, $y, $z, $transactionBlock]) {
            $block = $transactionBlock;

            if (!Faction::canBuild($player, $transactionBlock, "place")) {
                Util::antiBlockGlitch($player);

                $event->cancel();
                return;
            }
        }

        if ($block instanceof Block) {
            ExtraVanillaBlocks::getBlock($block)->onPlace($event);
        }
    }

    public function onSpread(BlockSpreadEvent $event): void
    {
        $source = $event->getSource();

        $sourcePos = $source->getPosition();
        $blockPos = $event->getBlock()->getPosition();

        if ($source instanceof Fire || $event->getBlock() instanceof Fire) {
            $event->cancel();
            return;
        }

        /*if ($source instanceof Lava && $sourcePos->getY() !== $blockPos->getY()) {
            $event->cancel();
        }*/

        if ($source instanceof Liquid) {
            if ($event->getBlock()->hasSameTypeId(VanillaBlocks::NETHER_WART())) {
                $event->cancel();
                return;
            }

            if ($blockPos->getWorld() === Main::getInstance()->getServer()->getWorldManager()->getDefaultWorld()) {
                if (Faction::inClaim($sourcePos->getX(), $sourcePos->getZ()) !== Faction::inClaim($blockPos->getX(), $blockPos->getZ())) {
                    $event->cancel();
                }
            }
        }
    }

    public function onBucket(PlayerBucketEvent $event): void
    {
        $player = $event->getPlayer();

        if (!Faction::canBuild($player, $event->getBlockClicked(), "place")) {
            $event->cancel();
        } else if (Session::get($player)->data["staff_mod"][0]) {
            $event->cancel();
        }
    }

    public function onTrampleFarmland(EntityTrampleFarmlandEvent $event): void
    {
        $event->cancel();
    }

    public function onGrow(BlockGrowEvent $event): void
    {
        $oldState = $event->getBlock();

        if ($oldState->getPosition()->getWorld()->getFolderName() === "mine") {
            $event->cancel();
            return;
        }

        $newState = $event->getNewState();

        if (!$newState instanceof Cactus) {
            return;
        }

        $position = $oldState->getPosition();
        $world = $position->getWorld();

        $supportBlock = $world->getBlockAt($position->x, $position->y - 1, $position->z);

        if (!$supportBlock->hasSameTypeId($newState) && !$supportBlock->hasTypeTag(BlockTypeTags::SAND)) {
            Faction::addCactus($event);
            return;
        }

        foreach (Facing::HORIZONTAL as $side) {
            /** @noinspection PhpDeprecationInspection */
            if ($oldState->getSide($side)->isSolid()) {
                Faction::addCactus($event);
            }
        }
    }

    public function onUpdate(BlockUpdateEvent $event): void
    {
        if ($event->getBlock()->getPosition()->getWorld()->getFolderName() === "mine" && !$event->getBlock() instanceof Water) {
            $event->cancel();
        }
    }

    public function onDrop(PlayerDropItemEvent $event): void
    {
        if (in_array($event->getPlayer()->getName(), GamblingTask::$players)) {
            $event->cancel();
        }
    }

    public function onCraft(CraftItemEvent $event): void
    {
        $input = $event->getInputs();
        $player = $event->getPlayer();

        foreach ($input as $item) {
            if (!is_null($item->getNamedTag()->getTag("partneritem"))) {
                $event->cancel();
                Util::removeCurrentWindow($player);

                $player->sendMessage(Util::PREFIX . "Vous ne pouvez pas utiliser des partner-items pour craft des items ou autre");
                break;
            } else if (!is_null($item->getNamedTag()->getTag("menu_item"))) {
                $event->cancel();
                Util::removeCurrentWindow($player);
                break;
            }
        }
    }

    /*public function onSupportBreak(BlockSupportBreakEvent $event): void
    {
        ExtraVanillaBlocks::getBlock($event->getBlock())->onSupportBreak($event);
    }*/


    public function onBreak(BlockBreakEvent $event): void
    {
        $player = $event->getPlayer();
        $block = $event->getBlock();

        $session = Session::get($player);

        if ($session->data["staff_mod"][0]) {
            $event->cancel();
            return;
        } else if ($player->getPosition()->getWorld()->getFolderName() !== "mine" && !Faction::canBuild($player, $block, "break")) {
            if ($block->isFullCube()) {
                Util::antiBlockGlitch($player);
            }

            $event->cancel();
            return;
        }

        if (
            $session->data["cobblestone"] === false &&
            ($block->hasSameTypeId(VanillaBlocks::COBBLESTONE()) || $block->hasSameTypeId(VanillaBlocks::STONE()))
        ) {
            $event->setDrops([]);
        }

        if (ExtraVanillaItems::getItem($event->getItem())->onBreak($event)) {
            return;
        } else if (ExtraVanillaBlocks::getBlock($event->getBlock())->onBreak($event)) {
            return;
        }

        if ($event->isCancelled()) {
            return;
        }

        $xp = match ($block->getTypeId()) {
            VanillaBlocks::EMERALD_ORE()->getTypeId() => 5,
            VanillaBlocks::DEEPSLATE_EMERALD_ORE()->getTypeId() => 5,
            default => null
        };

        if (
            !$player->isCreative() &&
            $block->getPosition()->getWorld()->getFolderName() === "mine"
        ) {
            [$breakable, $time, $replace] = ExtraVanillaBlocks::getBlock($block)->breakableOnMine();

            if (!$breakable) {
                $event->cancel();
                return;
            }

            Main::getInstance()->getScheduler()->scheduleDelayedTask(new ClosureTask(
                function () use ($block, $time, $replace): void {
                    $block->getPosition()->getWorld()->setBlock($block->getPosition(), $replace, false);
                    PlayerTask::$blocks[] = [time() + $time, $block->getPosition(), $block];
                }
            ), 1);
        }

        if (is_int($xp)) {
            Job::addXp($player, "Mineur", $xp);
        }

        if ($block->hasSameTypeId(VanillaBlocks::COBBLESTONE()) || $block->hasSameTypeId(VanillaBlocks::DEEPSLATE()) || $block->hasSameTypeId(VanillaBlocks::STONE())) {
            Job::addXp($player, "Mineur", 1);
        } else if ($block->hasSameTypeId(VanillaBlocks::MELON()) || $block->hasSameTypeId(VanillaBlocks::COCOA_POD()) || ($block instanceof Crops && !$block->ticksRandomly())) {
            Job::addXp($player, "Farmeur", mt_rand(1, 3));
        }

        Util::addItems($player, $event->getDrops());

        foreach ($event->getItem()->getEnchantments() as $enchant) {
            ExtraVanillaEnchantments::getEnchantment($enchant->getType())->onBreak($event, $enchant);
        }

        if ($event->getXpDropAmount() > 0) {
            $player->getXpManager()->addXp($event->getXpDropAmount());
        }

        $event->setDrops([]);
        $event->setXpDropAmount(0);
    }

    public function onLeavesDecay(LeavesDecayEvent $event): void
    {
        if ($event->getBlock()->getPosition()->getWorld() === Main::getInstance()->getServer()->getWorldManager()->getDefaultWorld()) {
            $event->cancel();
        }
    }

    public function onHitByProjectile(ProjectileHitEntityEvent $event): void
    {
        $player = $event->getEntityHit();

        $entity = $event->getEntity();
        $damager = $entity->getOwningEntity();

        if ($player instanceof Player && $damager instanceof Player) {
            $damagerPos = $damager->getPosition();
            $playerPos = $player->getPosition();

            if (Util::insideZone($damagerPos, "spawn") || Util::insideZone($playerPos, "spawn")) {
                return;
            }

            if ($entity instanceof SwitchBall) {
                if (Session::get($damager)->inCooldown("teleportation_switch")) {
                    $damager->sendMessage(Util::PREFIX . "Vous ne pouvez pas vous téléporté puis switch un joueur");
                    return;
                } else if ($damagerPos->distance($playerPos) > 24) {
                    $damager->sendMessage(Util::PREFIX . "Vous devez être proche de l'adversaire pour le switch");
                    return;
                }

                $player->teleport($damagerPos);
                $damager->teleport($playerPos);

                $player->broadcastSound(new EndermanTeleportSound());
                $player->broadcastSound(new EndermanTeleportSound());

                $damager->sendMessage(Util::PREFIX . "Vous avez été switch avec le joueur §n" . $player->getDisplayName());
                $player->sendMessage(Util::PREFIX . "Vous avez été switch avec le joueur §n" . $damager->getDisplayName());
            } else if ($entity instanceof AntiBackBall) {
                $player->setNoClientPredictions();

                $damager->sendMessage(Util::PREFIX . "Vous avez touché §n" . $player->getDisplayName() . " §favec votre antiback ball, il est donc freeze pendant §n2 §fsecondes");
                $player->sendMessage(Util::PREFIX . "Vous avez été touché par une antiback ball par §n" . $damager->getDisplayName() . " §fvous êtes donc freeze pendant §n2 §fsecondes");

                Session::get($damager)->setCooldown("combat", 30, [$player->getName()]);
                Session::get($player)->setCooldown("combat", 30, [$damager->getName()]);

                Main::getInstance()->getScheduler()->scheduleDelayedTask(new ClosureTask(function () use ($player) {
                    if ($player->isOnline()) {
                        $player->setNoClientPredictions(false);
                    }
                }), 2 * 20);
            } else if ($entity instanceof EggTrap) {
                $pos = $player->getPosition();
                $world = $pos->getWorld();

                $damager->sendMessage(Util::PREFIX . "Vous avez touché §n" . $player->getDisplayName() . " §favec votre §neggtrap§f, il est donc entouré de toiles d'araignées §n5 §fsecondes");
                $player->sendMessage(Util::PREFIX . "Vous avez été touché par un §neggtrap §fles toiles d'araignées disparaitront dans §n5 §fsecondes !");

                for ($x = -1; $x <= 1; $x++) {
                    for ($z = -1; $z <= 1; $z++) {
                        if ($world->getBlock($pos->add($x, 1, $z)) instanceof Air) {
                            $world->setBlock($pos->add($x, 1, $z), VanillaBlocks::COBWEB(), false);

                            PlayerTask::$blocks[] = [
                                time() + 5,
                                Position::fromObject($pos->add($x, 1, $z), $pos->getWorld()),
                                VanillaBlocks::AIR()
                            ];
                        }
                    }
                }
            }
        }
    }

    public function onCommand(CommandEvent $event): void
    {
        $sender = $event->getSender();

        $command = explode(" ", $event->getCommand());
        Main::getInstance()->getLogger()->info("[" . $sender->getName() . "] " . implode(" ", $command));

        if ($sender instanceof Player) {
            $session = Session::get($sender);

            if ($session->inCooldown("cmd")) {
                $event->cancel();
            } else {
                if (!$sender->hasPermission(DefaultPermissions::ROOT_OPERATOR)) {
                    $session->setCooldown("cmd", 1);
                }
            }

            if (in_array($sender->getName(), GamblingTask::$players)) {
                $sender->sendMessage(Util::PREFIX . "Vous ne pouvez pas executer de commande en plein gambling");
                $event->cancel();
                return;
            }

            $command[0] = strtolower($command[0]);
            $event->setCommand(implode(" ", $command));
        }
    }

    public function onPlayerSave(PlayerDataSaveEvent $event): void
    {
        $player = $event->getPlayer();

        if ($player instanceof Player) {
            $session = Session::get($player);
            $session->saveSessionData(false);
        }
    }

    public function onPreLogin(PlayerPreLoginEvent $event): void
    {
        $username = $event->getPlayerInfo()->getUsername();

        foreach (Main::getInstance()->getServer()->getWorldManager()->getDefaultWorld()->getEntities() as $entity) {
            if ($entity instanceof LogoutEntity) {
                $name = $entity->player;
                $name = is_null($name) ? "" : $name;

                if (strtolower($username) === strtolower($name)) {
                    $entity->killed = true;
                    $entity->flagForDespawn();
                }
            }
        }
    }

    public function onConsume(PlayerItemConsumeEvent $event): void
    {
        $item = $event->getItem();

        if (
            $item->getTypeId() === VanillaItems::CHORUS_FRUIT()->getTypeId() ||
            $item->getTypeId() === VanillaItems::POPPED_CHORUS_FRUIT()->getTypeId() ||
            $item->getTypeId() === VanillaItems::GOLDEN_APPLE()->getTypeId() ||
            $item->getTypeId() === VanillaItems::GOLDEN_CARROT()->getTypeId()
        ) {
            $event->cancel();
            return;
        }

        ExtraVanillaItems::getItem($item)->onConsume($event);
    }

    public function onItemSpawn(ItemSpawnEvent $event): void
    {
        $entity = $event->getEntity();

        if (ExtraVanillaItems::getItem($event->getEntity()->getItem())->isRare()) {
            $entity->setDespawnDelay(intval(60 * Main::getInstance()->getServer()->getTicksPerSecondAverage()));
        } else {
            $entity->setDespawnDelay(intval(15 * Main::getInstance()->getServer()->getTicksPerSecondAverage()));
        }
    }

    public function onDataPacketSendMaxoooz(DataPacketSendEvent $event): void
    {
        $packets = $event->getPackets();

        foreach ($packets as $packet) {
            if ($packet instanceof StartGamePacket) {
                $packet->levelSettings->muteEmoteAnnouncements = true;
            }
        }
    }

    public function onPacketReceive(DataPacketReceiveEvent $event): void
    {
        WaterdogPacketHandler::process($event);
    }

    public function onBrewItem(BrewItemEvent $event): void
    {
        $event->cancel();
    }

    public function onBrewingFuelUse(BrewingFuelUseEvent $event): void
    {
        $event->cancel();
    }

    public function onSign(SignChangeEvent $event): void
    {
        if (!Faction::canBuild($event->getPlayer(), $event->getBlock(), "break")) {
            $event->cancel();
        }
    }
}