<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Services\SettlementService;
use Illuminate\Http\Request;
use Throwable;

class BookingSettlementController extends Controller
{
    public function __construct(private readonly SettlementService $settlementService)
    {
    }

    public function show(Booking $booking)
    {
        $this->authorizeAction('booking_settlements.view');

        $booking->load([
            'quotation.inquiry.customer',
            'invoices.payments',
            'adjustments.generatedInvoice',
            'settlement.reviewer',
            'settlement.finalizer',
        ]);

        $settlement = $booking->settlement;
        $summary = $this->settlementService->calculateSettlementSummary($booking);
        $blockers = $this->settlementService->detectBlockingIssues($booking, $summary);

        return view('modules.booking-settlements.show', compact('booking', 'settlement', 'summary', 'blockers'));
    }

    public function review(Request $request, Booking $booking)
    {
        $this->authorizeAction('booking_settlements.review');
        $validated = $request->validate([
            'settlement_notes' => ['nullable', 'string', 'max:5000'],
        ]);

        try {
            $this->settlementService->reviewBooking($booking, (int) (auth()->id() ?? 0), (string) ($validated['settlement_notes'] ?? ''));
        } catch (Throwable $e) {
            return back()->withErrors(['settlement' => ui_phrase($e->getMessage())]);
        }

        return redirect()->route('bookings.settlement.show', $booking)->with('success', ui_phrase('Settlement has been reviewed.'));
    }

    public function markSettled(Request $request, Booking $booking)
    {
        $this->authorizeAction('booking_settlements.mark_settled');
        $validated = $request->validate([
            'settlement_notes' => ['nullable', 'string', 'max:5000'],
        ]);

        try {
            $this->settlementService->markSettled($booking, (int) (auth()->id() ?? 0), (string) ($validated['settlement_notes'] ?? ''));
        } catch (Throwable $e) {
            return back()->withErrors(['settlement' => ui_phrase($e->getMessage())]);
        }

        return redirect()->route('bookings.settlement.show', $booking)->with('success', ui_phrase('Settlement marked as settled.'));
    }

    public function close(Request $request, Booking $booking)
    {
        $this->authorizeAction('booking_settlements.close_booking');
        $validated = $request->validate([
            'settlement_notes' => ['nullable', 'string', 'max:5000'],
        ]);

        try {
            $this->settlementService->closeBooking($booking, (int) (auth()->id() ?? 0), (string) ($validated['settlement_notes'] ?? ''));
        } catch (Throwable $e) {
            return back()->withErrors(['settlement' => ui_phrase($e->getMessage())]);
        }

        return redirect()->route('bookings.show', $booking)->with('success', ui_phrase('Booking closed successfully.'));
    }

    private function authorizeAction(string $permission): void
    {
        if (! request()->user()?->can($permission)) {
            abort(403);
        }
    }
}
