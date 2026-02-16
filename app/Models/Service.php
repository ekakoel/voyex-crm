<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Service extends Model
{
    public const TYPE_ACCOMMODATIONS = 'accommodations';
    public const TYPE_TRANSPORTS = 'transports';
    public const TYPE_GUIDES = 'guides';
    public const TYPE_ATTRACTIONS = 'attractions';
    public const TYPE_TRAVEL_ACTIVITIES = 'travel_activities';

    public const TYPES = [
        self::TYPE_ACCOMMODATIONS,
        self::TYPE_TRANSPORTS,
        self::TYPE_GUIDES,
        self::TYPE_ATTRACTIONS,
        self::TYPE_TRAVEL_ACTIVITIES,
    ];

    protected $fillable = [
        'vendor_id',
        'service_type',
        'name',
        'description',
        'unit_price',
        'is_active',
    ];

    protected $casts = [
        'unit_price' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    public static function labels(): array
    {
        return [
            self::TYPE_ACCOMMODATIONS => 'Accommodations',
            self::TYPE_TRANSPORTS => 'Transports',
            self::TYPE_GUIDES => 'Guides',
            self::TYPE_ATTRACTIONS => 'Attractions',
            self::TYPE_TRAVEL_ACTIVITIES => 'Travel Activities',
        ];
    }

    public function vendor()
    {
        return $this->belongsTo(Vendor::class);
    }
}
