<?php

namespace App\Support\Workflow;

class InvoiceWorkflow
{
    public static function statuses(): array
    {
        return [
            'draft',
            'issued',
            'partially_paid',
            'paid',
            'revised',
            'void',
            'cancelled',
        ];
    }

    public static function editableStatuses(): array
    {
        return array_values(array_diff(self::statuses(), self::lockedStatuses()));
    }

    public static function lockedStatuses(): array
    {
        return ['paid', 'void', 'cancelled'];
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
            'draft' => ['issued', 'cancelled'],
            'issued' => ['partially_paid', 'paid', 'revised', 'void', 'cancelled'],
            'partially_paid' => ['paid', 'revised', 'void'],
            'revised' => ['issued', 'partially_paid', 'paid', 'void', 'cancelled'],
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
            'issued' => 'indigo',
            'partially_paid' => 'amber',
            'paid' => 'emerald',
            'revised' => 'violet',
            'void' => 'rose',
            'cancelled' => 'rose',
        ];

        return $palette[$status] ?? 'slate';
    }
}

