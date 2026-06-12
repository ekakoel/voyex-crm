<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class QuotationFollowUpNotification extends Model
{
    public const TYPE_FOLLOW_UP_DUE = 'quotation_follow_up_due';
    public const TYPE_FOLLOW_UP_OVERDUE = 'quotation_follow_up_overdue';
    public const TYPE_NO_RESPONSE_WARNING = 'quotation_no_response_warning';
    public const TYPE_VALIDITY_EXPIRED = 'quotation_validity_expired';
    public const TYPE_SERVICE_DATE_RISK = 'quotation_service_date_risk';
    public const TYPE_RESPONSE_NEEDS_REVISION = 'quotation_response_needs_revision';
    public const TYPE_AUTO_STATUS_REVIEW_REQUIRED = 'quotation_auto_status_review_required';

    protected $fillable = [
        'quotation_id',
        'user_id',
        'notification_type',
        'title',
        'message',
        'icon',
        'severity',
        'is_read',
        'read_at',
        'action_url',
        'due_at',
    ];

    protected $casts = [
        'is_read' => 'boolean',
        'read_at' => 'datetime',
        'due_at' => 'datetime',
    ];

    public function quotation()
    {
        return $this->belongsTo(Quotation::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
