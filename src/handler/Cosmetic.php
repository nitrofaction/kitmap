<?php

namespace Kitmap\handler;

use GdImage;
use Kitmap\Main;
use Kitmap\Session;
use pocketmine\entity\Skin;
use pocketmine\player\Player;
use Ramsey\Uuid\Nonstandard\Uuid;

class Cosmetic
{
    public static array $skins;

    public static function setDefaultSkin(Player $player): void
    {
        $session = Session::get($player);

        $skin = $session->data["skin"];
        $cosmetic = $session->data["cosmetic"] ?? null;

        if (!is_null($cosmetic)) {
            $cosmetic = explode(":", $cosmetic);
            $skin = self::getCosmetic($skin, $cosmetic[0], $cosmetic[1]);
        }

        if ($player->getSkin() !== $skin) {
            $player->setSkin($skin);
            $player->sendSkin();
        }
    }

    public static function getCosmetic(Skin $skin, string $type, string $name): Skin
    {
        $geometryData = self::$skins[$type][$name]["geometry"];
        $skinData = self::combineSkin($skin->getSkinData(), self::$skins[$type][$name]["texture"]);

        return new Skin($skin->getSkinId(), $skinData, "", "geometry." . $name, $geometryData);
    }


    public static function combineSkin(string $skinBytes, string $cosmeticBytes): string
    {
        $newSkinBytesArr1 = [];
        $newSkinBytesArr2 = [];

        for ($i = 0; $i < 64; $i++) {
            $startPos = $i * 256;
            $substring = substr($skinBytes, $startPos, 256);

            if ($i < 32) {
                $newSkinBytesArr1[] = $substring;
            } else {
                $newSkinBytesArr2[] = $substring;
            }
        }

        $mergedSkinBytesArr = [];

        for ($i = 0; $i < 32; $i++) {
            $mergedSkinBytesArr[] = $newSkinBytesArr1[$i];
            $mergedSkinBytesArr[] = $newSkinBytesArr2[$i];
        }

        $newSkinBytes = implode("", $mergedSkinBytesArr);
        return $newSkinBytes . substr($cosmeticBytes, strlen($newSkinBytes));
    }

    public static function checkSkin(Player $player, Skin $skin = null): Skin
    {
        $session = Session::get($player);
        $cosmetic = $session->data["cosmetic"] ?? null;

        $skin = $skin ?? $player->getSkin();
        $old = clone($skin);

        if (strlen($skin->getSkinData()) !== 64 * 64 * 4) {
            return self::getSkinFromName("skins", "steve");
        }

        if (!is_null($cosmetic)) {
            $cosmetic = explode(":", $cosmetic);
            $skin = self::getCosmetic($skin, $cosmetic[0], $cosmetic[1]);
        }

        if ($old !== $skin) {
            $player->setSkin($skin);
            $player->sendSkin();
        }

        return $skin;
    }

    public static function getSkinFromName(string $type, string $name): Skin
    {
        if (isset(self::$skins[$type][$name])) {
            if (isset(self::$skins[$type][$name]["geometry"])) {
                return new Skin(Uuid::uuid4()->toString(), self::$skins[$type][$name]["texture"], "", "geometry." . $name, self::$skins[$type][$name]["geometry"]);
            } else {
                return new Skin(Uuid::uuid4()->toString(), self::$skins[$type][$name]["texture"], "", "geometry.humanoid.custom", "");
            }
        } else {
            $path = Main::getInstance()->getDataFolder() . "data/skins/" . $name . ".png";

            if (!file_exists($path)) {
                return self::getSkinFromName("skins", "steve");
            } else {
                return new Skin(Uuid::uuid4()->toString(), self::getBytesFromImage($path));
            }
        }
    }

    public static function getBytesFromImage(string $path): string
    {
        $image = imagecreatefrompng($path);
        $size = @getimagesize($path);

        $bytes = self::imageToBytes($size[1], $size[0], $image);

        @imagedestroy($image);
        return $bytes;
    }

    public static function imageToBytes(int $width, int $height, GdImage $image): string
    {
        $bytes = "";

        for ($y = 0; $y < $height; $y++) {
            for ($x = 0; $x < $width; $x++) {
                $rgba = @imagecolorat($image, $x, $y);
                $a = ((~(($rgba >> 24))) << 1) & 0xff;
                $r = ($rgba >> 16) & 0xff;
                $g = ($rgba >> 8) & 0xff;
                $b = $rgba & 0xff;
                $bytes .= chr($r) . chr($g) . chr($b) . chr($a);
            }
        }

        return $bytes;
    }

    public static function setCosmetic(Player $player, string $type, string $name): void
    {
        $session = Session::get($player);
        $skin = $session->data["skin"];

        if (!$skin instanceof Skin) {
            return;
        }

        $skin = self::getCosmetic($skin, $type, $name);

        $player->setSkin($skin);
        $player->sendSkin();
    }

    public static function saveSkin(Player $player, Skin $skin): void
    {
        $session = Session::get($player);
        $session->data["skin"] = $skin;

        $path = Main::getInstance()->getDataFolder() . "data/skins/" . strtolower($player->getName()) . ".png";
        $img = self::bytesToImage($skin->getSkinData(), 64, 64);

        imagepng($img, $path);
        imagedestroy($img);

        $geometryData = $skin->getGeometryData();

        if (!empty($geometryData)) {
            $pathGeometry = Main::getInstance()->getDataFolder() . "data/skins/" . strtolower($player->getName()) . "_geometry.json";
            file_put_contents($pathGeometry, $geometryData);
        }
    }

    public static function bytesToImage(string $bytes, int $width, int $height): GdImage
    {
        $img = imagecreatetruecolor($width, $height);

        imagealphablending($img, false);
        imagesavealpha($img, true);

        $index = 0;

        for ($y = 0; $y < $height; ++$y) {
            for ($x = 0; $x < $width; ++$x) {
                $list = substr($bytes, $index, 4);

                $r = ord($list[0]);
                $g = ord($list[1]);
                $b = ord($list[2]);

                $a = 127 - (ord($list[3]) >> 1);
                $index += 4;

                $color = imagecolorallocatealpha($img, $r, $g, $b, $a);
                imagesetpixel($img, $x, $y, $color);
            }
        }

        return $img;
    }
}