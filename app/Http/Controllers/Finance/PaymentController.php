<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Http\Requests\Finance\PaymentLifecycleActionRequest;
use App\Http\Requests\Finance\StorePaymentRequest;
use App\Models\Invoice;
use App\Models\Payment;
use App\Services\PaymentService;
use Illuminate\Http\Request;
use Throwable;
use Illuminate\Validation\Rule;

class PaymentController extends Controller
{
    public function __construct(
        private readonly PaymentService $paymentService
    ) {
    }

    public function index(Request $request)
    {
        $validated = $request->validate([
            'status' => ['nullable', Rule::in(Payment::STATUS_OPTIONS)],
            'payment_type' => ['nullable', Rule::in(Payment::TYPE_OPTIONS)],
        ]);

        $query = Payment::query()->with(['invoice.booking.quotation.inquiry.customer', 'creator', 'confirmer', 'rejector']);
        $query->when($request->input('q'), function ($q) use ($request) {
            $term = (string) $request->input('q');
            $q->where('payment_number', 'like', "%{$term}%")
                ->orWhere('reference_number', 'like', "%{$term}%")
                ->orWhereHas('invoice', fn ($invoiceQ) => $invoiceQ->where('invoice_number', 'like', "%{$term}%"));
        });
        $query->when($validated['status'] ?? null, fn ($q) => $q->where('status', $validated['status']));
        $query->when($validated['payment_type'] ?? null, fn ($q) => $q->where('payment_type', $validated['payment_type']));
        $query->when($request->input('invoice_id'), fn ($q) => $q->where('invoice_id', (int) $request->input('invoice_id')));
        $query->when($request->input('payment_from'), fn ($q) => $q->whereDate('payment_date', '>=', $request->input('payment_from')));
        $query->when($request->input('payment_to'), fn ($q) => $q->whereDate('payment_date', '<=', $request->input('payment_to')));

        $perPage = (int) $request->input('per_page', 10);
        $perPage = $perPage > 0 && $perPage <= 100 ? $perPage : 10;
        $summaryQuery = clone $query;
        $summaries = [
            'total' => (clone $summaryQuery)->count(),
            'pending' => (clone $summaryQuery)->where('status', 'pending')->count(),
            'confirmed' => (clone $summaryQuery)->where('status', 'confirmed')->count(),
            'rejected' => (clone $summaryQuery)->where('status', 'rejected')->count(),
            'cancelled' => (clone $summaryQuery)->where('status', 'cancelled')->count(),
        ];

        $payments = $query->latest()->paginate($perPage)->withQueryString();

        return view('modules.payments.index', compact('payments', 'summaries'));
    }

    public function create(Request $request)
    {
        $invoiceId = (int) $request->input('invoice_id');
        $invoice = $invoiceId > 0 ? Invoice::query()->with('booking.quotation.inquiry.customer')->find($invoiceId) : null;
        $invoices = Invoice::query()
            ->with('booking.quotation.inquiry.customer')
            ->whereIn('status', ['issued', 'partially_paid', 'paid', 'overpaid'])
            ->latest('id')
            ->limit(200)
            ->get();

        return view('modules.payments.create', compact('invoice', 'invoices'));
    }

    public function store(StorePaymentRequest $request)
    {
        $validated = $request->validated();
        $invoice = Invoice::query()->findOrFail((int) $validated['invoice_id']);
        $actorId = (int) (auth()->id() ?? 0);

        try {
            $payment = $this->paymentService->createPayment(
                $invoice,
                $validated,
                $request->file('proof_file'),
                $actorId
            );
        } catch (Throwable $e) {
            return back()
                ->withInput()
                ->withErrors(['payment' => ui_phrase($e->getMessage())]);
        }

        return redirect()
            ->route('payments.show', $payment)
            ->with('success', ui_phrase('Payment recorded successfully.'));
    }

    public function show(Payment $payment)
    {
        $payment->load(['invoice.booking.quotation.inquiry.customer', 'creator', 'confirmer', 'rejector']);

        return view('modules.payments.show', compact('payment'));
    }

    public function confirm(PaymentLifecycleActionRequest $request, Payment $payment)
    {
        try {
            $payment = $this->paymentService->confirmPayment($payment, (int) (auth()->id() ?? 0));
        } catch (Throwable $e) {
            return back()->withErrors(['payment' => ui_phrase($e->getMessage())]);
        }

        return redirect()
            ->route('payments.show', $payment)
            ->with('success', ui_phrase('Payment confirmed successfully.'));
    }

    public function reject(PaymentLifecycleActionRequest $request, Payment $payment)
    {
        try {
            $payment = $this->paymentService->rejectPayment(
                $payment,
                (int) (auth()->id() ?? 0),
                (string) ($request->validated('rejection_reason') ?? $request->validated('notes') ?? '')
            );
        } catch (Throwable $e) {
            return back()->withErrors(['payment' => ui_phrase($e->getMessage())]);
        }

        return redirect()
            ->route('payments.show', $payment)
            ->with('success', ui_phrase('Payment rejected successfully.'));
    }

    public function cancel(PaymentLifecycleActionRequest $request, Payment $payment)
    {
        try {
            $payment = $this->paymentService->cancelPayment(
                $payment,
                (int) (auth()->id() ?? 0),
                (string) ($request->validated('notes') ?? '')
            );
        } catch (Throwable $e) {
            return back()->withErrors(['payment' => ui_phrase($e->getMessage())]);
        }

        return redirect()
            ->route('payments.show', $payment)
            ->with('success', ui_phrase('Payment cancelled successfully.'));
    }
}
