<?php

namespace App\Http\Controllers;

use App\Http\Requests\BookingAdjustment\BookingAdjustmentLifecycleRequest;
use App\Http\Requests\BookingAdjustment\StoreBookingAdjustmentRequest;
use App\Http\Requests\BookingAdjustment\UpdateBookingAdjustmentRequest;
use App\Models\Booking;
use App\Models\BookingAdjustment;
use App\Services\AdjustmentService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Throwable;

class BookingAdjustmentController extends Controller
{
    public function __construct(private readonly AdjustmentService $adjustmentService)
    {
    }

    public function index(Request $request, Booking $booking)
    {
        $this->authorizeAction('booking_adjustments.view');

        $validated = $request->validate([
            'status' => ['nullable', Rule::in(BookingAdjustment::STATUS_OPTIONS)],
            'adjustment_type' => ['nullable', Rule::in(BookingAdjustment::TYPE_OPTIONS)],
        ]);

        $adjustments = $booking->adjustments()
            ->with(['bookingItem', 'invoice', 'payment', 'generatedInvoice', 'requester', 'approver', 'applier'])
            ->when($validated['status'] ?? null, fn ($q) => $q->where('status', $validated['status']))
            ->when($validated['adjustment_type'] ?? null, fn ($q) => $q->where('adjustment_type', $validated['adjustment_type']))
            ->latest('id')
            ->paginate(10)
            ->withQueryString();

        return view('modules.booking-adjustments.index', compact('booking', 'adjustments'));
    }

    public function create(Booking $booking)
    {
        $this->authorizeAction('booking_adjustments.create');
        $booking->loadMissing(['items', 'invoices', 'invoices.payments']);

        return view('modules.booking-adjustments.create', compact('booking'));
    }

    public function store(StoreBookingAdjustmentRequest $request, Booking $booking)
    {
        try {
            $adjustment = $this->adjustmentService->createDraft($booking, $request->validated(), (int) (auth()->id() ?? 0));
        } catch (Throwable $e) {
            return back()->withInput()->withErrors(['adjustment' => ui_phrase($e->getMessage())]);
        }

        return redirect()->route('booking-adjustments.show', $adjustment)->with('success', ui_phrase('Adjustment created successfully.'));
    }

    public function show(BookingAdjustment $adjustment)
    {
        $this->authorizeAction('booking_adjustments.view');
        $adjustment->load(['booking.quotation.inquiry.customer', 'bookingItem', 'invoice', 'payment', 'generatedInvoice', 'requester', 'approver', 'rejecter', 'applier']);

        return view('modules.booking-adjustments.show', compact('adjustment'));
    }

    public function edit(BookingAdjustment $adjustment)
    {
        $this->authorizeAction('booking_adjustments.update');
        if (! $adjustment->isDraft()) {
            return redirect()->route('booking-adjustments.show', $adjustment)->withErrors(['adjustment' => ui_phrase('Only draft adjustment can be edited.')]);
        }

        $adjustment->loadMissing(['booking.items', 'booking.invoices', 'booking.invoices.payments']);
        $booking = $adjustment->booking;

        return view('modules.booking-adjustments.edit', compact('adjustment', 'booking'));
    }

    public function update(UpdateBookingAdjustmentRequest $request, BookingAdjustment $adjustment)
    {
        try {
            $adjustment = $this->adjustmentService->updateDraft($adjustment, $request->validated(), (int) (auth()->id() ?? 0));
        } catch (Throwable $e) {
            return back()->withInput()->withErrors(['adjustment' => ui_phrase($e->getMessage())]);
        }

        return redirect()->route('booking-adjustments.show', $adjustment)->with('success', ui_phrase('Adjustment updated successfully.'));
    }

    public function submit(BookingAdjustmentLifecycleRequest $request, BookingAdjustment $adjustment)
    {
        $this->authorizeAction('booking_adjustments.submit');
        try {
            $adjustment = $this->adjustmentService->submitForApproval($adjustment, (int) (auth()->id() ?? 0));
        } catch (Throwable $e) {
            return back()->withErrors(['adjustment' => ui_phrase($e->getMessage())]);
        }

        return redirect()->route('booking-adjustments.show', $adjustment)->with('success', ui_phrase('Adjustment submitted for approval.'));
    }

    public function approve(BookingAdjustmentLifecycleRequest $request, BookingAdjustment $adjustment)
    {
        $this->authorizeAction('booking_adjustments.approve');
        try {
            $adjustment = $this->adjustmentService->approve($adjustment, (int) (auth()->id() ?? 0));
        } catch (Throwable $e) {
            return back()->withErrors(['adjustment' => ui_phrase($e->getMessage())]);
        }

        return redirect()->route('booking-adjustments.show', $adjustment)->with('success', ui_phrase('Adjustment approved.'));
    }

    public function reject(BookingAdjustmentLifecycleRequest $request, BookingAdjustment $adjustment)
    {
        $this->authorizeAction('booking_adjustments.reject');
        try {
            $adjustment = $this->adjustmentService->reject($adjustment, (int) (auth()->id() ?? 0), (string) ($request->validated('rejection_reason') ?? $request->validated('reason') ?? ''));
        } catch (Throwable $e) {
            return back()->withErrors(['adjustment' => ui_phrase($e->getMessage())]);
        }

        return redirect()->route('booking-adjustments.show', $adjustment)->with('success', ui_phrase('Adjustment rejected.'));
    }

    public function apply(BookingAdjustmentLifecycleRequest $request, BookingAdjustment $adjustment)
    {
        $this->authorizeAction('booking_adjustments.apply');
        try {
            $adjustment = $this->adjustmentService->apply($adjustment, (int) (auth()->id() ?? 0));
        } catch (Throwable $e) {
            return back()->withErrors(['adjustment' => ui_phrase($e->getMessage())]);
        }

        return redirect()->route('booking-adjustments.show', $adjustment)->with('success', ui_phrase('Adjustment applied successfully.'));
    }

    public function cancel(BookingAdjustmentLifecycleRequest $request, BookingAdjustment $adjustment)
    {
        $this->authorizeAction('booking_adjustments.cancel');
        try {
            $adjustment = $this->adjustmentService->cancel($adjustment, (int) (auth()->id() ?? 0), (string) ($request->validated('reason') ?? ''));
        } catch (Throwable $e) {
            return back()->withErrors(['adjustment' => ui_phrase($e->getMessage())]);
        }

        return redirect()->route('booking-adjustments.show', $adjustment)->with('success', ui_phrase('Adjustment cancelled.'));
    }

    private function authorizeAction(string $permission): void
    {
        if (! request()->user()?->can($permission)) {
            abort(403);
        }
    }
}
