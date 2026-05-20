<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Http\Requests\Finance\InvoiceLifecycleActionRequest;
use App\Http\Requests\Finance\UpdateInvoiceRequest;
use App\Models\Invoice;
use App\Services\InvoiceService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class InvoiceController extends Controller
{
    public function __construct(
        private readonly InvoiceService $invoiceService
    ) {
    }

    public function index(Request $request)
    {
        $validated = $request->validate([
            'status' => ['nullable', Rule::in(Invoice::STATUS_OPTIONS)],
            'invoice_type' => ['nullable', Rule::in(Invoice::TYPE_OPTIONS)],
        ]);

        $query = Invoice::query()->with(['booking.quotation.inquiry.customer', 'generatedBy']);

        $query->when($request->input('q'), function ($q) use ($request) {
            $term = $request->input('q');
            $q->where('invoice_number', 'like', "%{$term}%")
                ->orWhereHas('booking', function ($booking) use ($term) {
                    $booking->where('booking_number', 'like', "%{$term}%")
                        ->orWhereHas('quotation', function ($quotation) use ($term) {
                            $quotation->where('quotation_number', 'like', "%{$term}%")
                                ->orWhereHas('inquiry.customer', function ($customer) use ($term) {
                                    $customer->where('name', 'like', "%{$term}%");
                                });
                        });
                });
        });

        $query->when($validated['status'] ?? null, fn ($q) => $q->where('status', $validated['status']));
        $query->when($validated['invoice_type'] ?? null, fn ($q) => $q->where('invoice_type', $validated['invoice_type']));
        $query->when($request->input('invoice_from'), fn ($q) => $q->whereDate('invoice_date', '>=', $request->input('invoice_from')));
        $query->when($request->input('invoice_to'), fn ($q) => $q->whereDate('invoice_date', '<=', $request->input('invoice_to')));

        $perPage = (int) $request->input('per_page', 10);
        $perPage = $perPage > 0 && $perPage <= 100 ? $perPage : 10;

        $invoices = $query->latest()->paginate($perPage)->withQueryString();

        return view('modules.invoices.index', compact('invoices'));
    }

    public function show(Invoice $invoice)
    {
        $invoice->load(['booking.quotation.inquiry.customer', 'generatedBy', 'payments.creator', 'payments.confirmer', 'payments.rejector']);
        return view('modules.invoices.show', compact('invoice'));
    }

    public function edit(Invoice $invoice)
    {
        $invoice->load(['booking.quotation.inquiry.customer', 'generatedBy']);
        if (! $invoice->isEditable()) {
            return redirect()
                ->route('invoices.show', $invoice)
                ->with('error', ui_phrase('Issued/paid/void/cancelled invoice cannot be edited directly.'));
        }

        return view('modules.invoices.edit', compact('invoice'));
    }

    public function update(UpdateInvoiceRequest $request, Invoice $invoice)
    {
        if (! $invoice->isEditable()) {
            return redirect()
                ->route('invoices.show', $invoice)
                ->with('error', ui_phrase('Issued/paid/void/cancelled invoice cannot be edited directly.'));
        }

        $validated = $request->validated();
        $amounts = $this->invoiceService->computeAmounts(
            (float) ($validated['subtotal'] ?? 0),
            (float) ($validated['discount_amount'] ?? 0),
            (float) ($validated['tax_amount'] ?? 0),
            (float) ($invoice->paid_amount ?? 0),
        );

        $invoice->fill(array_merge($validated, $amounts));
        $invoice->recalculateBalance();
        $invoice->save();

        return redirect()
            ->route('invoices.show', $invoice)
            ->with('success', ui_phrase('Invoice updated successfully.'));
    }

    public function issue(InvoiceLifecycleActionRequest $request, Invoice $invoice)
    {
        if (! $invoice->isEditable()) {
            return redirect()
                ->route('invoices.show', $invoice)
                ->with('error', ui_phrase('Issued/paid/void/cancelled invoice cannot be edited directly.'));
        }
        $invoice = $this->invoiceService->issue($invoice);

        return redirect()
            ->route('invoices.show', $invoice)
            ->with('success', ui_phrase('Invoice issued successfully.'));
    }

    public function void(InvoiceLifecycleActionRequest $request, Invoice $invoice)
    {
        if ($invoice->isPaid()) {
            return redirect()
                ->route('invoices.show', $invoice)
                ->with('error', ui_phrase('Paid/overpaid invoice cannot be voided.'));
        }
        $invoice = $this->invoiceService->markVoid($invoice, (string) ($request->validated('notes') ?? ''));

        return redirect()
            ->route('invoices.show', $invoice)
            ->with('success', ui_phrase('Invoice voided successfully.'));
    }

    public function cancel(InvoiceLifecycleActionRequest $request, Invoice $invoice)
    {
        if ($invoice->isPaid()) {
            return redirect()
                ->route('invoices.show', $invoice)
                ->with('error', ui_phrase('Paid/overpaid invoice cannot be cancelled.'));
        }
        $invoice = $this->invoiceService->cancel($invoice, (string) ($request->validated('notes') ?? ''));

        return redirect()
            ->route('invoices.show', $invoice)
            ->with('success', ui_phrase('Invoice cancelled successfully.'));
    }
}
