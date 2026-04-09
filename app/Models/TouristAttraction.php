<?php

namespace App\Models;

use App\Models\Concerns\HasAudit;
use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

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
        'gallery_images' => 'array',
    ];

    public function itineraries()
    {
        return $this->belongsToMany(Itinerary::class)->withTimestamps();
    }

    public function destination()
    {
        return $this->belongsTo(Destination::class);
    }
}






