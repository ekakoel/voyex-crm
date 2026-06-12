<?php

namespace App\Services;

use App\Models\BookingItem;
use App\Models\Hotel;
use App\Models\HotelRoom;
use App\Models\ServiceCancellationPolicy;
use Carbon\Carbon;

class CancellationPolicyService
{
    public function calculateCancellationFee(BookingItem $item, Carbon $cancelledAt): float
    {
        $result = $this->resolveCancellation($item, $cancelledAt);

        return (float) ($result['fee'] ?? 0);
    }

    public function resolveCancellation(BookingItem $item, Carbon $cancelledAt): array
    {
        [$serviceableType, $serviceableId] = $this->resolvePolicyTargetFromBookingItem($item);
        if ($serviceableType === '' || $serviceableId <= 0) {
            return [
                'matched' => false,
                'fee' => 0.0,
                'policy' => null,
                'reason' => 'missing_target',
            ];
        }

        $serviceDate = $item->service_date
            ? Carbon::parse((string) $item->service_date)
            : ($item->booking?->travel_date ? Carbon::parse((string) $item->booking->travel_date) : null);
        if (! $serviceDate) {
            return [
                'matched' => false,
                'fee' => 0.0,
                'policy' => null,
                'reason' => 'missing_service_date',
            ];
        }

        $hoursBefore = $cancelledAt->diffInHours($serviceDate, false);
        $vendorId = (int) ($item->vendor_id ?? 0);

        $policy = ServiceCancellationPolicy::query()
            ->with('rules')
            ->where('is_active', true)
            ->where('serviceable_type', $serviceableType)
            ->where('serviceable_id', $serviceableId)
            ->when($vendorId > 0, function ($query) use ($vendorId): void {
                $query->where(function ($q) use ($vendorId): void {
                    $q->whereNull('vendor_id')->orWhere('vendor_id', $vendorId);
                });
            })
            ->when($serviceDate, function ($query) use ($serviceDate): void {
                $query->where(function ($q) use ($serviceDate): void {
                    $q->whereNull('start_date')->orWhereDate('start_date', '<=', $serviceDate->toDateString());
                })->where(function ($q) use ($serviceDate): void {
                    $q->whereNull('end_date')->orWhereDate('end_date', '>=', $serviceDate->toDateString());
                });
            })
            ->orderByDesc('vendor_id')
            ->orderByDesc('id')
            ->first();

        if (! $policy) {
            return [
                'matched' => false,
                'fee' => 0.0,
                'policy' => null,
                'reason' => 'no_policy',
            ];
        }

        $fee = null;
        if ($policy->fee_type !== null) {
            if ($policy->cancel_before_hours !== null && $hoursBefore < (int) $policy->cancel_before_hours) {
                return [
                    'matched' => false,
                    'fee' => 0.0,
                    'policy' => $policy,
                    'reason' => 'outside_window',
                ];
            }
            $fee = $this->applyFee(
                (string) $policy->fee_type,
                (float) ($policy->fee_value ?? 0),
                (float) ($item->total ?? 0)
            );
        }

        if ($fee === null) {
            $ruleResult = $this->resolveFeeFromRules($policy, $hoursBefore, (float) ($item->total ?? 0));
            $fee = $ruleResult['fee'];
        }

        return [
            'matched' => true,
            'fee' => max(0, round((float) $fee, 2)),
            'policy' => $policy,
            'hours_before' => $hoursBefore,
        ];
    }

    private function resolveFeeFromRules(ServiceCancellationPolicy $policy, int $hoursBefore, float $itemTotal): array
    {
        $rules = $policy->rules ?? collect();
        if ($rules->isEmpty()) {
            return ['fee' => 0.0];
        }

        $daysBefore = (int) floor($hoursBefore / 24);
        $matchedRule = $rules->first(function ($rule) use ($daysBefore): bool {
            $min = $rule->min_days_before !== null ? (int) $rule->min_days_before : null;
            $max = $rule->max_days_before !== null ? (int) $rule->max_days_before : null;
            if ($min !== null && $daysBefore < $min) {
                return false;
            }
            if ($max !== null && $daysBefore > $max) {
                return false;
            }

            return true;
        });

        if (! $matchedRule) {
            return ['fee' => 0.0];
        }

        return [
            'fee' => $this->applyFee((string) ($matchedRule->fee_type ?? 'fixed'), (float) ($matchedRule->fee_value ?? 0), $itemTotal),
        ];
    }

    private function applyFee(string $feeType, float $feeValue, float $itemTotal): float
    {
        $feeType = strtolower(trim($feeType));
        if ($feeType === ServiceCancellationPolicy::FEE_TYPE_FREE) {
            return 0.0;
        }
        if (in_array($feeType, ['percentage', 'percent'], true)) {
            return max(0, $itemTotal) * (max(0, $feeValue) / 100);
        }

        return max(0, $feeValue);
    }

    private function resolvePolicyTargetFromBookingItem(BookingItem $item): array
    {
        $serviceableType = (string) ($item->serviceable_type ?? '');
        $serviceableId = (int) ($item->serviceable_id ?? 0);

        if ($serviceableType === '' || $serviceableId <= 0) {
            return ['', 0];
        }

        if ($serviceableType === HotelRoom::class || class_basename($serviceableType) === 'HotelRoom') {
            $room = HotelRoom::query()->with('hotel:id,name')->find($serviceableId);
            $hotelId = (int) ($room?->hotel?->id ?? 0);
            if ($hotelId > 0) {
                $serviceableType = (new Hotel())->getMorphClass();
                $serviceableId = $hotelId;
            }
        }

        return [$serviceableType, $serviceableId];
    }
}

