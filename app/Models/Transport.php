<?php

namespace App\Models;

use App\Models\Concerns\HasAudit;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Transport extends Model
{
    use HasAudit, SoftDeletes;

    protected static function booted(): void
    {
        static::creating(function (self $transport): void {
            if (blank($transport->code)) {
                $transport->code = self::generateUniqueCode();
            }
        });
    }

    protected $fillable = [
        'code',
        'name',
        'transport_type',
        'vendor_id',
        'description',
        'inclusions',
        'exclusions',
        'cancellation_policy',
        'notes',
        'brand_model',
        'seat_capacity',
        'luggage_capacity',
        'contract_rate',
        'markup_type',
        'markup',
        'publish_rate',
        'overtime_rate',
        'fuel_type',
        'transmission',
        'air_conditioned',
        'with_driver',
        'images',
        'is_active',
    ];

    protected $casts = [
        'images' => 'array',
        'seat_capacity' => 'integer',
        'luggage_capacity' => 'integer',
        'contract_rate' => 'decimal:0',
        'markup_type' => 'string',
        'markup' => 'decimal:0',
        'publish_rate' => 'decimal:0',
        'overtime_rate' => 'decimal:2',
        'air_conditioned' => 'boolean',
        'with_driver' => 'boolean',
        'is_active' => 'boolean',
    ];

    public function vendor()
    {
        return $this->belongsTo(Vendor::class);
    }

    public static function generateUniqueCode(): string
    {
        do {
            $code = 'TRN-' . now()->format('ymd') . '-' . Str::upper(Str::random(4));
        } while (self::withTrashed()->where('code', $code)->exists());

        return $code;
    }
}






