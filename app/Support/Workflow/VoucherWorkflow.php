<?php

namespace App\Support\Workflow;

class VoucherWorkflow
{
    public static function statuses(): array
    {
        return [
            'draft',
            'generated',
            'sent_to_vendor',
            'confirmed_by_vendor',
            'reissued',
            'cancelled',
            'used',
        ];
    }

    public static function editableStatuses(): array
    {
        return array_values(array_diff(self::statuses(), self::lockedStatuses()));
    }

    public static function lockedStatuses(): array
    {
        return ['cancelled', 'used'];
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
            'draft' => ['generated', 'cancelled'],
            'generated' => ['sent_to_vendor', 'reissued', 'cancelled'],
            'sent_to_vendor' => ['confirmed_by_vendor', 'reissued', 'cancelled'],
            'confirmed_by_vendor' => ['used', 'reissued', 'cancelled'],
            'reissued' => ['sent_to_vendor', 'confirmed_by_vendor', 'cancelled'],
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
            'draft' => 'slate',
            'generated' => 'indigo',
            'sent_to_vendor' => 'amber',
            'confirmed_by_vendor' => 'cyan',
            'reissued' => 'violet',
            'cancelled' => 'rose',
            'used' => 'emerald',
        ];

        return $palette[$status] ?? 'slate';
    }
}

