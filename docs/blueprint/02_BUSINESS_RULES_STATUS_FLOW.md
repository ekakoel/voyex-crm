# BUSINESS RULES & STATUS FLOW

## 1. Inquiry Status

```php
[
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
]
```

### Final Inquiry Status

```php
[
    'converted_to_booking',
    'lost',
    'cancelled',
    'expired',
    'unqualified',
]
```

### Transition Ideal

```text
new_request
→ registered
→ assigned
→ contacted
→ waiting_customer
→ qualified
→ itinerary_in_progress
→ quotation_in_progress
→ quotation_sent
→ under_negotiation
→ accepted
→ converted_to_booking
```

## 2. Itinerary Status

```php
[
    'draft',
    'in_review',
    'approved',
    'quotation_generated',
    'revised',
    'confirmed',
    'converted_to_booking',
    'cancelled',
    'archived',
]
```

### Rule

- `draft`: boleh diedit.
- `approved`: boleh generate quotation.
- `quotation_generated`: sudah menjadi sumber quotation.
- `confirmed`: customer menyetujui itinerary.
- `converted_to_booking`: terkunci, perubahan via adjustment.

## 3. Quotation Status

```php
[
    'draft',
    'pending_validation',
    'validated',
    'sent',
    'revision_requested',
    'revised',
    'accepted',
    'rejected',
    'expired',
    'converted',
    'superseded',
    'amended',
    'cancelled',
]
```

### Transition Ideal

```text
draft
→ pending_validation
→ validated
→ sent
→ revision_requested / accepted / rejected / expired
```

Jika revisi:

```text
quotation_v1.sent
→ quotation_v1.superseded
→ quotation_v2.draft
→ quotation_v2.pending_validation
→ quotation_v2.validated
→ quotation_v2.sent
```

## 4. Quotation Validation Status

```php
[
    'pending',
    'valid',
    'invalid',
    'need_update',
    'expired_rate',
]
```

### Rule

- Semua item chargeable harus valid sebelum quotation sent.
- Validasi menyimpan snapshot rate.
- Validasi tidak boleh menghapus history rate lama.

## 5. Booking Status

```php
[
    'pending_confirmation',
    'confirmed',
    'awaiting_dp',
    'dp_received',
    'awaiting_balance',
    'ready_to_operate',
    'in_operation',
    'service_completed',
    'completed_unsettled',
    'completed_settled',
    'closed',
    'cancelled',
]
```

### Rule

- Booking dibuat dari accepted quotation.
- Booking closed hanya jika settlement settled.

## 6. Invoice Status

```php
[
    'draft',
    'issued',
    'partially_paid',
    'paid',
    'overpaid',
    'revised',
    'void',
    'cancelled',
]
```

## 7. Payment Status

```php
[
    'pending',
    'waiting_confirmation',
    'confirmed',
    'rejected',
    'refunded',
    'allocated_as_deposit',
]
```

## 8. Operation Status

```php
[
    'not_started',
    'preparing',
    'ready_to_operate',
    'in_operation',
    'service_completed',
    'issue_reported',
]
```

## 9. Adjustment Status

```php
[
    'draft',
    'pending_approval',
    'approved',
    'rejected',
    'applied',
    'cancelled',
]
```

## 10. Settlement Status

```php
[
    'pending_review',
    'outstanding_balance',
    'overpaid',
    'refund_required',
    'deposit_recorded',
    'settled',
]
```

## 11. Rule Locking Data

| Kondisi | Action |
|---|---|
| Inquiry final | Lock update utama |
| Itinerary converted_to_booking | Lock edit langsung |
| Quotation accepted/converted | Lock edit langsung |
| Invoice issued/paid | Lock edit langsung |
| Payment confirmed | Lock delete/update amount |
| Adjustment applied | Lock edit langsung |
| Booking closed | Read-only |
