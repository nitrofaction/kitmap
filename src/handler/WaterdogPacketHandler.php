<?php

namespace Kitmap\handler;

use Closure;
use JsonMapper;
use JsonMapper_Exception;
use Kitmap\Util;
use pocketmine\event\server\DataPacketReceiveEvent;
use pocketmine\network\mcpe\handler\LoginPacketHandler;
use pocketmine\network\mcpe\JwtException;
use pocketmine\network\mcpe\JwtUtils;
use pocketmine\network\mcpe\NetworkSession;
use pocketmine\network\mcpe\protocol\LoginPacket;
use pocketmine\network\mcpe\protocol\types\login\ClientData;
use pocketmine\network\PacketHandlingException;
use pocketmine\player\PlayerInfo;
use pocketmine\player\XboxLivePlayerInfo;
use pocketmine\Server;
use pocketmine\utils\TextFormat;
use ReflectionClass;
use ReflectionProperty;

class WaterdogPacketHandler extends LoginPacketHandler
{
    public function __construct(Server $server, NetworkSession $session, string $Waterdog_XUID, string $Waterdog_IP)
    {
        $playerInfoConsumer = Closure::bind(function (PlayerInfo $info) use ($session, $Waterdog_XUID, $Waterdog_IP): void {
            $session->ip = $Waterdog_IP;
            $session->info = new XboxLivePlayerInfo($Waterdog_XUID, $info->getUsername(), $info->getUuid(), $info->getSkin(), $info->getLocale(), $info->getExtraData());
            $session->logger->setPrefix($session->getLogPrefix());
            $session->logger->info("Player: " . TextFormat::AQUA . $info->getUsername() . TextFormat::RESET);
        }, $this, $session);

        $authCallback = Closure::bind(function (bool $isAuthenticated, bool $authRequired, ?string $error, ?string $clientPubKey) use ($session): void {
            $session->setAuthenticationStatus(true, $authRequired, $error, $clientPubKey);
        }, $this, $session);

        parent::__construct($server, $session, $playerInfoConsumer, $authCallback);
    }

    public static function process(DataPacketReceiveEvent $event): void
    {
        $packet = $event->getPacket();

        if ($packet instanceof LoginPacket) {
            try {
                [, $clientData,] = JwtUtils::parse($packet->clientDataJwt);
            } catch (JwtException $e) {
                throw PacketHandlingException::wrap($e);
            }

            if (
                !isset($clientData["Waterdog_XUID"]) ||
                !isset($clientData["Waterdog_IP"]) ||
                !self::checkIpAddress($event->getOrigin()->getIp())
            ) {
                $event->getOrigin()->disconnect(Util::PREFIX . "Connectez vous depuis le port 19132" . Util::IARROW);
                return;
            }

            $event->getOrigin()->setHandler(new WaterdogPacketHandler(
                Server::getInstance(),
                $event->getOrigin(),
                $clientData["Waterdog_XUID"],
                $clientData["Waterdog_IP"]
            ));

            unset($clientData);
        }
    }

    private static function checkIpAddress(string $providedIpAddress): bool
    {
        if (!filter_var($providedIpAddress, FILTER_VALIDATE_IP)) {
            $providedIpAddress = gethostbyname($providedIpAddress);
        }
        if (strtolower($providedIpAddress) === "localhost" || $providedIpAddress === "0.0.0.0") {
            $providedIpAddress = "127.0.0.1";
        }
        return $providedIpAddress == "127.0.0.1";
    }

    protected function parseClientData(string $clientDataJwt): ClientData
    {
        try {
            [, $clientDataClaims,] = JwtUtils::parse($clientDataJwt);
        } catch (JwtException $e) {
            throw PacketHandlingException::wrap($e);
        }

        $mapper = new JsonMapper;
        $mapper->bEnforceMapType = false;
        $mapper->bExceptionOnMissingData = true;
        $mapper->bExceptionOnUndefinedProperty = true;

        try {
            $clientDataProperties = array_map(fn(ReflectionProperty $property) => $property->getName(), (new ReflectionClass(ClientData::class))->getProperties());

            foreach ($clientDataClaims as $k => $v) {
                if (!in_array($k, $clientDataProperties)) unset($clientDataClaims[$k]);
            }

            unset($properties);
            $clientData = $mapper->map($clientDataClaims, new ClientData);
        } catch (JsonMapper_Exception $e) {
            throw PacketHandlingException::wrap($e);
        }
        return $clientData;
    }
}