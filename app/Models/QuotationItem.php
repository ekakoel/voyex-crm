<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\User;

class QuotationItem extends Model
{
    public const STATUS_ACTIVE = 'active';
    public const STATUS_VALIDATED = 'validated';
    public const STATUS_VENDOR_PENDING = 'vendor_pending';
    public const STATUS_VENDOR_CONFIRMED = 'vendor_confirmed';
    public const STATUS_VOUCHER_GENERATED = 'voucher_generated';
    public const STATUS_USED = 'used';
    public const STATUS_CANCELLED_FREE = 'cancelled_free';
    public const STATUS_CANCELLED_WITH_CHARGE = 'cancelled_with_charge';
    public const STATUS_NOT_AVAILABLE = 'not_available';
    public const STATUS_REPLACED = 'replaced';
    public const STATUS_ADDED_AFTER_APPROVAL = 'added_after_approval';

    protected $fillable = [
        'quotation_id',
        'serviceable_id',
        'serviceable_type',
        'description',
        'qty',
        'contract_rate',
        'markup_type',
        'markup',
        'unit_price',
        'discount_type',
        'discount',
        'day_number',
        'sort_order',
        'service_date',
        'serviceable_meta',
        'itinerary_item_type',
        'status',
        'cancellation_fee_type',
        'cancellation_fee_value',
        'cancellation_fee_amount',
        'cancellation_reason',
        'actual_used_at',
        'replaced_by_item_id',
        'is_validation_required',
        'is_validated',
        'validated_at',
        'validated_by',
        'validation_notes',
        'last_validated_contract_rate',
        'last_validated_markup_type',
        'last_validated_markup',
        'total',
    ];

    protected $casts = [
        'unit_price' => 'decimal:2',
        'contract_rate' => 'decimal:2',
        'markup_type' => 'string',
        'markup' => 'decimal:2',
        'discount_type' => 'string',
        'discount' => 'decimal:2',
        'total' => 'decimal:2',
        'day_number' => 'integer',
        'sort_order' => 'integer',
        'service_date' => 'date',
        'serviceable_meta' => 'array',
        'status' => 'string',
        'cancellation_fee_type' => 'string',
        'cancellation_fee_value' => 'decimal:2',
        'cancellation_fee_amount' => 'decimal:2',
        'cancellation_reason' => 'string',
        'actual_used_at' => 'datetime',
        'replaced_by_item_id' => 'integer',
        'is_validation_required' => 'boolean',
        'is_validated' => 'boolean',
        'validated_at' => 'datetime',
        'last_validated_contract_rate' => 'decimal:2',
        'last_validated_markup_type' => 'string',
        'last_validated_markup' => 'decimal:2',
    ];

    public function quotation()
    {
        return $this->belongsTo(Quotation::class);
    }

    public function serviceable()
    {
        return $this->morphTo();
    }

    public function validator()
    {
        return $this->belongsTo(User::class, 'validated_by');
    }

    public function replacedByItem()
    {
        return $this->belongsTo(self::class, 'replaced_by_item_id');
    }
}
