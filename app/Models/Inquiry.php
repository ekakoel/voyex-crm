<?php

namespace App\Models;

use App\Models\Concerns\HasAudit;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\User;
use App\Models\ActivityLog;
use App\Traits\LogsActivity;
use Illuminate\Support\Str;

class Inquiry extends Model
{
    use SoftDeletes;
    use HasFactory, HasAudit, LogsActivity;

    public const STATUS_OPTIONS = [
        'draft',
        'processed',
        'pending',
        'approved',
        'rejected',
        'final',
    ];

    public const FINAL_STATUS = 'final';
    protected $fillable = [
        'inquiry_number',
        'customer_id',
        'source',
        'status',
        'priority',
        'deadline',
        'assigned_to',
        'notes',
        'reminder_enabled',
    ];
    protected $casts = [
        'deadline' => 'date',
    ];

    public function quotation()
    {
        return $this->hasOne(Quotation::class);
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function assignedUser()
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function followUps()
    {
        return $this->hasMany(InquiryFollowUp::class);
    }

    public function communications()
    {
        return $this->hasMany(InquiryCommunication::class);
    }

    public function activities()
    {
        return $this->morphMany(ActivityLog::class, 'subject');
    }

    public function itineraries()
    {
        return $this->hasMany(Itinerary::class);
    }

    public function isFinal(): bool
    {
        return $this->status === self::FINAL_STATUS;
    }

    public function isAssignedTo(?User $user): bool
    {
        if (! $user) {
            return false;
        }

        return (int) ($this->assigned_to ?? 0) === (int) $user->id;
    }

    protected static function boot()
    {
        parent::boot();
        static::creating(function ($model) {
            if (! empty($model->inquiry_number)) {
                return;
            }

            $dateKey = now()->format('ymd');
            $customerCode = Customer::query()
                ->whereKey($model->customer_id)
                ->value('code');

            $customerCode = strtoupper((string) $customerCode);
            $customerCode = preg_replace('/[^A-Z0-9]/', '', $customerCode) ?: 'CST';
            $customerCode = str_pad(substr($customerCode, 0, 3), 3, 'X');

            $sequence = self::nextDailySequence($dateKey);
            $model->inquiry_number = "INQ-{$customerCode}{$dateKey}{$sequence}";
        });
    }

    private static function nextDailySequence(string $dateKey): string
    {
        $date = now()->toDateString();
        $counter = self::query()->whereDate('created_at', $date)->count() + 1;

        while (true) {
            $suffix = self::numberToLetters($counter);
            $exists = self::query()
                ->whereDate('created_at', $date)
                ->where('inquiry_number', 'like', "%{$dateKey}{$suffix}")
                ->exists();
            if (! $exists) {
                return $suffix;
            }
            $counter++;
        }
    }

    private static function numberToLetters(int $number): string
    {
        $letters = '';
        while ($number > 0) {
            $mod = ($number - 1) % 26;
            $letters = chr(65 + $mod) . $letters;
            $number = intdiv($number - 1, 26);
        }
        return $letters;
    }
}





