<?php

namespace App\Http\Controllers\Concerns;

use App\Models\ServiceCancellationPolicy;
use Illuminate\Database\Eloquent\Model;

trait ManagesServiceCancellationPolicy
{
    protected function resolveCancellationPolicyRules(?Model $serviceable): array
    {
        if (! $serviceable || ! $serviceable->exists) {
            return [];
        }

        $policy = ServiceCancellationPolicy::query()
            ->with('rules')
            ->where('serviceable_type', $serviceable->getMorphClass())
            ->where('serviceable_id', (int) $serviceable->getKey())
            ->where('is_active', true)
            ->latest('id')
            ->first();

        if (! $policy) {
            return [];
        }

        return $policy->rules->map(fn ($rule) => [
            'min_days_before' => $rule->min_days_before,
            'max_days_before' => $rule->max_days_before,
            'fee_type' => (string) ($rule->fee_type ?? 'fixed'),
            'fee_value' => (float) ($rule->fee_value ?? 0),
            'description' => (string) ($rule->description ?? ''),
        ])->values()->all();
    }

    protected function syncCancellationPolicy(Model $serviceable, mixed $rawRules, ?string $name = null): void
    {
        $rules = $this->normalizeCancellationPolicyRules($rawRules);

        $policy = ServiceCancellationPolicy::query()->firstOrNew([
            'serviceable_type' => $serviceable->getMorphClass(),
            'serviceable_id' => (int) $serviceable->getKey(),
        ]);

        $policy->name = trim((string) ($name ?: ($serviceable->name ?? class_basename($serviceable))));
        $policy->is_active = $rules !== [];
        $policy->save();

        $policy->rules()->delete();
        foreach ($rules as $index => $rule) {
            $policy->rules()->create([
                'min_days_before' => $rule['min_days_before'],
                'max_days_before' => $rule['max_days_before'],
                'fee_type' => $rule['fee_type'],
                'fee_value' => $rule['fee_value'],
                'description' => $rule['description'],
                'sort_order' => $index,
            ]);
        }
    }

    protected function normalizeCancellationPolicyRules(mixed $rawRules): array
    {
        if (! is_array($rawRules)) {
            return [];
        }

        $normalized = [];
        foreach ($rawRules as $row) {
            if (! is_array($row)) {
                continue;
            }

            $feeType = strtolower(trim((string) ($row['fee_type'] ?? 'fixed')));
            if (! in_array($feeType, ['fixed', 'percent'], true)) {
                $feeType = 'fixed';
            }

            $feeValue = max(0, (float) ($row['fee_value'] ?? 0));
            $min = isset($row['min_days_before']) && $row['min_days_before'] !== '' ? max(0, (int) $row['min_days_before']) : null;
            $max = isset($row['max_days_before']) && $row['max_days_before'] !== '' ? max(0, (int) $row['max_days_before']) : null;
            $description = trim((string) ($row['description'] ?? ''));

            if ($min === null && $max === null && $feeValue <= 0 && $description === '') {
                continue;
            }

            $normalized[] = [
                'min_days_before' => $min,
                'max_days_before' => $max,
                'fee_type' => $feeType,
                'fee_value' => $feeValue,
                'description' => $description,
            ];
        }

        return $normalized;
    }
}

