<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class QuotationCustomerResponse extends Model
{
    public const STATUS_PENDING_DECISION = 'pending_decision';
    public const STATUS_NEED_MORE_INFORMATION = 'need_more_information';
    public const STATUS_REVISION_REQUESTED = 'revision_requested';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_CANCELLED = 'cancelled';
    public const STATUS_REJECTED = 'rejected';
    public const STATUS_NO_RESPONSE_NOTE = 'no_response_note';

    protected $fillable = [
        'quotation_id',
        'customer_id',
        'handled_by',
        'response_channel',
        'response_status',
        'response_note',
        'requires_revision',
        'revision_type',
        'revision_priority',
        'requested_changes',
        'is_used_for_revision',
        'used_for_revision_at',
        'used_for_revision_by',
        'quotation_revision_id',
        'response_at',
        'created_by',
    ];

    protected $casts = [
        'requires_revision' => 'boolean',
        'requested_changes' => 'array',
        'is_used_for_revision' => 'boolean',
        'used_for_revision_at' => 'datetime',
        'response_at' => 'datetime',
    ];

    public function quotation()
    {
        return $this->belongsTo(Quotation::class);
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function handler()
    {
        return $this->belongsTo(User::class, 'handled_by');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function usedBy()
    {
        return $this->belongsTo(User::class, 'used_for_revision_by');
    }
}
