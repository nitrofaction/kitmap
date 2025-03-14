<?php

namespace Kitmap\entity\animation;

use Kitmap\entity\FireworksRocket;
use pocketmine\entity\animation\Animation;
use pocketmine\network\mcpe\protocol\ActorEventPacket;
use pocketmine\network\mcpe\protocol\types\ActorEvent;

class FireworkParticleAnimation implements Animation
{
    /** @var FireworksRocket */
    private FireworksRocket $firework;

    public function __construct(FireworksRocket $firework)
    {
        $this->firework = $firework;
    }

    public function encode(): array
    {
        return [
            ActorEventPacket::create($this->firework->getId(), ActorEvent::FIREWORK_PARTICLES, 0)
        ];
    }
}
