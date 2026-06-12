<?php

namespace App\Support\Workflow;

class InquiryWorkflow
{
    public static function statuses(): array
    {
        return [
            'new_request',
            'need_customer_data',
            'registered',
            'assigned',
            'contacted',
            'waiting_customer',
            'qualified',
            'unqualified',
            'itinerary_in_progress',
            'quotation_in_progress',
            'quotation_sent',
            'under_negotiation',
            'accepted',
            'converted_to_booking',
            'lost',
            'cancelled',
            'expired',
        ];
    }

    public static function editableStatuses(): array
    {
        return array_values(array_diff(self::statuses(), self::lockedStatuses()));
    }

    public static function lockedStatuses(): array
    {
        return ['converted_to_booking', 'lost', 'cancelled', 'expired', 'unqualified'];
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
            'new_request' => ['need_customer_data', 'registered', 'cancelled'],
            'need_customer_data' => ['registered', 'cancelled'],
            'registered' => ['assigned', 'contacted', 'cancelled'],
            'assigned' => ['contacted', 'cancelled'],
            'contacted' => ['waiting_customer', 'qualified', 'unqualified', 'lost', 'cancelled'],
            'waiting_customer' => ['contacted', 'qualified', 'lost', 'expired', 'cancelled'],
            'qualified' => ['itinerary_in_progress', 'quotation_in_progress', 'cancelled'],
            'itinerary_in_progress' => ['quotation_in_progress', 'cancelled'],
            'quotation_in_progress' => ['quotation_sent', 'under_negotiation', 'cancelled'],
            'quotation_sent' => ['under_negotiation', 'accepted', 'lost', 'expired', 'cancelled'],
            'under_negotiation' => ['quotation_sent', 'accepted', 'lost', 'expired', 'cancelled'],
            'accepted' => ['converted_to_booking', 'cancelled'],
        ];

        return in_array($to, $map[$from] ?? [], true);
    }

    public static function label(string $status): string
    {
        return self::humanize($status);
    }

    public static function color(string $status): string
    {
        $palette = [
            'new_request' => 'blue',
            'need_customer_data' => 'amber',
            'waiting_customer' => 'amber',
            'qualified' => 'sky',
            'accepted' => 'emerald',
            'converted_to_booking' => 'violet',
            'lost' => 'rose',
            'cancelled' => 'rose',
            'expired' => 'rose',
            'unqualified' => 'rose',
        ];

        return $palette[$status] ?? 'slate';
    }

    private static function humanize(string $value): string
    {
        return ucwords(str_replace('_', ' ', $value));
    }
}

