<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Http\Requests\Finance\InvoiceLifecycleActionRequest;
use App\Http\Requests\Finance\UpdateInvoiceRequest;
use App\Models\Invoice;
use App\Services\InvoiceService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
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

        $query = Invoice::query()->with(['booking.quotation.inquiry.customer', 'booking.quotation.inquiry.handledBy:id,name', 'generatedBy']);

        $query->when($request->input('q'), function ($q) use ($request) {
            $term = trim((string) $request->input('q'));
            if (mb_strlen($term) < 3) {
                return;
            }

            $q->where(function ($nested) use ($term) {
                $nested->where('invoice_number', 'like', "%{$term}%")
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
        });

        $query->when($validated['status'] ?? null, fn ($q) => $q->where('status', $validated['status']));
        $query->when($validated['invoice_type'] ?? null, fn ($q) => $q->where('invoice_type', $validated['invoice_type']));
        $query->when($request->input('invoice_from'), fn ($q) => $q->whereDate('invoice_date', '>=', $request->input('invoice_from')));
        $query->when($request->input('invoice_to'), fn ($q) => $q->whereDate('invoice_date', '<=', $request->input('invoice_to')));

        $perPage = (int) $request->input('per_page', 10);
        $perPage = $perPage > 0 && $perPage <= 100 ? $perPage : 10;

        $summaryQuery = clone $query;
        $summaries = [
            'total' => (clone $summaryQuery)->count(),
            'proforma' => (clone $summaryQuery)->where('invoice_type', 'proforma')->count(),
            'final' => (clone $summaryQuery)->where('invoice_type', 'final')->count(),
            'unpaid_balance' => (float) ((clone $summaryQuery)->whereNotIn('status', ['paid', 'overpaid', 'void', 'cancelled'])->sum('balance_amount') ?? 0),
            'overdue' => (clone $summaryQuery)
                ->whereNotIn('status', ['paid', 'overpaid', 'void', 'cancelled'])
                ->whereDate('due_date', '<', now()->toDateString())
                ->count(),
        ];

        $invoices = $query->latest()->paginate($perPage)->withQueryString();

        return view('modules.invoices.index', compact('invoices', 'summaries'));
    }

    public function show(Invoice $invoice)
    {
        $invoice->load(['booking.quotation.inquiry.customer', 'generatedBy', 'payments.creator', 'payments.confirmer', 'payments.rejector']);
        return view('modules.invoices.show', compact('invoice'));
    }

    public function edit(Invoice $invoice)
    {
        $invoice->load(['booking.quotation.inquiry.customer', 'generatedBy']);
        if (! $this->canManageInvoiceByInquiryHandler($invoice)) {
            return $this->denyInvoiceMutation($invoice);
        }
        if (! $invoice->isEditable()) {
            return redirect()
                ->route('invoices.show', $invoice)
                ->with('error', ui_phrase('Issued/paid/void/cancelled invoice cannot be edited directly.'));
        }

        return view('modules.invoices.edit', compact('invoice'));
    }

    public function update(UpdateInvoiceRequest $request, Invoice $invoice)
    {
        if (! $this->canManageInvoiceByInquiryHandler($invoice)) {
            return $this->denyInvoiceMutation($invoice);
        }
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
        if (! $this->canManageInvoiceByInquiryHandler($invoice)) {
            return $this->denyInvoiceMutation($invoice);
        }
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
        if (! $this->canManageInvoiceByInquiryHandler($invoice)) {
            return $this->denyInvoiceMutation($invoice);
        }
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
        if (! $this->canManageInvoiceByInquiryHandler($invoice)) {
            return $this->denyInvoiceMutation($invoice);
        }
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

    private function canManageInvoiceByInquiryHandler(Invoice $invoice): bool
    {
        $user = auth()->user();
        if (! $user || ! $user->can('module.invoices.update')) {
            return false;
        }

        $inquiry = $invoice->booking?->quotation?->inquiry;
        if (! $inquiry) {
            return true;
        }

        $handlerId = 0;
        if (Schema::hasColumn('inquiries', 'handled_by')) {
            $handlerId = (int) ($inquiry->handled_by ?? 0);
        }
        if ($handlerId <= 0 && Schema::hasColumn('inquiries', 'assigned_to')) {
            $handlerId = (int) ($inquiry->assigned_to ?? 0);
        }
        if ($handlerId <= 0 && Schema::hasColumn('inquiries', 'created_by')) {
            $handlerId = (int) ($inquiry->created_by ?? 0);
        }

        return $handlerId <= 0 || $handlerId === (int) $user->id;
    }

    private function denyInvoiceMutation(Invoice $invoice)
    {
        return redirect()
            ->route('invoices.show', $invoice)
            ->with('error', ui_phrase('You do not have permission to modify this invoice.'));
    }
}
