# Image Thumbnail Standard

Last Updated: 2026-04-17


## Objective
- All user-uploaded images rendered by the system must use thumbnail-first delivery for better performance.
- If thumbnail file is missing, system regenerates it from the original image and stores it under `thumbs/`.

## Core Implementation
- Central utility: `app/Support/ImageThumbnailGenerator.php`
- Standard methods:
  - `resolvePublicUrl($path, $directories = [], $disk = 'public', $targetWidth = 360, $targetHeight = 240, $preferThumbnail = true)`
  - `resolveOriginalPublicUrl($path, $directories = [], $disk = 'public')`
  - `thumbnailPathFor($originalPath)`

## Required Usage Rule
- In Blade views, do not use direct `asset('storage/...')` for uploaded media.
- Use:
  - thumbnail URL via `ImageThumbnailGenerator::resolvePublicUrl(...)`
  - original fallback via `ImageThumbnailGenerator::resolveOriginalPublicUrl(...)`

## PDF/Data URI Rule
- For PDF pipelines (itinerary/quotation), if thumbnail data is not found, regenerate thumbnail first from original image, then retry thumbnail read.

## Backfill Command
- Command: `php artisan images:generate-thumbnails`
- Optional flags:
  - `--force`
  - `--width=360`
  - `--height=240`
  - `--disk=public`

## Storage Convention
- Thumbnail path format:
  - original: `module/path/file.jpg`
  - thumb: `module/path/thumbs/file_thumb.jpg`

## QA Checklist
- `php -l app/Support/ImageThumbnailGenerator.php`
- `php artisan list | Select-String -Pattern "images:generate-thumbnails"`
- `php artisan view:cache`
