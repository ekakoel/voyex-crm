<?php

namespace App\Models;

use App\Models\Concerns\HasAudit;
use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class IslandTransfer extends Model
{
    use HasAudit;
    use LogsActivity;
    use SoftDeletes;

    protected $fillable = [
        'vendor_id',
        'name',
        'transfer_type',
        'departure_point_name',
        'departure_latitude',
        'departure_longitude',
        'arrival_point_name',
        'arrival_latitude',
        'arrival_longitude',
        'route_geojson',
        'duration_minutes',
        'contract_rate',
        'markup_type',
        'markup',
        'publish_rate',
        'capacity_min',
        'capacity_max',
        'notes',
        'is_active',
    ];

    protected $casts = [
        'departure_latitude' => 'float',
        'departure_longitude' => 'float',
        'arrival_latitude' => 'float',
        'arrival_longitude' => 'float',
        'route_geojson' => 'array',
        'duration_minutes' => 'integer',
        'contract_rate' => 'decimal:0',
        'markup_type' => 'string',
        'markup' => 'decimal:0',
        'publish_rate' => 'decimal:0',
        'capacity_min' => 'integer',
        'capacity_max' => 'integer',
        'is_active' => 'boolean',
    ];

    public function vendor()
    {
        return $this->belongsTo(Vendor::class);
    }
}
