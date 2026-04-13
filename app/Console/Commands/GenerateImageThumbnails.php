<?php

namespace App\Console\Commands;

use App\Support\ImageThumbnailGenerator;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class GenerateImageThumbnails extends Command
{
    protected $signature = 'images:generate-thumbnails
        {--disk=public : Storage disk name}
        {--force : Regenerate even when thumbnail already exists}
        {--width=360 : Thumbnail width}
        {--height=240 : Thumbnail height}';

    protected $description = 'Generate thumbnails for image files and store them in each folder thumbs/ directory.';

    public function handle(): int
    {
        $disk = (string) $this->option('disk');
        $force = (bool) $this->option('force');
        $width = max(1, (int) $this->option('width'));
        $height = max(1, (int) $this->option('height'));

        $storage = Storage::disk($disk);
        $files = $storage->allFiles();

        $processed = 0;
        $generated = 0;
        $skipped = 0;

        foreach ($files as $path) {
            $normalized = str_replace('\\', '/', (string) $path);
            $extension = strtolower((string) pathinfo($normalized, PATHINFO_EXTENSION));
            if (! in_array($extension, ['jpg', 'jpeg', 'png', 'webp', 'gif', 'bmp'], true)) {
                continue;
            }

            if (Str::contains($normalized, '/thumbs/')) {
                continue;
            }

            $processed++;
            $thumbnailPath = ImageThumbnailGenerator::thumbnailPathFor($normalized);

            if (! $force && $storage->exists($thumbnailPath)) {
                $skipped++;
                continue;
            }

            $result = ImageThumbnailGenerator::generate($disk, $normalized, $width, $height);
            if ($result !== null) {
                $generated++;
                continue;
            }

            $skipped++;
        }

        $this->info("Thumbnail generation completed on disk [{$disk}].");
        $this->line("Processed images: {$processed}");
        $this->line("Generated: {$generated}");
        $this->line("Skipped: {$skipped}");

        return self::SUCCESS;
    }
}

