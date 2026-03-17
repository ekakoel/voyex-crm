<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\User;

class InquiryFollowUp extends Model
{
    protected $table = 'inquiry_followups';

    protected $fillable = [
        'inquiry_id',
        'due_date',
        'channel',
        'note',
        'created_by',
        'is_done',
        'done_at',
        'done_reason',
    ];

    protected $casts = [
        'due_date' => 'date',
        'done_at' => 'datetime',
        'is_done' => 'boolean',
    ];

    public function inquiry()
    {
        return $this->belongsTo(Inquiry::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    protected static function booted(): void
    {
        static::saving(function (InquiryFollowUp $followUp) {
            if ($followUp->is_done && ! $followUp->done_at) {
                $followUp->done_at = now();
            }
            if (! $followUp->is_done) {
                $followUp->done_at = null;
            }
        });
    }
}



