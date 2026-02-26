<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use Illuminate\Http\Request;

class InvoiceController extends Controller
{
    public function index(Request $request)
    {
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

        $query->when($request->input('status'), fn ($q) => $q->where('status', $request->input('status')));
        $query->when($request->input('invoice_from'), fn ($q) => $q->whereDate('invoice_date', '>=', $request->input('invoice_from')));
        $query->when($request->input('invoice_to'), fn ($q) => $q->whereDate('invoice_date', '<=', $request->input('invoice_to')));

        $perPage = (int) $request->input('per_page', 10);
        $perPage = $perPage > 0 && $perPage <= 100 ? $perPage : 10;

        $invoices = $query->latest()->paginate($perPage)->withQueryString();

        return view('modules.invoices.index', compact('invoices'));
    }

    public function show(Invoice $invoice)
    {
        $invoice->load(['booking.quotation.inquiry.customer', 'generatedBy']);
        return view('modules.invoices.show', compact('invoice'));
    }
}


