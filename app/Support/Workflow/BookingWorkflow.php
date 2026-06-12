<?php

namespace App\Support\Workflow;

class BookingWorkflow
{
    public static function statuses(): array
    {
        return [
            'created',
            'vendor_confirmation',
            'voucher_preparation',
            'ready_to_operate',
            'in_operation',
            'service_completed',
            'reconciliation',
            'invoiced',
            'closed',
            'cancelled',
        ];
    }

    public static function editableStatuses(): array
    {
        return array_values(array_diff(self::statuses(), self::lockedStatuses()));
    }

    public static function lockedStatuses(): array
    {
        return ['closed', 'cancelled'];
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
            'created' => ['vendor_confirmation', 'cancelled'],
            'vendor_confirmation' => ['voucher_preparation', 'cancelled'],
            'voucher_preparation' => ['ready_to_operate', 'cancelled'],
            'ready_to_operate' => ['in_operation', 'cancelled'],
            'in_operation' => ['service_completed', 'cancelled'],
            'service_completed' => ['reconciliation', 'cancelled'],
            'reconciliation' => ['invoiced', 'cancelled'],
            'invoiced' => ['closed', 'cancelled'],
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
            'created' => 'slate',
            'vendor_confirmation' => 'amber',
            'voucher_preparation' => 'indigo',
            'ready_to_operate' => 'cyan',
            'in_operation' => 'sky',
            'service_completed' => 'emerald',
            'reconciliation' => 'violet',
            'invoiced' => 'teal',
            'closed' => 'emerald',
            'cancelled' => 'rose',
        ];

        return $palette[$status] ?? 'slate';
    }
}

