<?php

namespace App\Support;

use Illuminate\Support\Facades\Storage;

class ImageThumbnailGenerator
{
    public static function thumbnailPathFor(string $originalPath): string
    {
        $directory = trim((string) pathinfo($originalPath, PATHINFO_DIRNAME), '.');
        $name = (string) pathinfo($originalPath, PATHINFO_FILENAME);

        return ($directory !== '' ? $directory.'/' : '').'thumbs/'.$name.'_thumb.jpg';
    }

    public static function generate(string $disk, string $originalPath, int $targetWidth = 360, int $targetHeight = 240): ?string
    {
        if (! extension_loaded('gd')) {
            return null;
        }

        $storage = Storage::disk($disk);
        if (! $storage->exists($originalPath)) {
            return null;
        }

        $binary = $storage->get($originalPath);
        $source = @imagecreatefromstring($binary);
        if (! $source) {
            return null;
        }

        $sourceWidth = imagesx($source);
        $sourceHeight = imagesy($source);
        if ($sourceWidth <= 0 || $sourceHeight <= 0) {
            imagedestroy($source);
            return null;
        }

        $sourceRatio = $sourceWidth / $sourceHeight;
        $targetRatio = $targetWidth / $targetHeight;

        if ($sourceRatio > $targetRatio) {
            $cropHeight = $sourceHeight;
            $cropWidth = (int) round($sourceHeight * $targetRatio);
            $cropX = (int) floor(($sourceWidth - $cropWidth) / 2);
            $cropY = 0;
        } else {
            $cropWidth = $sourceWidth;
            $cropHeight = (int) round($sourceWidth / $targetRatio);
            $cropX = 0;
            $cropY = (int) floor(($sourceHeight - $cropHeight) / 2);
        }

        $thumbnail = imagecreatetruecolor($targetWidth, $targetHeight);
        if (! $thumbnail) {
            imagedestroy($source);
            return null;
        }

        imagecopyresampled(
            $thumbnail,
            $source,
            0,
            0,
            $cropX,
            $cropY,
            $targetWidth,
            $targetHeight,
            $cropWidth,
            $cropHeight
        );

        ob_start();
        imagejpeg($thumbnail, null, 82);
        $thumbnailBinary = (string) ob_get_clean();

        imagedestroy($source);
        imagedestroy($thumbnail);

        if ($thumbnailBinary === '') {
            return null;
        }

        $thumbnailPath = self::thumbnailPathFor($originalPath);
        $storage->put($thumbnailPath, $thumbnailBinary);

        return $thumbnailPath;
    }
}

