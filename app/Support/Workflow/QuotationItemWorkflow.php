<?php

namespace App\Support\Workflow;

class QuotationItemWorkflow
{
    public static function statuses(): array
    {
        return [
            'active',
            'validated',
            'vendor_pending',
            'vendor_confirmed',
            'voucher_generated',
            'used',
            'cancelled_free',
            'cancelled_with_charge',
            'not_available',
            'replaced',
            'added_after_approval',
        ];
    }

    public static function editableStatuses(): array
    {
        return array_values(array_diff(self::statuses(), self::lockedStatuses()));
    }

    public static function lockedStatuses(): array
    {
        return ['used', 'cancelled_free', 'cancelled_with_charge'];
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
            'active' => ['validated', 'cancelled_free', 'cancelled_with_charge'],
            'validated' => ['vendor_pending', 'cancelled_free', 'cancelled_with_charge'],
            'vendor_pending' => ['vendor_confirmed', 'not_available', 'cancelled_free', 'cancelled_with_charge'],
            'vendor_confirmed' => ['voucher_generated', 'cancelled_free', 'cancelled_with_charge'],
            'voucher_generated' => ['used', 'cancelled_free', 'cancelled_with_charge'],
            'not_available' => ['replaced', 'cancelled_free', 'cancelled_with_charge'],
            'replaced' => ['vendor_pending', 'vendor_confirmed'],
            'added_after_approval' => ['vendor_pending', 'vendor_confirmed', 'voucher_generated'],
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
            'active' => 'emerald',
            'validated' => 'cyan',
            'vendor_pending' => 'amber',
            'vendor_confirmed' => 'indigo',
            'voucher_generated' => 'violet',
            'used' => 'emerald',
            'cancelled_free' => 'rose',
            'cancelled_with_charge' => 'rose',
            'not_available' => 'rose',
            'replaced' => 'sky',
            'added_after_approval' => 'orange',
        ];

        return $palette[$status] ?? 'slate';
    }
}

