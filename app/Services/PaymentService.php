<?php

namespace App\Services;

use App\Models\ActivityLog;
use App\Models\Invoice;
use App\Models\Payment;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class PaymentService
{
    public function createPayment(Invoice $invoice, array $payload, ?UploadedFile $proofFile, int $actorId): Payment
    {
        return DB::transaction(function () use ($invoice, $payload, $proofFile, $actorId): Payment {
            $invoice->refresh();

            $type = (string) ($payload['payment_type'] ?? 'full_payment');
            if (! $invoice->canReceivePayment($type)) {
                throw new \RuntimeException('Invoice cannot receive payment for current status.');
            }

            $proofPath = null;
            if ($proofFile) {
                $proofPath = $proofFile->store('payments/proofs', 'public');
            }

            $payment = Payment::query()->create([
                'invoice_id' => $invoice->id,
                'payment_number' => $this->generatePaymentNumber(),
                'payment_type' => $type,
                'payment_date' => $payload['payment_date'],
                'amount' => (float) ($payload['amount'] ?? 0),
                'currency_code' => strtoupper((string) ($payload['currency_code'] ?? 'IDR')),
                'method' => $payload['method'] ?? null,
                'reference_number' => $payload['reference_number'] ?? null,
                'proof_path' => $proofPath,
                'status' => (string) ($payload['status'] ?? 'pending'),
                'notes' => $payload['notes'] ?? null,
                'created_by' => $actorId ?: null,
            ]);

            if ($payment->status === 'confirmed') {
                $payment->confirmed_by = $actorId ?: null;
                $payment->confirmed_at = now();
                $payment->save();
            }

            $this->recalculateInvoicePaymentState($invoice->fresh(), $actorId);
            $this->logInvoicePaymentEvent(
                'payment.created',
                $invoice,
                $actorId,
                [
                    'payment_id' => $payment->id,
                    'payment_number' => $payment->payment_number,
                    'status' => $payment->status,
                ]
            );

            return $payment->fresh();
        });
    }

    public function confirmPayment(Payment $payment, int $actorId): Payment
    {
        return DB::transaction(function () use ($payment, $actorId): Payment {
            $payment->refresh();
            if (! $payment->canBeConfirmed()) {
                throw new \RuntimeException('Payment cannot be confirmed.');
            }
            $invoice = $payment->invoice()->firstOrFail();
            if (! in_array((string) $invoice->status, ['issued', 'partially_paid', 'paid', 'overpaid'], true)) {
                throw new \RuntimeException('Payment cannot be confirmed for current invoice status.');
            }

            $payment->status = 'confirmed';
            $payment->confirmed_by = $actorId ?: null;
            $payment->confirmed_at = now();
            $payment->rejected_by = null;
            $payment->rejected_at = null;
            $payment->rejection_reason = null;
            $payment->save();

            $this->recalculateInvoicePaymentState($invoice, $actorId);
            $this->logInvoicePaymentEvent(
                'payment.confirmed',
                $invoice,
                $actorId,
                ['payment_id' => $payment->id, 'payment_number' => $payment->payment_number]
            );

            return $payment->fresh();
        });
    }

    public function rejectPayment(Payment $payment, int $actorId, ?string $reason = null): Payment
    {
        return DB::transaction(function () use ($payment, $actorId, $reason): Payment {
            $payment->refresh();
            if (! $payment->canBeRejected()) {
                throw new \RuntimeException('Payment cannot be rejected.');
            }
            $invoice = $payment->invoice()->firstOrFail();
            if (! in_array((string) $invoice->status, ['issued', 'partially_paid', 'paid', 'overpaid'], true)) {
                throw new \RuntimeException('Payment cannot be rejected for current invoice status.');
            }

            $payment->status = 'rejected';
            $payment->rejected_by = $actorId ?: null;
            $payment->rejected_at = now();
            $payment->rejection_reason = trim((string) $reason) ?: null;
            $payment->confirmed_by = null;
            $payment->confirmed_at = null;
            $payment->save();

            $this->recalculateInvoicePaymentState($invoice, $actorId);
            $this->logInvoicePaymentEvent(
                'payment.rejected',
                $invoice,
                $actorId,
                ['payment_id' => $payment->id, 'payment_number' => $payment->payment_number, 'reason' => $payment->rejection_reason]
            );

            return $payment->fresh();
        });
    }

    public function cancelPayment(Payment $payment, int $actorId, ?string $note = null): Payment
    {
        return DB::transaction(function () use ($payment, $note, $actorId): Payment {
            $payment->refresh();
            if (! $payment->isCancellable()) {
                throw new \RuntimeException('Payment cannot be cancelled.');
            }
            $invoice = $payment->invoice()->firstOrFail();
            if (! in_array((string) $invoice->status, ['issued', 'partially_paid', 'paid', 'overpaid'], true)) {
                throw new \RuntimeException('Payment cannot be cancelled for current invoice status.');
            }

            $payment->status = 'cancelled';
            if (trim((string) $note) !== '') {
                $payment->notes = trim((string) $note);
            }
            $payment->save();

            $this->recalculateInvoicePaymentState($invoice, $actorId);
            $this->logInvoicePaymentEvent(
                'payment.cancelled',
                $invoice,
                $actorId,
                ['payment_id' => $payment->id, 'payment_number' => $payment->payment_number]
            );

            return $payment->fresh();
        });
    }

    public function recalculateInvoicePaymentState(Invoice $invoice, ?int $actorId = null): Invoice
    {
        $invoice->loadMissing('confirmedPayments');
        $confirmedPaid = (float) $invoice->confirmedPayments()->sum('amount');
        $before = [
            'status' => $invoice->status,
            'paid_amount' => (float) ($invoice->paid_amount ?? 0),
            'balance_amount' => (float) ($invoice->balance_amount ?? 0),
        ];
        $invoice->paid_amount = $confirmedPaid;
        $invoice->recalculateBalance();
        $invoice->save();
        $fresh = $invoice->fresh();
        $after = [
            'status' => $fresh->status,
            'paid_amount' => (float) ($fresh->paid_amount ?? 0),
            'balance_amount' => (float) ($fresh->balance_amount ?? 0),
        ];

        if ($before !== $after) {
            $this->logInvoicePaymentEvent('invoice.payment_state_recalculated', $fresh, $actorId, [
                'before' => $before,
                'after' => $after,
            ]);
        }

        return $fresh;
    }

    public function generatePaymentNumber(): string
    {
        do {
            $number = 'PAY-' . now()->format('Ymd') . '-' . random_int(1000, 9999);
        } while (Payment::query()->where('payment_number', $number)->exists());

        return $number;
    }

    private function logInvoicePaymentEvent(string $action, Invoice $invoice, ?int $actorId, array $properties = []): void
    {
        ActivityLog::query()->create([
            'user_id' => $actorId,
            'module' => 'Invoice',
            'action' => $action,
            'subject_id' => $invoice->getKey(),
            'subject_type' => $invoice->getMorphClass(),
            'properties' => $properties !== [] ? $properties : null,
        ]);
    }
}
