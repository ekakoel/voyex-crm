<?php

namespace App\Models;

use App\Models\Concerns\HasAudit;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Vendor extends Model
{
    use HasAudit;
    use SoftDeletes;

    public const TYPE_TRANSPORTATION = 'transportation';
    public const TYPE_ISLAND_TRANSFER = 'island_transfer';
    public const TYPE_FOOD_BEVERAGE = 'food_beverage';
    public const TYPE_ACTIVITIES = 'activities';

    protected $fillable = [
        'name',
        'type',
        'types',
        'location',
        'google_maps_url',
        'latitude',
        'longitude',
        'city',
        'province',
        'country',
        'timezone',
        'destination_id',
        'contact_name',
        'contact_email',
        'contact_phone',
        'website',
        'address',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'latitude' => 'float',
        'longitude' => 'float',
        'types' => 'array',
    ];

    public static function typeOptions(): array
    {
        return [
            self::TYPE_TRANSPORTATION => ui_phrase('Transportation'),
            self::TYPE_ISLAND_TRANSFER => ui_phrase('Island Transfer'),
            self::TYPE_FOOD_BEVERAGE => ui_phrase('F&B'),
            self::TYPE_ACTIVITIES => ui_phrase('Activities'),
        ];
    }

    public function normalizedTypes(): array
    {
        $types = is_array($this->types) ? $this->types : [];

        if ($types === [] && filled($this->type)) {
            $types = [(string) $this->type];
        }

        return array_values(array_intersect(array_keys(self::typeOptions()), $types));
    }

    public function typeLabels(): array
    {
        $options = self::typeOptions();

        return array_values(array_map(
            static fn (string $type): string => $options[$type],
            $this->normalizedTypes()
        ));
    }

    public function activities()
    {
        return $this->hasMany(Activity::class);
    }

    public function destination()
    {
        return $this->belongsTo(Destination::class);
    }

    public function foodBeverages()
    {
        return $this->hasMany(FoodBeverage::class);
    }


    public function transports()
    {
        return $this->hasMany(Transport::class);
    }

    public function islandTransfers()
    {
        return $this->hasMany(IslandTransfer::class);
    }

}
