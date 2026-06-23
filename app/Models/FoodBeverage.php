<?php

namespace App\Models;

use App\Models\Concerns\HasAudit;
use App\Models\Concerns\HasCancellationPolicy;
use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class FoodBeverage extends Model
{
    use HasAudit, HasCancellationPolicy, LogsActivity, SoftDeletes;

    public const MEAL_PERIOD_OPTIONS = [
        'breakfast' => 'Breakfast',
        'lunch' => 'Lunch',
        'tea_time' => 'Tea Time',
        'dinner' => 'Dinner',
    ];

    protected $fillable = [
        'vendor_id',
        'name',
        'service_type',
        'duration_minutes',
        'adult_contract_rate',
        'child_contract_rate',
        'adult_markup_type',
        'adult_markup',
        'child_markup_type',
        'child_markup',
        'adult_publish_rate',
        'child_publish_rate',
        'contract_rate',
        'markup_type',
        'markup',
        'publish_rate',
        'meal_period',
        'menu_highlights',
        'cancellation_policy',
        'notes',
        'gallery_images',
        'is_active',
    ];

    protected $casts = [
        'duration_minutes' => 'integer',
        'adult_contract_rate' => 'decimal:0',
        'child_contract_rate' => 'decimal:0',
        'adult_markup_type' => 'string',
        'adult_markup' => 'decimal:0',
        'child_markup_type' => 'string',
        'child_markup' => 'decimal:0',
        'adult_publish_rate' => 'decimal:0',
        'child_publish_rate' => 'decimal:0',
        'contract_rate' => 'decimal:0',
        'markup_type' => 'string',
        'markup' => 'decimal:0',
        'publish_rate' => 'decimal:0',
        'gallery_images' => 'array',
        'is_active' => 'boolean',
    ];

    public function vendor()
    {
        return $this->belongsTo(Vendor::class);
    }

    public static function mealPeriodOptions(): array
    {
        return self::MEAL_PERIOD_OPTIONS;
    }

    public static function mealPeriodKeys(): array
    {
        return array_keys(self::MEAL_PERIOD_OPTIONS);
    }

    public static function mealPeriodLabel(string $key): ?string
    {
        return self::MEAL_PERIOD_OPTIONS[$key] ?? null;
    }

    /**
     * @param  mixed  $source
     * @return array<int, string>
     */
    public static function normalizeMealPeriodTokens($source): array
    {
        $text = is_array($source) ? implode(',', $source) : (string) $source;
        $parts = array_map('trim', explode(',', $text));

        $found = [];
        foreach ($parts as $part) {
            $normalizedPart = strtolower($part);
            if ($normalizedPart === 'breakfast') {
                $found[] = 'breakfast';
            }
            if ($normalizedPart === 'lunch') {
                $found[] = 'lunch';
            }
            if ($normalizedPart === 'tea time' || $normalizedPart === 'tea-time' || $normalizedPart === 'tea_time') {
                $found[] = 'tea_time';
            }
            if ($normalizedPart === 'dinner') {
                $found[] = 'dinner';
            }
        }

        $uniqueFound = array_unique($found);
        $ordered = [];
        foreach (self::mealPeriodKeys() as $allowed) {
            if (in_array($allowed, $uniqueFound, true)) {
                $ordered[] = $allowed;
            }
        }

        return $ordered;
    }

    public static function formatMealPeriodForStorage(array $selections): ?string
    {
        $tokens = self::normalizeMealPeriodTokens($selections);
        if ($tokens === []) {
            return null;
        }

        return implode(', ', array_map(
            static fn (string $token): string => self::mealPeriodLabel($token) ?? $token,
            $tokens
        ));
    }
}

