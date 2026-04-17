<?php

namespace App\Support;

use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;

class ImageThumbnailGenerator
{
    private static function buildPublicDiskUrl(string $path): string
    {
        return '/storage/' . ltrim($path, '/');
    }

    public static function normalizeStoredPath(?string $path, array $directories = [], string $disk = 'public'): ?string
    {
        $value = trim((string) $path);
        if ($value === '') {
            return null;
        }

        if (Str::startsWith($value, ['http://', 'https://'])) {
            return $value;
        }

        $normalized = ltrim(str_replace('\\', '/', $value), '/');
        if (Str::startsWith($normalized, 'storage/')) {
            $normalized = Str::after($normalized, 'storage/');
        }

        if ($normalized === '') {
            return null;
        }

        if (! Str::contains($normalized, '/')) {
            foreach ($directories as $directory) {
                $candidate = trim((string) $directory, '/');
                if ($candidate === '') {
                    continue;
                }

                $candidatePath = $candidate . '/' . $normalized;
                if (Storage::disk($disk)->exists($candidatePath)) {
                    return $candidatePath;
                }
            }
        }

        return $normalized;
    }

    public static function resolveOriginalPublicUrl(?string $path, array $directories = [], string $disk = 'public'): ?string
    {
        $normalized = self::normalizeStoredPath($path, $directories, $disk);
        if ($normalized === null) {
            return null;
        }

        if (Str::startsWith($normalized, ['http://', 'https://'])) {
            return $normalized;
        }

        $storage = Storage::disk($disk);
        if (! $storage->exists($normalized)) {
            return null;
        }

        if ($disk === 'public') {
            return self::buildPublicDiskUrl($normalized);
        }

        return $storage->url($normalized);
    }

    public static function resolvePublicUrl(
        ?string $path,
        array $directories = [],
        string $disk = 'public',
        int $targetWidth = 360,
        int $targetHeight = 240,
        bool $preferThumbnail = true
    ): ?string {
        $normalized = self::normalizeStoredPath($path, $directories, $disk);
        if ($normalized === null) {
            return null;
        }

        if (Str::startsWith($normalized, ['http://', 'https://'])) {
            return $normalized;
        }

        $storage = Storage::disk($disk);
        if (! $storage->exists($normalized)) {
            return null;
        }

        if ($preferThumbnail) {
            $thumbnailPath = self::thumbnailPathFor($normalized);
            if (! $storage->exists($thumbnailPath)) {
                self::generate($disk, $normalized, $targetWidth, $targetHeight);
            }

            if ($storage->exists($thumbnailPath)) {
                if ($disk === 'public') {
                    return self::buildPublicDiskUrl($thumbnailPath);
                }
                return $storage->url($thumbnailPath);
            }
        }

        if ($disk === 'public') {
            return self::buildPublicDiskUrl($normalized);
        }

        return $storage->url($normalized);
    }

    public static function thumbnailPathFor(string $originalPath): string
    {
        $directory = trim((string) pathinfo($originalPath, PATHINFO_DIRNAME), '.');
        $name = (string) pathinfo($originalPath, PATHINFO_FILENAME);

        return ($directory !== '' ? $directory.'/' : '').'thumbs/'.$name.'_thumb.jpg';
    }

    public static function generate(string $disk, string $originalPath, int $targetWidth = 360, int $targetHeight = 240): ?string
    {
        $storage = Storage::disk($disk);
        if (! $storage->exists($originalPath)) {
            return null;
        }

        $thumbnailPath = self::thumbnailPathFor($originalPath);
        if (! extension_loaded('gd')) {
            // Fallback: keep thumbnail path available even when GD extension is not installed.
            $storage->copy($originalPath, $thumbnailPath);
            return $thumbnailPath;
        }

        $binary = $storage->get($originalPath);
        $source = @imagecreatefromstring($binary);
        if (! $source) {
            $storage->copy($originalPath, $thumbnailPath);
            return $thumbnailPath;
        }

        $sourceWidth = imagesx($source);
        $sourceHeight = imagesy($source);
        if ($sourceWidth <= 0 || $sourceHeight <= 0) {
            imagedestroy($source);
            $storage->copy($originalPath, $thumbnailPath);
            return $thumbnailPath;
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
            $storage->copy($originalPath, $thumbnailPath);
            return $thumbnailPath;
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
            $storage->copy($originalPath, $thumbnailPath);
            return $thumbnailPath;
        }

        $storage->put($thumbnailPath, $thumbnailBinary);

        return $thumbnailPath;
    }

    public static function processAndGenerate(
        string $disk,
        string $originalPath,
        int $ratioWidth = 3,
        int $ratioHeight = 2,
        int $thumbWidth = 360,
        int $thumbHeight = 240
    ): ?string {
        $storage = Storage::disk($disk);
        if (! $storage->exists($originalPath)) {
            return null;
        }

        if (! extension_loaded('gd')) {
            self::generate($disk, $originalPath, $thumbWidth, $thumbHeight);
            return $originalPath;
        }

        $binary = $storage->get($originalPath);
        $source = @imagecreatefromstring($binary);
        if (! $source) {
            self::generate($disk, $originalPath, $thumbWidth, $thumbHeight);
            return $originalPath;
        }

        $sourceWidth = imagesx($source);
        $sourceHeight = imagesy($source);
        if ($sourceWidth <= 0 || $sourceHeight <= 0 || $ratioWidth <= 0 || $ratioHeight <= 0) {
            imagedestroy($source);
            self::generate($disk, $originalPath, $thumbWidth, $thumbHeight);
            return $originalPath;
        }

        $sourceRatio = $sourceWidth / $sourceHeight;
        $targetRatio = $ratioWidth / $ratioHeight;

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

        $cropped = imagecreatetruecolor($cropWidth, $cropHeight);
        if (! $cropped) {
            imagedestroy($source);
            self::generate($disk, $originalPath, $thumbWidth, $thumbHeight);
            return $originalPath;
        }

        imagecopyresampled(
            $cropped,
            $source,
            0,
            0,
            $cropX,
            $cropY,
            $cropWidth,
            $cropHeight,
            $cropWidth,
            $cropHeight
        );

        ob_start();
        imagejpeg($cropped, null, 88);
        $croppedBinary = (string) ob_get_clean();

        imagedestroy($source);
        imagedestroy($cropped);

        if ($croppedBinary === '') {
            self::generate($disk, $originalPath, $thumbWidth, $thumbHeight);
            return $originalPath;
        }

        $directory = trim((string) pathinfo($originalPath, PATHINFO_DIRNAME), '.');
        $name = (string) pathinfo($originalPath, PATHINFO_FILENAME);
        $processedPath = ($directory !== '' ? $directory.'/' : '').$name.'.jpg';

        $written = $storage->put($processedPath, $croppedBinary);
        if (! $written || ! $storage->exists($processedPath)) {
            // Fail-safe: keep and use original file when processed write is not persisted.
            self::generate($disk, $originalPath, $thumbWidth, $thumbHeight);
            return $originalPath;
        }

        if ($processedPath !== $originalPath && $storage->exists($originalPath)) {
            $storage->delete($originalPath);
        }

        self::generate($disk, $processedPath, $thumbWidth, $thumbHeight);

        return $processedPath;
    }
}
