<?php

namespace Kitmap\entity\animation;

use Kitmap\handler\Pack as Api;
use pocketmine\block\Block;
use pocketmine\entity\Attribute;
use pocketmine\entity\EntitySizeInfo;
use pocketmine\entity\Living;
use pocketmine\entity\Location;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\math\Vector3;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\network\mcpe\protocol\AddActorPacket;
use pocketmine\network\mcpe\protocol\types\entity\Attribute as NetworkAttribute;
use pocketmine\network\mcpe\protocol\types\entity\EntityIds;
use pocketmine\network\mcpe\protocol\types\entity\PropertySyncData;
use pocketmine\player\Player;

class Pack extends Living
{
    private string $networkTypeId;

    public function __construct(Location $location, ?CompoundTag $nbt = null)
    {
        $this->networkTypeId = $nbt->getString("id", EntityIds::AGENT);
        parent::__construct($location, $nbt);
    }

    public static function getNetworkTypeId(): string
    {
        return EntityIds::AGENT;
    }

    public function saveNBT(): CompoundTag
    {
        $nbt = parent::saveNBT();
        $nbt->setString("id", $this->networkTypeId);
        return $nbt;
    }

    public function attack(EntityDamageEvent $source): void
    {
        $source->cancel();

        if ($source instanceof EntityDamageByEntityEvent) {
            $player = $source->getDamager();
            $block = $this->getBlock();

            if (!$player instanceof Player) {
                return;
            }

            $player->removeCurrentWindow();
            Api::openPackUI($player, $block);
        }
    }

    private function getBlock(): Block
    {
        return $this->getPosition()->getWorld()->getBlock($this->getFloorVector());
    }

    public function getFloorVector(): Vector3
    {
        return new Vector3($this->getPosition()->getFloorX(), round($this->getPosition()->getY()), $this->getPosition()->getFloorZ());
    }

    public function getName(): string
    {
        return "Pack";
    }

    public function onUpdate(int $currentTick): bool
    {
        static $animationTime = 0.0;
        $animationTime += 0.02;

        $floatSpeed = sin($animationTime) * 0.05;
        $this->setMotion(new Vector3(0, $floatSpeed, 0));

        return parent::onUpdate($currentTick);
    }

    protected function initEntity(CompoundTag $nbt): void
    {
        parent::initEntity($nbt);

        $this->setNameTagAlwaysVisible(false);
        $this->setScale(1.5);
    }

    protected function sendSpawnPacket(Player $player): void
    {
        $player->getNetworkSession()->sendDataPacket(AddActorPacket::create(
            $this->getId(),
            $this->getId(),
            $this->networkTypeId,
            $this->location->asVector3(),
            $this->getMotion(),
            $this->location->pitch,
            $this->location->yaw,
            $this->location->yaw,
            $this->location->yaw,
            array_map(function (Attribute $attr): NetworkAttribute {
                return new NetworkAttribute($attr->getId(), $attr->getMinValue(), $attr->getMaxValue(), $attr->getValue(), $attr->getDefaultValue(), []);
            }, $this->attributeMap->getAll()),
            $this->getAllNetworkData(),
            new PropertySyncData([], []),
            []
        ));
    }

    protected function getInitialSizeInfo(): EntitySizeInfo
    {
        return new EntitySizeInfo(0.7, 0.7);
    }
}