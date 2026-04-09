<?php

namespace App\Models;

use App\Models\Concerns\HasAudit;
use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class TouristAttraction extends Model
{
    use HasAudit, LogsActivity, SoftDeletes;
    protected $fillable = [
        'name',
        'ideal_visit_minutes',
        'contract_rate_per_pax',
        'markup_type',
        'markup',
        'publish_rate_per_pax',
        'location',
        'city',
        'province',
        'country',
        'timezone',
        'address',
        'destination_id',
        'google_maps_url',
        'latitude',
        'longitude',
        'description',
        'gallery_images',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'ideal_visit_minutes' => 'integer',
        'contract_rate_per_pax' => 'decimal:0',
        'markup_type' => 'string',
        'markup' => 'decimal:0',
        'publish_rate_per_pax' => 'decimal:0',
        'latitude' => 'float',
        'longitude' => 'float',
    ];

    public function getGalleryImagesAttribute($value): array
    {
        $images = is_array($value) ? $value : (is_string($value) ? json_decode($value, true) : []);
        if (! is_array($images)) {
            return [];
        }

        $normalized = [];
        foreach ($images as $path) {
            if (! is_string($path) || trim($path) === '') {
                continue;
            }

            $normalizedPath = $this->normalizeGalleryPath($path);
            if ($normalizedPath !== '') {
                $normalized[] = $normalizedPath;
            }
        }

        return array_values(array_unique($normalized));
    }

    public function setGalleryImagesAttribute($value): void
    {
        $images = is_array($value) ? $value : (is_string($value) ? json_decode($value, true) : []);
        if (! is_array($images)) {
            $images = [];
        }

        $normalized = [];
        foreach ($images as $path) {
            if (! is_string($path) || trim($path) === '') {
                continue;
            }

            $normalizedPath = $this->normalizeGalleryPath($path);
            if ($normalizedPath !== '') {
                $normalized[] = $normalizedPath;
            }
        }

        $this->attributes['gallery_images'] = json_encode(array_values(array_unique($normalized)));
    }

    private function normalizeGalleryPath(string $path): string
    {
        $normalized = trim(str_replace('\\', '/', $path), '/');
        if ($normalized === '') {
            return '';
        }

        if (Str::startsWith($normalized, 'storage/')) {
            $normalized = Str::after($normalized, 'storage/');
        }

        if (Str::startsWith($normalized, 'tourist-attractions/')) {
            return $normalized;
        }

        if (! Str::contains($normalized, '/')) {
            return 'tourist-attractions/' . $normalized;
        }

        return $normalized;
    }

    public function itineraries()
    {
        return $this->belongsToMany(Itinerary::class)->withTimestamps();
    }

    public function destination()
    {
        return $this->belongsTo(Destination::class);
    }
}





