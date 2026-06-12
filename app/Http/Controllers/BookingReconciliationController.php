<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\BookingItem;
use App\Services\BookingAdjustmentService;
use App\Services\CancellationPolicyService;
use App\Support\Workflow\BookingWorkflow;
use Illuminate\Http\Request;

class BookingReconciliationController extends Controller
{
    public function __construct(
        private readonly BookingAdjustmentService $bookingAdjustmentService,
        private readonly CancellationPolicyService $cancellationPolicyService
    ) {
    }

    public function show(Booking $booking)
    {
        $booking->load([
            'quotation.inquiry.customer',
            'items.voucher',
            'items.vendorConfirmer',
            'items.adjustments',
            'adjustments',
        ]);

        return view('modules.bookings.reconciliation', compact('booking'));
    }

    public function updateItem(Request $request, Booking $booking, BookingItem $bookingItem)
    {
        if ((int) $bookingItem->booking_id !== (int) $booking->id) {
            abort(404);
        }

        if ($this->isReconciliationLocked($booking)) {
            return redirect()
                ->route('bookings.reconciliation.show', $booking)
                ->with('error', ui_phrase('Reconciliation has been finalized. Booking items are locked.'));
        }

        $validated = $request->validate([
            'action' => ['required', 'in:mark_used,mark_not_used,cancel_free,cancel_with_charge'],
            'cancellation_fee' => ['nullable', 'numeric', 'min:0'],
            'reason' => ['nullable', 'string', 'max:2000'],
        ]);

        $action = (string) ($validated['action'] ?? '');
        $reason = trim((string) ($validated['reason'] ?? ''));

        if ($action === 'mark_used') {
            $bookingItem->update([
                'status' => BookingItem::STATUS_USED,
                'cancelled_at' => null,
                'cancellation_fee' => 0,
                'cancellation_fee_calculated' => 0,
                'cancellation_fee_overridden' => false,
            ]);
            $booking->logActivity('reconciliation.item_marked_used', $booking, [
                'booking_item_id' => $bookingItem->id,
                'description' => $bookingItem->description,
            ]);
        } elseif ($action === 'mark_not_used') {
            $bookingItem->update([
                'status' => BookingItem::STATUS_NOT_USED,
            ]);
            $booking->logActivity('reconciliation.item_marked_not_used', $booking, [
                'booking_item_id' => $bookingItem->id,
                'description' => $bookingItem->description,
            ]);
        } elseif ($action === 'cancel_free') {
            $bookingItem->update([
                'status' => BookingItem::STATUS_CANCELLED,
                'cancelled_at' => now(),
                'cancellation_fee' => 0,
                'cancellation_fee_calculated' => 0,
                'cancellation_fee_overridden' => false,
            ]);
            $booking->logActivity('reconciliation.item_cancelled_free', $booking, [
                'booking_item_id' => $bookingItem->id,
                'description' => $bookingItem->description,
                'reason' => $reason !== '' ? $reason : null,
            ]);
        } else {
            $fee = (float) ($validated['cancellation_fee'] ?? 0);
            if ($fee <= 0) {
                $resolved = $this->cancellationPolicyService->resolveCancellation($bookingItem, now());
                $fee = max(0, (float) ($resolved['fee'] ?? 0));
            }

            $bookingItem->update([
                'status' => BookingItem::STATUS_CANCELLED,
                'cancelled_at' => now(),
                'cancellation_fee' => $fee,
                'cancellation_fee_calculated' => $fee,
                'cancellation_fee_overridden' => true,
            ]);

            if ($fee > 0) {
                $adjustment = $this->bookingAdjustmentService->createCancellationFeeFromItem($bookingItem, $fee, $reason);
                $booking->logActivity('reconciliation.item_cancelled_with_charge', $booking, [
                    'booking_item_id' => $bookingItem->id,
                    'description' => $bookingItem->description,
                    'fee' => $fee,
                    'adjustment_id' => $adjustment?->id,
                ]);
            } else {
                $booking->logActivity('reconciliation.item_cancelled_with_charge', $booking, [
                    'booking_item_id' => $bookingItem->id,
                    'description' => $bookingItem->description,
                    'fee' => 0,
                ]);
            }
        }

        return redirect()
            ->route('bookings.reconciliation.show', $booking)
            ->with('success', ui_phrase('Reconciliation item updated.'));
    }

    public function finalize(Request $request, Booking $booking)
    {
        if ($this->isReconciliationLocked($booking)) {
            return redirect()
                ->route('bookings.reconciliation.show', $booking)
                ->with('success', ui_phrase('Reconciliation has already been finalized.'));
        }

        $from = (string) ($booking->status ?? '');
        $target = BookingWorkflow::canTransition($from, 'reconciliation')
            ? 'reconciliation'
            : (in_array($from, ['service_completed', 'reconciliation'], true) ? 'reconciliation' : 'service_completed');

        $booking->update(['status' => $target]);
        $booking->logActivity('reconciliation.finalized', $booking, [
            'from_status' => $from,
            'to_status' => $target,
            'finalized_by' => auth()->id(),
        ]);

        return redirect()
            ->route('bookings.reconciliation.show', $booking)
            ->with('success', ui_phrase('Reconciliation finalized successfully.'));
    }

    private function isReconciliationLocked(Booking $booking): bool
    {
        return in_array((string) ($booking->status ?? ''), ['reconciliation', 'completed_settled', 'closed', 'cancelled'], true);
    }
}

