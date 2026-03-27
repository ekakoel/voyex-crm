<?php

namespace App\Http\Controllers\Sales;

use PDF;
use App\Models\Activity;
use App\Models\FoodBeverage;
use App\Models\HotelRoom;
use App\Models\Itinerary;
use App\Models\Quotation;
use App\Models\QuotationComment;
use App\Models\QuotationItem;
use App\Models\TouristAttraction;
use App\Models\TransportUnit;
use App\Services\ItineraryQuotationService;
use App\Services\InvoiceService;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\DB;

class QuotationController extends Controller
{
    public function __construct(
        private readonly InvoiceService $invoiceService,
        private readonly ItineraryQuotationService $itineraryQuotationService
    )
    {
    }

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $query = Quotation::query()->withTrashed()->with(['inquiry.customer', 'creator']);

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
        $perPage = (int) request('per_page', 10);
        $perPage = $perPage > 0 && $perPage <= 100 ? $perPage : 10;

        $quotations = $query->latest()->paginate($perPage)->withQueryString();
        return view('modules.quotations.index', compact('quotations'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $prefillItineraryId = request()->integer('itinerary_id') ?: null;
        $itineraries = Itinerary::query()
            ->with(['inquiry.customer'])
            ->whereDoesntHave('quotation')
            ->where('is_active', true)
            ->orderByDesc('id')
            ->get(['id', 'title', 'inquiry_id', 'destination', 'duration_days', 'duration_nights', 'is_active']);

        if ($prefillItineraryId && ! $itineraries->firstWhere('id', $prefillItineraryId)) {
            $prefillItineraryId = null;
        }

        return view('modules.quotations.create', compact('itineraries', 'prefillItineraryId'));
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
            'itinerary_id' => [
                'required',
                'integer',
                'exists:itineraries,id',
                Rule::unique('quotations', 'itinerary_id'),
            ],
            'status' => ['required', Rule::in(Quotation::STATUS_OPTIONS)],
            'validity_date' => ['required', 'date'],
            'discount_type' => ['nullable', Rule::in(['percent', 'fixed'])],
            'discount_value' => ['nullable', 'numeric', 'min:0'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.description' => ['required', 'string', 'max:255'],
            'items.*.qty' => ['required', 'integer', 'min:1'],
            'items.*.unit_price' => ['required', 'numeric', 'min:0'],
            'items.*.discount_type' => ['nullable', Rule::in(['percent', 'fixed'])],
            'items.*.discount' => ['nullable', 'numeric', 'min:0'],
            'items.*.serviceable_type' => ['nullable', Rule::in($this->serviceableTypes())],
            'items.*.serviceable_id' => ['nullable', 'integer', 'min:1'],
            'items.*.day_number' => ['nullable', 'integer', 'min:1'],
            'items.*.serviceable_meta' => ['nullable'],
            'items.*.itinerary_item_type' => ['nullable', Rule::in($this->itineraryItemTypes())],
        ]);

        foreach ($validated['items'] as $index => $item) {
            $type = ($item['discount_type'] ?? 'fixed') === 'percent' ? 'percent' : 'fixed';
            $value = (float) ($item['discount'] ?? 0);
            if ($type === 'percent' && $value > 100) {
                throw ValidationException::withMessages([
                    "items.{$index}.discount" => 'Discount percent cannot be greater than 100.',
                ]);
            }
        }

        $inquiryId = $this->resolveInquiryIdFromItinerary((int) $validated['itinerary_id']);
        $this->assertPricingPermission($validated);

        $validated['quotation_number'] = $this->generateQuotationNumber();

        DB::beginTransaction();
        try {
            $totals = $this->computeTotals($validated['items'], $validated['discount_type'] ?? null, (float) ($validated['discount_value'] ?? 0));

            $quotation = Quotation::query()->create([
                'quotation_number' => $validated['quotation_number'],
                'inquiry_id' => $inquiryId,
                'itinerary_id' => $validated['itinerary_id'],
                'status' => $validated['status'],
                'validity_date' => $validated['validity_date'],
                'sub_total' => $totals['sub_total'],
                'discount_type' => $validated['discount_type'] ?? null,
                'discount_value' => (float) ($validated['discount_value'] ?? 0),
                'final_amount' => $totals['final_amount'],
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
            ->route('quotations.index')
            ->with('success', 'Quotation created successfully.');
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $quotation = Quotation::query()->findOrFail($id);
        $quotation->load([
            'inquiry.customer',
            'itinerary.creator',
            'itinerary.inquiry.customer',
            'items',
            'comments.user',
            'booking',
            'approvedBy',
            'approvalNoteBy',
            'creator',
            'updater',
        ]);

        return view('modules.quotations.show', compact('quotation'));
    }


    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        $quotation = Quotation::query()->findOrFail($id);
        if ($quotation->isFinal()) {
            return redirect()
                ->route('quotations.show', $quotation)
                ->with('error', 'Final quotation cannot be edited.');
        }
        if (($quotation->status ?? '') === 'approved') {
            return redirect()
                ->route('quotations.show', $quotation)
                ->with('error', 'Approved quotation cannot be edited.');
        }
        if (! $this->canManageQuotation($quotation, 'update')) {
            return $this->denyQuotationMutation($quotation);
        }
        $quotation->load(['items', 'itinerary.inquiry.customer', 'itinerary.inquiry.assignedUser', 'itinerary.creator', 'comments.user', 'approvedBy', 'approvalNoteBy']);

        $itineraries = Itinerary::query()
            ->with(['inquiry.customer'])
            ->where(function ($query) use ($quotation) {
                $query->whereDoesntHave('quotation')
                    ->orWhere('id', $quotation->itinerary_id);
            })
            ->orderByDesc('id')
            ->get(['id', 'title', 'inquiry_id', 'destination', 'duration_days', 'duration_nights', 'is_active']);

        return view('modules.quotations.edit', compact('quotation', 'itineraries'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $quotation = Quotation::query()->findOrFail($id);
        if ($quotation->isFinal()) {
            return redirect()
                ->route('quotations.show', $quotation)
                ->with('error', 'Final quotation cannot be updated.');
        }
        if (($quotation->status ?? '') === 'approved') {
            return redirect()
                ->route('quotations.show', $quotation)
                ->with('error', 'Approved quotation cannot be updated.');
        }
        if (! $this->canManageQuotation($quotation, 'update')) {
            return $this->denyQuotationMutation($quotation);
        }

        $items = collect($request->input('items', []))
            ->filter(fn ($row) => trim((string) ($row['description'] ?? '')) !== '')
            ->values()
            ->all();
        $request->merge(['items' => $items]);

        $validated = $request->validate([
            'itinerary_id' => [
                'required',
                'integer',
                'exists:itineraries,id',
                Rule::unique('quotations', 'itinerary_id')->ignore($quotation->id),
            ],
            'status' => ['required', Rule::in(Quotation::STATUS_OPTIONS)],
            'validity_date' => ['required', 'date'],
            'discount_type' => ['nullable', Rule::in(['percent', 'fixed'])],
            'discount_value' => ['nullable', 'numeric', 'min:0'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.description' => ['required', 'string', 'max:255'],
            'items.*.qty' => ['required', 'integer', 'min:1'],
            'items.*.unit_price' => ['required', 'numeric', 'min:0'],
            'items.*.discount_type' => ['nullable', Rule::in(['percent', 'fixed'])],
            'items.*.discount' => ['nullable', 'numeric', 'min:0'],
            'items.*.serviceable_type' => ['nullable', Rule::in($this->serviceableTypes())],
            'items.*.serviceable_id' => ['nullable', 'integer', 'min:1'],
            'items.*.day_number' => ['nullable', 'integer', 'min:1'],
            'items.*.serviceable_meta' => ['nullable'],
            'items.*.itinerary_item_type' => ['nullable', Rule::in($this->itineraryItemTypes())],
        ]);

        foreach ($validated['items'] as $index => $item) {
            $type = ($item['discount_type'] ?? 'fixed') === 'percent' ? 'percent' : 'fixed';
            $value = (float) ($item['discount'] ?? 0);
            if ($type === 'percent' && $value > 100) {
                throw ValidationException::withMessages([
                    "items.{$index}.discount" => 'Discount percent cannot be greater than 100.',
                ]);
            }
        }

        $inquiryId = $this->resolveInquiryIdFromItinerary((int) $validated['itinerary_id']);
        $this->assertPricingPermission($validated);

        DB::beginTransaction();
        try {
            $totals = $this->computeTotals($validated['items'], $validated['discount_type'] ?? null, (float) ($validated['discount_value'] ?? 0));

            $quotation->update([
                'inquiry_id' => $inquiryId,
                'itinerary_id' => $validated['itinerary_id'],
                'status' => $validated['status'],
                'validity_date' => $validated['validity_date'],
                'sub_total' => $totals['sub_total'],
                'discount_type' => $validated['discount_type'] ?? null,
                'discount_value' => (float) ($validated['discount_value'] ?? 0),
                'final_amount' => $totals['final_amount'],
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
            ->route('quotations.index')
            ->with('success', 'Quotation updated successfully.');
    }

    public function storeComment(Request $request, Quotation $quotation)
    {
        if ($quotation->isFinal()) {
            return redirect()->back()->with('error', 'Final quotation cannot be modified.');
        }
        $user = $request->user();
        if (! $user) {
            return redirect()->back()->with('error', 'Please login first.');
        }

        if (! $user->hasAnyRole(['Director', 'Manager', 'Marketing'])) {
            return redirect()->back()->with('error', 'You are not allowed to comment on this quotation.');
        }

        if ((int) $quotation->created_by === (int) $user->id) {
            return redirect()->back()->with('error', 'Creator cannot comment on their own quotation.');
        }

        $validated = $request->validate([
            'comment_body' => ['required', 'string', 'max:2000'],
        ]);
        $cleanBody = trim(strip_tags((string) $validated['comment_body']));
        if ($cleanBody === '') {
            return redirect()->back()->with('error', 'Comment cannot be empty.');
        }

        QuotationComment::query()->create([
            'quotation_id' => $quotation->id,
            'user_id' => $user->id,
            'body' => $cleanBody,
        ]);

        return redirect()->back()->with('success', 'Comment added.');
    }

    public function updateComment(Request $request, Quotation $quotation, QuotationComment $comment)
    {
        if ($quotation->isFinal()) {
            return redirect()->back()->with('error', 'Final quotation cannot be modified.');
        }
        $user = $request->user();
        if (! $user) {
            return redirect()->back()->with('error', 'Please login first.');
        }

        if ((int) $comment->quotation_id !== (int) $quotation->id) {
            return redirect()->back()->with('error', 'Invalid comment.');
        }

        if (! $user->hasAnyRole(['Director', 'Manager', 'Marketing'])) {
            return redirect()->back()->with('error', 'You are not allowed to edit comments.');
        }

        if ((int) $comment->user_id !== (int) $user->id) {
            return redirect()->back()->with('error', 'You can only edit your own comment.');
        }

        $validated = $request->validate([
            'comment_body' => ['required', 'string', 'max:2000'],
        ]);

        $cleanBody = trim(strip_tags((string) $validated['comment_body']));
        if ($cleanBody === '') {
            return redirect()->back()->with('error', 'Comment cannot be empty.');
        }

        $comment->update([
            'body' => $cleanBody,
        ]);

        return redirect()->back()->with('success', 'Comment updated.');
    }

    public function destroyComment(Request $request, Quotation $quotation, QuotationComment $comment)
    {
        if ($quotation->isFinal()) {
            return redirect()->back()->with('error', 'Final quotation cannot be modified.');
        }
        $user = $request->user();
        if (! $user) {
            return redirect()->back()->with('error', 'Please login first.');
        }

        if ((int) $comment->quotation_id !== (int) $quotation->id) {
            return redirect()->back()->with('error', 'Invalid comment.');
        }

        if (! $user->hasAnyRole(['Director', 'Manager', 'Marketing'])) {
            return redirect()->back()->with('error', 'You are not allowed to delete comments.');
        }

        if ((int) $comment->user_id !== (int) $user->id) {
            return redirect()->back()->with('error', 'You can only delete your own comment.');
        }

        $comment->delete();

        return redirect()->back()->with('success', 'Comment deleted.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $quotation = Quotation::query()->findOrFail($id);
        if ($quotation->isFinal()) {
            return redirect()
                ->route('quotations.show', $quotation)
                ->with('error', 'Final quotation cannot be deleted.');
        }
        if (($quotation->status ?? '') === 'approved') {
            return redirect()
                ->route('quotations.show', $quotation)
                ->with('error', 'Approved quotation cannot be deleted.');
        }
        if (! $this->canManageQuotation($quotation, 'delete')) {
            return $this->denyQuotationMutation($quotation);
        }
        $quotation->delete();

        return redirect()
            ->route('quotations.index')
            ->with('success', 'Quotation deactivated successfully.');
    }

    public function toggleStatus($quotation)
    {
        $quotation = Quotation::withTrashed()->findOrFail($quotation);
        if ($quotation->isFinal()) {
            return redirect()
                ->route('quotations.show', $quotation)
                ->with('error', 'Final quotation cannot be changed.');
        }
        if (($quotation->status ?? '') === 'approved') {
            return redirect()
                ->route('quotations.show', $quotation)
                ->with('error', 'Approved quotation cannot be deactivated.');
        }
        if (! $this->canManageQuotation($quotation, 'delete')) {
            return $this->denyQuotationMutation($quotation);
        }

        if ($quotation->trashed()) {
            $quotation->restore();

            return redirect()
                ->route('quotations.index')
                ->with('success', 'Quotation activated successfully.');
        }

        $quotation->delete();

        return redirect()
            ->route('quotations.index')
            ->with('success', 'Quotation deactivated successfully.');
    }

    public function generatePDF(Quotation $quotation)
    {
        $quotation->load(['inquiry.customer', 'items', 'itinerary']);
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
            ]);
            foreach ($quotations as $quotation) {
                fputcsv($handle, [
                    $quotation->quotation_number,
                    $quotation->inquiry?->inquiry_number ?? '',
                    $quotation->inquiry?->customer?->name ?? '',
                    $quotation->status,
                    optional($quotation->validity_date)->format('Y-m-d'),
                    $quotation->final_amount,
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

    public function approve(Request $request, Quotation $quotation)
    {
        if ($quotation->isFinal()) {
            return redirect()->back()->with('error', 'Final quotation cannot be modified.');
        }
        $payload = [
            'approved_by' => auth()->id(),
            'approved_at' => now(),
        ];

        $payload['status'] = 'approved';

        if ($request->has('approval_note') && auth()->user()->hasAnyRole(['Director'])) {
            $validated = $request->validate([
                'approval_note' => ['nullable', 'string', 'max:2000'],
            ]);
            $note = trim((string) ($validated['approval_note'] ?? ''));
            $payload['approval_note'] = $note === '' ? null : $note;
            $payload['approval_note_by'] = $note === '' ? null : auth()->id();
            $payload['approval_note_at'] = $note === '' ? null : now();
        }

        $quotation->update($payload);

        if ($quotation->booking) {
            $this->invoiceService->generateForBooking($quotation->booking);
        }

        return redirect()
            ->route('quotations.edit', $quotation)
            ->with('success', 'Quotation approved.');
    }

    public function reject(Request $request, Quotation $quotation)
    {
        if ($quotation->isFinal()) {
            return redirect()->back()->with('error', 'Final quotation cannot be modified.');
        }
        $payload = [
            'approved_by' => auth()->id(),
            'approved_at' => now(),
        ];

        $payload['status'] = 'rejected';

        if ($request->has('approval_note') && auth()->user()->hasAnyRole(['Director'])) {
            $validated = $request->validate([
                'approval_note' => ['nullable', 'string', 'max:2000'],
            ]);
            $note = trim((string) ($validated['approval_note'] ?? ''));
            $payload['approval_note'] = $note === '' ? null : $note;
            $payload['approval_note_by'] = $note === '' ? null : auth()->id();
            $payload['approval_note_at'] = $note === '' ? null : now();
        }

        $quotation->update($payload);

        return redirect()
            ->route('quotations.edit', $quotation)
            ->with('success', 'Quotation rejected.');
    }

    public function setPending(Request $request, Quotation $quotation)
    {
        if ($quotation->isFinal()) {
            return redirect()->back()->with('error', 'Final quotation cannot be modified.');
        }
        $user = $request->user();
        if (! $user || ! $user->hasAnyRole(['Director'])) {
            return redirect()->back()->with('error', 'Only Director can set quotation to pending.');
        }

        if (($quotation->status ?? '') !== 'approved') {
            return redirect()->back()->with('error', 'Only approved quotation can be set to pending.');
        }

        $payload = [
            'approved_by' => null,
            'approved_at' => null,
            'status' => 'pending',
        ];
        if ($request->has('approval_note')) {
            $validated = $request->validate([
                'approval_note' => ['nullable', 'string', 'max:2000'],
            ]);
            $note = trim((string) ($validated['approval_note'] ?? ''));
            $payload['approval_note'] = $note === '' ? null : $note;
            $payload['approval_note_by'] = $note === '' ? null : auth()->id();
            $payload['approval_note_at'] = $note === '' ? null : now();
        }

        $quotation->update($payload);

        return redirect()
            ->route('quotations.edit', $quotation)
            ->with('success', 'Quotation status changed to pending.');
    }

    /**
     * @return array<int, string>
     */
    private function serviceableTypes(): array
    {
        return [
            TouristAttraction::class,
            Activity::class,
            FoodBeverage::class,
            TransportUnit::class,
            HotelRoom::class,
        ];
    }

    /**
     * @return array<int, string>
     */
    private function itineraryItemTypes(): array
    {
        return [
            'transport_day',
            'attraction',
            'activity',
            'fnb',
            'hotel_day_end',
        ];
    }

    /**
     * @param mixed $value
     * @return array<string, mixed>|null
     */
    private function normalizeServiceableMeta($value): ?array
    {
        if (is_array($value)) {
            return $value;
        }
        if (is_string($value) && $value !== '') {
            $decoded = json_decode($value, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                return $decoded;
            }
        }

        return null;
    }

    private function computeTotals(array $items, ?string $discountType, float $discountValue): array
    {
        $subTotal = 0;
        $normalizedItems = [];

        foreach ($items as $item) {
            $qty = (int) $item['qty'];
            $unitPrice = (float) $item['unit_price'];
            $discount = (float) ($item['discount'] ?? 0);
            $discountType = ($item['discount_type'] ?? 'fixed') === 'percent' ? 'percent' : 'fixed';
            $discountAmount = $discountType === 'percent'
                ? (($qty * $unitPrice) * ($discount / 100))
                : $discount;
            $total = max(0, ($qty * $unitPrice) - $discountAmount);
            $subTotal += $total;

            $normalized = [
                'description' => $item['description'],
                'qty' => $qty,
                'unit_price' => $unitPrice,
                'discount_type' => ($item['discount_type'] ?? 'fixed') === 'percent' ? 'percent' : 'fixed',
                'discount' => $discount,
                'total' => $total,
            ];

            $serviceableType = $item['serviceable_type'] ?? null;
            $serviceableId = (int) ($item['serviceable_id'] ?? 0);
            if ($serviceableType && $serviceableId > 0 && in_array($serviceableType, $this->serviceableTypes(), true)) {
                $normalized['serviceable_type'] = $serviceableType;
                $normalized['serviceable_id'] = $serviceableId;
            }
            $dayNumber = (int) ($item['day_number'] ?? 0);
            if ($dayNumber > 0) {
                $normalized['day_number'] = $dayNumber;
            }
            $serviceableMeta = $this->normalizeServiceableMeta($item['serviceable_meta'] ?? null);
            if (! empty($serviceableMeta)) {
                $normalized['serviceable_meta'] = $serviceableMeta;
            }
            $itineraryItemType = $item['itinerary_item_type'] ?? null;
            if ($itineraryItemType && in_array($itineraryItemType, $this->itineraryItemTypes(), true)) {
                $normalized['itinerary_item_type'] = $itineraryItemType;
            }

            $normalizedItems[] = $normalized;
        }

        $discountAmount = 0;
        if ($discountType === 'percent') {
            $discountAmount = $subTotal * ($discountValue / 100);
        } elseif ($discountType === 'fixed') {
            $discountAmount = $discountValue;
        }

        $finalAmount = max(0, $subTotal - $discountAmount);

        return [
            'items' => $normalizedItems,
            'sub_total' => $subTotal,
            'final_amount' => $finalAmount,
            'needs_approval' => ($discountAmount > 0),
        ];
    }

    private function assertPricingPermission(array $validated): void
    {
        $hasDiscount = (float) ($validated['discount_value'] ?? 0) > 0;
        if ($hasDiscount && ! auth()->user()->hasAnyRole(['Manager', 'Director'])) {
            abort(403, 'Only Managers or Directors can apply discounts.');
        }
    }

    private function resolveInquiryIdFromItinerary(int $itineraryId): ?int
    {
        $itinerary = Itinerary::query()->select(['id', 'inquiry_id'])->find($itineraryId);
        if (! $itinerary) {
            throw ValidationException::withMessages([
                'itinerary_id' => 'Selected itinerary not found.',
            ]);
        }

        return $itinerary->inquiry_id ? (int) $itinerary->inquiry_id : null;
    }

    private function canManageQuotation(Quotation $quotation, string $ability = 'update'): bool
    {
        $user = auth()->user();
        if (! $user) {
            return false;
        }
        if (! in_array($ability, ['update', 'delete'], true)) {
            $ability = 'update';
        }

        return $user->can($ability, $quotation);
    }

    private function denyQuotationMutation(Quotation $quotation)
    {
        return redirect()
            ->route('quotations.show', $quotation)
            ->with('error', 'Hanya creator yang dapat mengubah atau menghapus quotation ini.');
    }

    public function itineraryItems(Itinerary $itinerary)
    {
        $items = $this->itineraryQuotationService->buildItems($itinerary);
        $missingPriceCount = collect($items)->filter(function (array $item): bool {
            return (float) ($item['unit_price'] ?? 0) <= 0;
        })->count();

        return response()->json([
            'items' => $items,
            'meta' => [
                'missing_price_count' => $missingPriceCount,
            ],
        ]);
    }
}



