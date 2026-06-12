<?php

namespace App\Support\Workflow;

class BookingItemWorkflow
{
    public static function statuses(): array
    {
        return [
            'pending_vendor',
            'confirmed_by_vendor',
            'voucher_generated',
            'used',
            'not_used',
            'cancelled_free',
            'cancelled_with_charge',
            'replaced',
            'completed',
        ];
    }

    public static function editableStatuses(): array
    {
        return array_values(array_diff(self::statuses(), self::lockedStatuses()));
    }

    public static function lockedStatuses(): array
    {
        return ['used', 'completed', 'cancelled_free', 'cancelled_with_charge'];
    }

    public static function canTransition(string $from, string $to): bool
    {
        if (! in_array($from, self::statuses(), true) || ! in_array($to, self::statuses(), true)) {
            return false;
        }

        if ($from === $to) {
            return true;
        }

        $map = [
            'pending_vendor' => ['confirmed_by_vendor', 'replaced', 'cancelled_free', 'cancelled_with_charge'],
            'confirmed_by_vendor' => ['voucher_generated', 'replaced', 'cancelled_free', 'cancelled_with_charge'],
            'voucher_generated' => ['used', 'not_used', 'cancelled_free', 'cancelled_with_charge'],
            'not_used' => ['completed', 'cancelled_free', 'cancelled_with_charge'],
            'replaced' => ['pending_vendor', 'confirmed_by_vendor'],
            'used' => ['completed'],
        ];

        return in_array($to, $map[$from] ?? [], true);
    }

    public static function label(string $status): string
    {
        return ucwords(str_replace('_', ' ', $status));
    }

    public static function color(string $status): string
    {
        $palette = [
            'pending_vendor' => 'amber',
            'confirmed_by_vendor' => 'indigo',
            'voucher_generated' => 'violet',
            'used' => 'emerald',
            'not_used' => 'slate',
            'cancelled_free' => 'rose',
            'cancelled_with_charge' => 'rose',
            'replaced' => 'sky',
            'completed' => 'emerald',
        ];

        return $palette[$status] ?? 'slate';
    }
}

