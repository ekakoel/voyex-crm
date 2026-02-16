<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Inquiry;
use App\Models\Promotion;
use App\Models\Quotation;
use App\Models\QuotationTemplate;
use App\Models\Service;
use Barryvdh\DomPDF\PDF;
use Illuminate\Contracts\Validation\Rule;
use Illuminate\Support\Facades\DB;

class QuotationController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $query = Quotation::query()->with(['inquiry.customer']);

        $query->when(request('q'), function ($q) {
            $term = request('q');
            $q->where('quotation_number', 'like', "%{$term}%")
                ->orWhereHas('inquiry', function ($inq) use ($term) {
                    $inq->where('inquiry_number', 'like', "%{$term}%")
                        ->orWhereHas('customer', function ($c) use ($term) {
                            $c->where('name', 'like', "%{$term}%");
                        });
                });
        });

        $query->when(request('status'), fn ($q) => $q->where('status', request('status')));
        $query->when(request('inquiry_id'), fn ($q) => $q->where('inquiry_id', request('inquiry_id')));
        $query->when(request('valid_from'), fn ($q) => $q->whereDate('validity_date', '>=', request('valid_from')));
        $query->when(request('valid_to'), fn ($q) => $q->whereDate('validity_date', '<=', request('valid_to')));
        $query->when(request('min_amount'), fn ($q) => $q->where('final_amount', '>=', request('min_amount')));
        $query->when(request('max_amount'), fn ($q) => $q->where('final_amount', '<=', request('max_amount')));

        $perPage = (int) request('per_page', 10);
        $perPage = $perPage > 0 && $perPage <= 100 ? $perPage : 10;

        $quotations = $query->latest()->paginate($perPage)->withQueryString();
        $inquiries = Inquiry::query()->with('customer')->orderBy('created_at', 'desc')->get();

        return view('admin.quotations.index', compact('quotations', 'inquiries'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $inquiries = Inquiry::query()
            ->with('customer')
            ->whereDoesntHave('quotation')
            ->orderBy('created_at', 'desc')
            ->get();

        $services = Service::query()->where('is_active', true)->orderBy('name')->get();
        $templates = QuotationTemplate::query()->where('is_active', true)->orderBy('name')->get();

        return view('admin.quotations.create', compact('inquiries', 'services', 'templates'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $items = collect($request->input('items', []))
            ->filter(fn ($row) => trim((string) ($row['description'] ?? '')) !== '')
            ->values()
            ->all();
        $request->merge(['items' => $items]);

        $validated = $request->validate([
            'inquiry_id' => ['required', 'exists:inquiries,id', 'unique:quotations,inquiry_id'],
            'status' => ['required', Rule::in(['draft', 'sent', 'approved', 'rejected'])],
            'validity_date' => ['required', 'date'],
            'template_id' => ['nullable', 'exists:quotation_templates,id'],
            'discount_type' => ['nullable', Rule::in(['percent', 'fixed'])],
            'discount_value' => ['nullable', 'numeric', 'min:0'],
            'promo_code' => ['nullable', 'string'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.service_id' => ['nullable', 'exists:services,id'],
            'items.*.description' => ['required', 'string', 'max:255'],
            'items.*.qty' => ['required', 'integer', 'min:1'],
            'items.*.unit_price' => ['required', 'numeric', 'min:0'],
            'items.*.discount' => ['nullable', 'numeric', 'min:0'],
        ]);

        $this->assertPricingPermission($validated);

        $validated['quotation_number'] = $this->generateQuotationNumber();

        DB::beginTransaction();
        try {
            $totals = $this->computeTotals($validated['items'], $validated['discount_type'] ?? null, (float) ($validated['discount_value'] ?? 0), $validated['promo_code'] ?? null);

            $quotation = Quotation::query()->create([
                'quotation_number' => $validated['quotation_number'],
                'inquiry_id' => $validated['inquiry_id'],
                'status' => $validated['status'],
                'validity_date' => $validated['validity_date'],
                'template_id' => $validated['template_id'] ?? null,
                'sub_total' => $totals['sub_total'],
                'discount_type' => $validated['discount_type'] ?? null,
                'discount_value' => (float) ($validated['discount_value'] ?? 0),
                'promo_code' => $validated['promo_code'] ?? null,
                'promo_discount' => $totals['promo_discount'],
                'final_amount' => $totals['final_amount'],
                'approval_status' => $totals['needs_approval'] ? 'submitted' : 'approved',
            ]);

            foreach ($totals['items'] as $item) {
                $quotation->items()->create($item);
            }

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            return back()->with('error', 'Failed to save quotation. Please check the data.');
        }

        return redirect()
            ->route('admin.quotations.index')
            ->with('success', 'Quotation created successfully.');
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        return redirect()->route('admin.quotations.edit', $id);
    }


    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        $quotation = Quotation::query()->findOrFail($id);
        $inquiries = Inquiry::query()
            ->with('customer')
            ->where(function ($q) use ($quotation) {
                $q->whereDoesntHave('quotation')
                    ->orWhere('id', $quotation->inquiry_id);
            })
            ->orderBy('created_at', 'desc')
            ->get();

        $services = Service::query()->where('is_active', true)->orderBy('name')->get();
        $templates = QuotationTemplate::query()->where('is_active', true)->orderBy('name')->get();
        $quotation->load('items');

        return view('admin.quotations.edit', compact('quotation', 'inquiries', 'services', 'templates'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $quotation = Quotation::query()->findOrFail($id);

        $items = collect($request->input('items', []))
            ->filter(fn ($row) => trim((string) ($row['description'] ?? '')) !== '')
            ->values()
            ->all();
        $request->merge(['items' => $items]);

        $validated = $request->validate([
            'inquiry_id' => [
                'required',
                'exists:inquiries,id',
                Rule::unique('quotations', 'inquiry_id')->ignore($quotation->id),
            ],
            'status' => ['required', Rule::in(['draft', 'sent', 'approved', 'rejected'])],
            'validity_date' => ['required', 'date'],
            'template_id' => ['nullable', 'exists:quotation_templates,id'],
            'discount_type' => ['nullable', Rule::in(['percent', 'fixed'])],
            'discount_value' => ['nullable', 'numeric', 'min:0'],
            'promo_code' => ['nullable', 'string'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.service_id' => ['nullable', 'exists:services,id'],
            'items.*.description' => ['required', 'string', 'max:255'],
            'items.*.qty' => ['required', 'integer', 'min:1'],
            'items.*.unit_price' => ['required', 'numeric', 'min:0'],
            'items.*.discount' => ['nullable', 'numeric', 'min:0'],
        ]);

        $this->assertPricingPermission($validated);

        DB::beginTransaction();
        try {
            $totals = $this->computeTotals($validated['items'], $validated['discount_type'] ?? null, (float) ($validated['discount_value'] ?? 0), $validated['promo_code'] ?? null);

            $quotation->update([
                'inquiry_id' => $validated['inquiry_id'],
                'status' => $validated['status'],
                'validity_date' => $validated['validity_date'],
                'template_id' => $validated['template_id'] ?? null,
                'sub_total' => $totals['sub_total'],
                'discount_type' => $validated['discount_type'] ?? null,
                'discount_value' => (float) ($validated['discount_value'] ?? 0),
                'promo_code' => $validated['promo_code'] ?? null,
                'promo_discount' => $totals['promo_discount'],
                'final_amount' => $totals['final_amount'],
                'approval_status' => $totals['needs_approval'] ? 'submitted' : 'approved',
            ]);

            $quotation->items()->delete();
            foreach ($totals['items'] as $item) {
                $quotation->items()->create($item);
            }

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            return back()->with('error', 'Failed to save quotation. Please check the data.');
        }

        return redirect()
            ->route('admin.quotations.index')
            ->with('success', 'Quotation updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $quotation = Quotation::query()->findOrFail($id);
        $quotation->delete();

        return redirect()
            ->route('admin.quotations.index')
            ->with('success', 'Quotation deleted successfully.');
    }

    public function generatePDF(Quotation $quotation)
    {
        $quotation->load(['inquiry.customer', 'items.service', 'template']);
        $pdf = PDF::loadView('pdf.quotation', compact('quotation'));
        return $pdf->stream('quotation.pdf');
    }

    public function exportCsv()
    {
        $query = Quotation::query()->with(['inquiry.customer']);

        $query->when(request('q'), function ($q) {
            $term = request('q');
            $q->where('quotation_number', 'like', "%{$term}%")
                ->orWhereHas('inquiry', function ($inq) use ($term) {
                    $inq->where('inquiry_number', 'like', "%{$term}%")
                        ->orWhereHas('customer', function ($c) use ($term) {
                            $c->where('name', 'like', "%{$term}%");
                        });
                });
        });

        $query->when(request('status'), fn ($q) => $q->where('status', request('status')));
        $query->when(request('inquiry_id'), fn ($q) => $q->where('inquiry_id', request('inquiry_id')));
        $query->when(request('valid_from'), fn ($q) => $q->whereDate('validity_date', '>=', request('valid_from')));
        $query->when(request('valid_to'), fn ($q) => $q->whereDate('validity_date', '<=', request('valid_to')));
        $query->when(request('min_amount'), fn ($q) => $q->where('final_amount', '>=', request('min_amount')));
        $query->when(request('max_amount'), fn ($q) => $q->where('final_amount', '<=', request('max_amount')));

        $quotations = $query->latest()->get();

        return response()->streamDownload(function () use ($quotations) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, [
                'quotation_number',
                'inquiry_number',
                'customer_name',
                'status',
                'validity_date',
                'final_amount',
                'approval_status',
            ]);
            foreach ($quotations as $quotation) {
                fputcsv($handle, [
                    $quotation->quotation_number,
                    $quotation->inquiry->inquiry_number ?? '',
                    $quotation->inquiry->customer->name ?? '',
                    $quotation->status,
                    optional($quotation->validity_date)->format('Y-m-d'),
                    $quotation->final_amount,
                    $quotation->approval_status,
                ]);
            }
            fclose($handle);
        }, 'quotations-export.csv', [
            'Content-Type' => 'text/csv',
        ]);
    }

    private function generateQuotationNumber(): string
    {
        do {
            $number = 'QT-' . now()->format('Ymd') . '-' . random_int(1000, 9999);
        } while (Quotation::query()->where('quotation_number', $number)->exists());

        return $number;
    }

    public function approve(Quotation $quotation)
    {
        $quotation->update([
            'approval_status' => 'approved',
            'approved_by' => auth()->id(),
            'approved_at' => now(),
        ]);

        return redirect()
            ->route('admin.quotations.edit', $quotation)
            ->with('success', 'Quotation approved.');
    }

    public function reject(Quotation $quotation)
    {
        $quotation->update([
            'approval_status' => 'rejected',
            'approved_by' => auth()->id(),
            'approved_at' => now(),
        ]);

        return redirect()
            ->route('admin.quotations.edit', $quotation)
            ->with('success', 'Quotation rejected.');
    }

    private function computeTotals(array $items, ?string $discountType, float $discountValue, ?string $promoCode): array
    {
        $subTotal = 0;
        $normalizedItems = [];

        foreach ($items as $item) {
            $qty = (int) $item['qty'];
            $unitPrice = (float) $item['unit_price'];
            $discount = (float) ($item['discount'] ?? 0);
            $total = max(0, ($qty * $unitPrice) - $discount);
            $subTotal += $total;

            $normalizedItems[] = [
                'service_id' => $item['service_id'] ?? null,
                'description' => $item['description'],
                'qty' => $qty,
                'unit_price' => $unitPrice,
                'discount' => $discount,
                'total' => $total,
            ];
        }

        $discountAmount = 0;
        if ($discountType === 'percent') {
            $discountAmount = $subTotal * ($discountValue / 100);
        } elseif ($discountType === 'fixed') {
            $discountAmount = $discountValue;
        }

        $promoDiscount = 0;
        if ($promoCode) {
            $promo = Promotion::query()
                ->where('code', $promoCode)
                ->where('is_active', true)
                ->where(function ($q) {
                    $q->whereNull('start_date')->orWhere('start_date', '<=', now()->toDateString());
                })
                ->where(function ($q) {
                    $q->whereNull('end_date')->orWhere('end_date', '>=', now()->toDateString());
                })
                ->first();

            if ($promo) {
                $promoDiscount = $promo->type === 'percent'
                    ? $subTotal * ($promo->value / 100)
                    : (float) $promo->value;
            }
        }

        $finalAmount = max(0, $subTotal - $discountAmount - $promoDiscount);

        return [
            'items' => $normalizedItems,
            'sub_total' => $subTotal,
            'promo_discount' => $promoDiscount,
            'final_amount' => $finalAmount,
            'needs_approval' => ($discountAmount > 0 || $promoDiscount > 0),
        ];
    }

    private function assertPricingPermission(array $validated): void
    {
        $hasDiscount = (float) ($validated['discount_value'] ?? 0) > 0;
        $hasPromo = ! empty($validated['promo_code']);

        if (($hasDiscount || $hasPromo) && ! auth()->user()->hasAnyRole(['Sales Manager', 'Director'])) {
            abort(403, 'Only Sales Managers or Directors can apply discounts/promotions.');
        }
    }
}
