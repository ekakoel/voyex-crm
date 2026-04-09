<?php

namespace App\Http\Controllers\Sales;

use PDF;
use App\Models\Activity;
use App\Models\FoodBeverage;
use App\Models\HotelRoom;
use App\Models\Itinerary;
use App\Models\Quotation;
use App\Models\QuotationApproval;
use App\Models\QuotationComment;
use App\Models\QuotationItem;
use App\Models\TouristAttraction;
use App\Models\TransportUnit;
use App\Services\ActivityAuditLogger;
use App\Services\ItineraryQuotationService;
use App\Services\InvoiceService;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class QuotationController extends Controller
{
    public function __construct(
        private readonly InvoiceService $invoiceService,
        private readonly ItineraryQuotationService $itineraryQuotationService,
        private readonly ActivityAuditLogger $activityAuditLogger
    )
    {
    }

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $query = Quotation::query()->withTrashed()->with(['inquiry.customer', 'creator', 'approvals:id,quotation_id,user_id,approval_role']);

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
        if (request()->boolean('needs_my_approval')) {
            $this->applyNeedsMyApprovalFilter($query, auth()->user());
        }
        $perPage = (int) request('per_page', 10);
        $perPage = $perPage > 0 && $perPage <= 100 ? $perPage : 10;

        $quotations = $query->latest()->paginate($perPage)->withQueryString();
        $authUser = auth()->user();
        $approvalRole = $this->resolveApprovalRoleForUser($authUser);
        $quotations->getCollection()->transform(function (Quotation $quotation) use ($authUser, $approvalRole) {
            $quotation->setAttribute('needs_my_approval_badge', $this->needsMyApprovalForQuotation($quotation, $authUser, $approvalRole));
            return $quotation;
        });
        $statsCards = $this->buildQuotationStatsCards();

        return view('modules.quotations.index', compact('quotations', 'statsCards'));
    }

    public function approvalNotifications(Request $request)
    {
        $user = $request->user();
        if (! $user) {
            return response()->json([
                'enabled' => false,
                'count' => 0,
                'role' => null,
                'latest' => null,
            ], 401);
        }

        $approvalRole = $this->resolveApprovalRoleForUser($user);
        if (! $approvalRole) {
            return response()->json([
                'enabled' => false,
                'count' => 0,
                'role' => null,
                'latest' => null,
            ]);
        }

        $query = Quotation::query();
        $this->applyNeedsMyApprovalFilter($query, $user);

        $count = (clone $query)->count();
        $latest = (clone $query)
            ->latest('created_at')
            ->latest('id')
            ->first(['id', 'quotation_number', 'created_at']);

        return response()->json([
            'enabled' => true,
            'count' => (int) $count,
            'role' => $approvalRole,
            'latest' => $latest ? [
                'id' => (int) $latest->id,
                'quotation_number' => (string) ($latest->quotation_number ?? ''),
                'created_at' => optional($latest->created_at)->toIso8601String(),
            ] : null,
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $prefillItineraryId = request()->integer('itinerary_id') ?: null;
        $itineraries = $this->availableItinerariesQuery()
            ->whereNotIn('id', Quotation::withTrashed()
                ->select('itinerary_id')
                ->whereNotNull('itinerary_id'))
            ->orderByDesc('id')
            ->get(['id', 'title', 'inquiry_id', 'destination', 'duration_days', 'duration_nights', 'is_active', 'status']);

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
            'validity_date' => ['required', 'date'],
            'discount_type' => ['nullable', Rule::in(['percent', 'fixed'])],
            'discount_value' => ['nullable', 'numeric', 'min:0'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.description' => ['required', 'string', 'max:255'],
            'items.*.qty' => ['required', 'integer', 'min:1'],
            'items.*.contract_rate' => ['nullable', 'numeric', 'min:0'],
            'items.*.rate' => ['nullable', 'numeric', 'min:0'],
            'items.*.markup_type' => ['nullable', Rule::in(['percent', 'fixed'])],
            'items.*.markup' => ['nullable', 'numeric', 'min:0'],
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
            $markupType = ($item['markup_type'] ?? 'fixed') === 'percent' ? 'percent' : 'fixed';
            $markupValue = (float) ($item['markup'] ?? 0);
            if ($markupType === 'percent' && $markupValue > 100) {
                throw ValidationException::withMessages([
                    "items.{$index}.markup" => 'Markup percent cannot be greater than 100.',
                ]);
            }
            $type = ($item['discount_type'] ?? 'fixed') === 'percent' ? 'percent' : 'fixed';
            $value = (float) ($item['discount'] ?? 0);
            if ($type === 'percent' && $value > 100) {
                throw ValidationException::withMessages([
                    "items.{$index}.discount" => 'Discount percent cannot be greater than 100.',
                ]);
            }
        }

        $inquiryId = $this->resolveInquiryIdFromItinerary((int) $validated['itinerary_id']);
        if (! $this->canApplyGlobalDiscount()) {
            $validated['discount_type'] = null;
            $validated['discount_value'] = 0;
        } else {
            $this->assertPricingPermission($validated);
        }

        $validated['quotation_number'] = $this->generateQuotationNumber();

        DB::beginTransaction();
        try {
            $totals = $this->computeTotals($validated['items'], $validated['discount_type'] ?? null, (float) ($validated['discount_value'] ?? 0));

            $quotation = Quotation::withoutActivityLogging(function () use ($validated, $inquiryId, $totals) {
                return Quotation::query()->create([
                    'quotation_number' => $validated['quotation_number'],
                    'inquiry_id' => $inquiryId,
                    'itinerary_id' => $validated['itinerary_id'],
                    'status' => 'pending',
                    'validity_date' => $validated['validity_date'],
                    'sub_total' => $totals['sub_total'],
                    'discount_type' => $validated['discount_type'] ?? null,
                    'discount_value' => (float) ($validated['discount_value'] ?? 0),
                    'final_amount' => $totals['final_amount'],
                ]);
            });

            foreach ($totals['items'] as $item) {
                $quotation->items()->create($item);
            }
            $quotation->loadMissing('itinerary:id,status');
            if ($quotation->itinerary) {
                $quotation->itinerary->syncLifecycleStatus();
            }

            $quotation->load('items');
            $this->activityAuditLogger->logCreated($quotation, $this->buildQuotationAuditSnapshot($quotation), 'Quotation');

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            return back()->with('error', 'Failed to save quotation. Please check the data.');
        }

        return redirect()
            ->route('quotations.show', $quotation)
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
            'activities.user',
            'comments.user',
            'booking',
            'approvedBy',
            'approvalNoteBy',
            'creator',
            'updater',
            'approvals.user',
        ]);
        $approvalProgress = $this->buildApprovalProgress($quotation);

        $activities = $quotation->activities()
            ->with('user:id,name')
            ->latest()
            ->paginate(5, ['*'], 'activity_page');

        return view('modules.quotations.show', compact('quotation', 'approvalProgress', 'activities'));
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
        $quotation->load(['items', 'itinerary.inquiry.customer', 'itinerary.inquiry.assignedUser', 'itinerary.creator', 'comments.user', 'approvedBy', 'approvalNoteBy', 'approvals.user']);

        $itineraries = $this->availableItinerariesQuery()
            ->where(function ($query) use ($quotation) {
                $query->whereDoesntHave('quotation', function (Builder $quotationQuery): void {
                    $quotationQuery->whereNull('deleted_at');
                })
                    ->orWhere('id', $quotation->itinerary_id);
            })
            ->orderByDesc('id')
            ->get(['id', 'title', 'inquiry_id', 'destination', 'duration_days', 'duration_nights', 'is_active', 'status']);

        $approvalProgress = $this->buildApprovalProgress($quotation);
        $activities = $quotation->activities()
            ->with('user:id,name')
            ->latest()
            ->paginate(5, ['*'], 'activity_page');

        return view('modules.quotations.edit', compact('quotation', 'itineraries', 'approvalProgress', 'activities'));
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
            'validity_date' => ['required', 'date'],
            'discount_type' => ['nullable', Rule::in(['percent', 'fixed'])],
            'discount_value' => ['nullable', 'numeric', 'min:0'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.description' => ['required', 'string', 'max:255'],
            'items.*.qty' => ['required', 'integer', 'min:1'],
            'items.*.contract_rate' => ['nullable', 'numeric', 'min:0'],
            'items.*.rate' => ['nullable', 'numeric', 'min:0'],
            'items.*.markup_type' => ['nullable', Rule::in(['percent', 'fixed'])],
            'items.*.markup' => ['nullable', 'numeric', 'min:0'],
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
            $markupType = ($item['markup_type'] ?? 'fixed') === 'percent' ? 'percent' : 'fixed';
            $markupValue = (float) ($item['markup'] ?? 0);
            if ($markupType === 'percent' && $markupValue > 100) {
                throw ValidationException::withMessages([
                    "items.{$index}.markup" => 'Markup percent cannot be greater than 100.',
                ]);
            }
            $type = ($item['discount_type'] ?? 'fixed') === 'percent' ? 'percent' : 'fixed';
            $value = (float) ($item['discount'] ?? 0);
            if ($type === 'percent' && $value > 100) {
                throw ValidationException::withMessages([
                    "items.{$index}.discount" => 'Discount percent cannot be greater than 100.',
                ]);
            }
        }

        $inquiryId = $this->resolveInquiryIdFromItinerary((int) $validated['itinerary_id']);
        if (! $this->canApplyGlobalDiscount()) {
            $validated['discount_type'] = $quotation->discount_type;
            $validated['discount_value'] = (float) ($quotation->discount_value ?? 0);
        } else {
            $discountTypeProvided = $request->has('discount_type');
            $discountValueProvided = $request->has('discount_value');
            if ($discountTypeProvided || $discountValueProvided) {
                $this->assertPricingPermission($validated);
            } else {
                $validated['discount_type'] = $quotation->discount_type;
                $validated['discount_value'] = (float) ($quotation->discount_value ?? 0);
            }
        }

        DB::beginTransaction();
        try {
            $totals = $this->computeTotals($validated['items'], $validated['discount_type'] ?? null, (float) ($validated['discount_value'] ?? 0));
            $previousItineraryId = (int) ($quotation->itinerary_id ?? 0);

            $quotation->loadMissing('items');
            $beforeAudit = $this->buildQuotationAuditSnapshot($quotation);

            Quotation::withoutActivityLogging(function () use ($quotation, $inquiryId, $validated, $totals): void {
                $quotation->update([
                    'inquiry_id' => $inquiryId,
                    'itinerary_id' => $validated['itinerary_id'],
                    'validity_date' => $validated['validity_date'],
                    'sub_total' => $totals['sub_total'],
                    'discount_type' => $validated['discount_type'] ?? null,
                    'discount_value' => (float) ($validated['discount_value'] ?? 0),
                    'final_amount' => $totals['final_amount'],
                ]);
            });

            $quotation->items()->delete();
            foreach ($totals['items'] as $item) {
                $quotation->items()->create($item);
            }
            $currentItineraryId = (int) ($quotation->itinerary_id ?? 0);
            foreach (array_values(array_unique(array_filter([$previousItineraryId, $currentItineraryId]))) as $itineraryId) {
                $linkedItinerary = Itinerary::query()->find($itineraryId);
                if ($linkedItinerary) {
                    $linkedItinerary->syncLifecycleStatus();
                }
            }
            $quotation->load('items');
            $this->activityAuditLogger->logUpdated($quotation, $beforeAudit, $this->buildQuotationAuditSnapshot($quotation), 'Quotation');

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            return back()->with('error', 'Failed to save quotation. Please check the data.');
        }

        return redirect()
            ->route('quotations.show', $quotation)
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

        $validated = $request->validate([
            'comment_body' => ['required', 'string', 'max:2000'],
            'parent_id' => ['nullable', 'integer', 'exists:quotation_comments,id'],
        ]);
        $cleanBody = trim(strip_tags((string) $validated['comment_body']));
        if ($cleanBody === '') {
            return redirect()->back()->with('error', 'Comment cannot be empty.');
        }

        $parentId = isset($validated['parent_id']) ? (int) $validated['parent_id'] : null;
        if ($parentId) {
            $parentComment = QuotationComment::query()
                ->whereKey($parentId)
                ->where('quotation_id', $quotation->id)
                ->first();

            if (! $parentComment) {
                return redirect()->back()->with('error', 'Parent comment is invalid.');
            }
            if ($parentComment->parent_id !== null) {
                return redirect()->back()->with('error', 'Reply can only target a top-level comment.');
            }
            if ((int) ($quotation->created_by ?? 0) !== (int) $user->id) {
                return redirect()->back()->with('error', 'Only quotation creator can reply to comments.');
            }
        }

        QuotationComment::query()->create([
            'quotation_id' => $quotation->id,
            'user_id' => $user->id,
            'parent_id' => $parentId,
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
        $quotation->loadMissing('itinerary:id,status');
        if ($quotation->itinerary) {
            $quotation->itinerary->syncLifecycleStatus();
        }

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
            $quotation->loadMissing('itinerary:id,status');
            if ($quotation->itinerary) {
                $quotation->itinerary->syncLifecycleStatus();
            }

            return redirect()
                ->route('quotations.index')
                ->with('success', 'Quotation activated successfully.');
        }

        $quotation->delete();
        $quotation->loadMissing('itinerary:id,status');
        if ($quotation->itinerary) {
            $quotation->itinerary->syncLifecycleStatus();
        }

        return redirect()
            ->route('quotations.index')
            ->with('success', 'Quotation deactivated successfully.');
    }

    public function generatePDF(Quotation $quotation)
    {
        if (($quotation->status ?? '') !== 'approved') {
            return redirect()
                ->route('quotations.show', $quotation)
                ->with('error', 'PDF hanya tersedia untuk quotation dengan status approved.');
        }

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
        $user = $request->user();
        if (! $user) {
            return redirect()->back()->with('error', 'Please login first.');
        }
        if ($quotation->isCreator($user)) {
            return redirect()
                ->route('quotations.show', $quotation)
                ->with('error', 'Creator quotation tidak dapat melakukan approval.');
        }

        $approvalRole = $this->resolveApprovalRoleForUser($user);
        if (! $approvalRole) {
            return redirect()->back()->with('error', 'Only Manager, Director, or Reservation can approve this quotation.');
        }
        $alreadyApprovedByUser = $quotation->approvals()
            ->where('user_id', $user->id)
            ->exists();
        if ($alreadyApprovedByUser) {
            return redirect()
                ->route('quotations.show', $quotation)
                ->with('error', 'Anda sudah melakukan approval untuk quotation ini.');
        }

        $progressBefore = $this->buildApprovalProgress($quotation);
        if ($approvalRole === 'manager' && ! $progressBefore['reservation_other_approved']) {
            return redirect()
                ->route('quotations.show', $quotation)
                ->with('error', 'Manager dapat approve setelah minimal 1 Reservation (bukan creator) melakukan approval.');
        }
        if ($approvalRole === 'director') {
            if (! $progressBefore['reservation_other_approved']) {
                return redirect()
                    ->route('quotations.show', $quotation)
                    ->with('error', 'Director dapat approve setelah minimal 1 Reservation (bukan creator) melakukan approval.');
            }
            if (! $progressBefore['manager_approved']) {
                return redirect()
                    ->route('quotations.show', $quotation)
                    ->with('error', 'Director dapat approve setelah Manager melakukan approval.');
            }
        }

        $validated = $request->validate([
            'approval_note' => ['nullable', 'string', 'max:2000'],
        ]);
        $note = trim((string) ($validated['approval_note'] ?? ''));

        QuotationApproval::query()->updateOrCreate(
            [
                'quotation_id' => $quotation->id,
                'user_id' => $user->id,
            ],
            [
                'approval_role' => $approvalRole,
                'note' => $note === '' ? null : $note,
                'approved_at' => now(),
            ]
        );

        if ($note !== '' && $user->hasRole('Director')) {
            $quotation->update([
                'approval_note' => $note,
                'approval_note_by' => $user->id,
                'approval_note_at' => now(),
            ]);
        }

        $progress = $this->syncQuotationApprovalStatus($quotation);

        if ($progress['is_ready']) {
            if ($quotation->booking) {
                $this->invoiceService->generateForBooking($quotation->booking);
            }

            return redirect()
                ->route('quotations.show', $quotation)
                ->with('success', 'Quotation approved. All required approvals are complete.');
        }

        $missing = implode(', ', $progress['missing_labels']);

        return redirect()
            ->route('quotations.show', $quotation)
            ->with('success', "Approval recorded. Waiting for: {$missing}.");
    }

    public function reject(Request $request, Quotation $quotation)
    {
        if ($quotation->isFinal()) {
            return redirect()->back()->with('error', 'Final quotation cannot be modified.');
        }
        $user = $request->user();
        if (! $user || ! $user->hasAnyRole(['Director', 'Manager'])) {
            return redirect()->back()->with('error', 'Only Director or Manager can reject quotation.');
        }

        $validated = $request->validate([
            'approval_note' => ['required', 'string', 'max:2000'],
        ]);
        $note = trim((string) ($validated['approval_note'] ?? ''));
        if ($note === '') {
            return redirect()
                ->route('quotations.show', $quotation)
                ->withErrors(['approval_note' => 'Reject note is required.']);
        }

        $quotation->approvals()->delete();
        $quotation->update([
            'status' => 'rejected',
            'approved_by' => null,
            'approved_at' => null,
            'approval_note' => $note,
            'approval_note_by' => $user->id,
            'approval_note_at' => now(),
        ]);
        $quotation->loadMissing('itinerary:id,status');
        if ($quotation->itinerary) {
            $quotation->itinerary->syncLifecycleStatus();
        }

        return redirect()
            ->route('quotations.show', $quotation)
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

        $quotation->approvals()->delete();
        $quotation->update($payload);
        $quotation->loadMissing('itinerary:id,status');
        if ($quotation->itinerary) {
            $quotation->itinerary->syncLifecycleStatus();
        }

        return redirect()
            ->route('quotations.show', $quotation)
            ->with('success', 'Quotation status changed to pending.');
    }

    public function updateGlobalDiscount(Request $request, Quotation $quotation)
    {
        if ($quotation->isFinal()) {
            return redirect()->back()->with('error', 'Final quotation cannot be modified.');
        }
        if (($quotation->status ?? '') === 'approved') {
            return redirect()->back()->with('error', 'Approved quotation cannot be modified. Set to pending first.');
        }
        if (! $this->canApplyGlobalDiscount()) {
            return redirect()->back()->with('error', 'Only Manager or Director can set global discount.');
        }

        $validated = $request->validate([
            'global_discount_type' => ['nullable', Rule::in(['percent', 'fixed'])],
            'global_discount_value' => ['nullable', 'numeric', 'min:0'],
        ]);

        $discountType = $validated['global_discount_type'] ?? null;
        $discountValue = (float) ($validated['global_discount_value'] ?? 0);

        if ($discountType === 'percent' && $discountValue > 100) {
            return redirect()->back()->withErrors([
                'global_discount_value' => 'Global discount percent cannot be greater than 100.',
            ]);
        }

        $subTotal = (float) ($quotation->sub_total ?? 0);
        if ($subTotal <= 0) {
            $subTotal = (float) $quotation->items()->sum('total');
        }

        $discountAmount = 0.0;
        if ($discountType === 'percent') {
            $discountAmount = $subTotal * ($discountValue / 100);
        } elseif ($discountType === 'fixed') {
            $discountAmount = $discountValue;
        }

        $quotation->update([
            'discount_type' => $discountType,
            'discount_value' => $discountValue,
            'sub_total' => $subTotal,
            'final_amount' => max(0, $subTotal - $discountAmount),
        ]);

        return redirect()
            ->route('quotations.edit', $quotation)
            ->with('success', 'Global discount updated.');
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
            'manual',
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
            $contractRate = (float) ($item['contract_rate'] ?? 0);
            $markupType = ($item['markup_type'] ?? 'fixed') === 'percent' ? 'percent' : 'fixed';
            $markup = (float) ($item['markup'] ?? 0);
            $providedUnitPrice = max(0, (float) ($item['unit_price'] ?? 0));
            $providedRate = max(0, (float) ($item['rate'] ?? 0));
            $itemType = (string) ($item['itinerary_item_type'] ?? '');
            $isManualItem = $itemType === 'manual';
            $hasContractRateInput = array_key_exists('contract_rate', $item)
                && $item['contract_rate'] !== null
                && $item['contract_rate'] !== '';

            $unitPriceFromMarkup = $markupType === 'percent'
                ? ($contractRate + ($contractRate * ($markup / 100)))
                : ($contractRate + $markup);
            if ($isManualItem) {
                // Manual items persist rate-per-unit in unit_price.
                $unitPrice = $providedRate;
                if ($unitPrice <= 0 && $providedUnitPrice > 0) {
                    // Backward compatible fallback when legacy payload posts total rate in unit_price.
                    $unitPrice = $qty > 0 ? ($providedUnitPrice / $qty) : $providedUnitPrice;
                }
            } else {
                $unitPrice = $hasContractRateInput
                    ? max(0, $unitPriceFromMarkup)
                    : $providedUnitPrice;
            }
            if ($unitPrice <= 0 && $providedUnitPrice > 0) {
                $unitPrice = $providedUnitPrice;
            }
            $discount = (float) ($item['discount'] ?? 0);
            $itemDiscountType = ($item['discount_type'] ?? 'fixed') === 'percent' ? 'percent' : 'fixed';
            $discountAmount = $itemDiscountType === 'percent'
                ? (($qty * $unitPrice) * ($discount / 100))
                : $discount;
            $total = max(0, ($qty * $unitPrice) - $discountAmount);
            $subTotal += $total;

            $normalized = [
                'description' => $item['description'],
                'qty' => $qty,
                'contract_rate' => $contractRate,
                'markup_type' => $markupType,
                'markup' => $markup,
                'unit_price' => $unitPrice,
                'discount_type' => $itemDiscountType,
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

    private function canApplyGlobalDiscount(): bool
    {
        $user = auth()->user();
        if (! $user) {
            return false;
        }

        return $user->hasAnyRole(['Manager', 'Director']);
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

    private function availableItinerariesQuery(): Builder
    {
        $query = Itinerary::query()->with(['inquiry.customer']);

        if (Schema::hasColumn('itineraries', 'is_active')) {
            $query->where(function (Builder $builder): void {
                $builder->where('is_active', true)->orWhereNull('is_active');
            });
        }

        if (Schema::hasColumn('itineraries', 'status')) {
            $query->where('status', '!=', Itinerary::FINAL_STATUS);
        }

        return $query;
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
            ->with('error', 'Quotation ini hanya dapat diubah oleh Creator, Manager, Director, atau Super Admin.');
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

    private function resolveApprovalRoleForUser($user): ?string
    {
        if (! $user) {
            return null;
        }

        if ($user->hasRole('Director')) {
            return 'director';
        }
        if ($user->hasRole('Manager')) {
            return 'manager';
        }
        if ($user->hasRole('Reservation')) {
            return 'reservation';
        }

        return null;
    }

    private function applyNeedsMyApprovalFilter(Builder $query, $user): void
    {
        if (! $user) {
            $query->whereRaw('1 = 0');
            return;
        }

        $approvalRole = $this->resolveApprovalRoleForUser($user);
        if (! $approvalRole) {
            $query->whereRaw('1 = 0');
            return;
        }

        $query->where('status', 'pending')
            ->whereNull('deleted_at');

        if (Schema::hasColumn('quotations', 'created_by')) {
            $query->where(function (Builder $inner) use ($user): void {
                $inner->whereNull('created_by')
                    ->orWhere('created_by', '!=', (int) $user->id);
            });
        }

        $hasReservationOtherApproval = function (Builder $inner): void {
            $inner->where('approval_role', 'reservation')
                ->where(function (Builder $scope): void {
                    $scope->whereNull('quotations.created_by')
                        ->orWhereColumn('quotation_approvals.user_id', '<>', 'quotations.created_by');
                });
        };

        $query->whereDoesntHave('approvals', function (Builder $inner) use ($user): void {
            $inner->where('user_id', (int) $user->id);
        });

        if ($approvalRole === 'reservation') {
            $query->whereDoesntHave('approvals', $hasReservationOtherApproval);
            return;
        }

        if ($approvalRole === 'manager') {
            $query->whereHas('approvals', $hasReservationOtherApproval)
                ->whereDoesntHave('approvals', function (Builder $inner): void {
                    $inner->where('approval_role', 'manager');
                });
            return;
        }

        if ($approvalRole === 'director') {
            $query->whereHas('approvals', $hasReservationOtherApproval)
                ->whereHas('approvals', function (Builder $inner): void {
                    $inner->where('approval_role', 'manager');
                })
                ->whereDoesntHave('approvals', function (Builder $inner): void {
                    $inner->where('approval_role', 'director');
                });
            return;
        }

        $query->whereRaw('1 = 0');
    }

    private function needsMyApprovalForQuotation(Quotation $quotation, $user, ?string $approvalRole): bool
    {
        if (! $user || ! $approvalRole) {
            return false;
        }
        if ((string) ($quotation->status ?? '') !== 'pending') {
            return false;
        }
        if (method_exists($quotation, 'trashed') && $quotation->trashed()) {
            return false;
        }
        if (Schema::hasColumn('quotations', 'created_by') && (int) ($quotation->created_by ?? 0) === (int) $user->id) {
            return false;
        }

        $approvals = $quotation->relationLoaded('approvals')
            ? $quotation->approvals
            : $quotation->approvals()->get();

        $alreadyApprovedByUser = $approvals->contains(fn ($approval) => (int) ($approval->user_id ?? 0) === (int) $user->id);
        if ($alreadyApprovedByUser) {
            return false;
        }

        $reservationOtherApproved = $approvals
            ->filter(fn ($approval) => (string) ($approval->approval_role ?? '') === 'reservation')
            ->contains(fn ($approval) => (int) ($approval->user_id ?? 0) !== (int) ($quotation->created_by ?? 0));
        $managerApproved = $approvals->contains(fn ($approval) => (string) ($approval->approval_role ?? '') === 'manager');
        $directorApproved = $approvals->contains(fn ($approval) => (string) ($approval->approval_role ?? '') === 'director');

        return match ($approvalRole) {
            'reservation' => ! $reservationOtherApproved,
            'manager' => $reservationOtherApproved && ! $managerApproved,
            'director' => $reservationOtherApproved && $managerApproved && ! $directorApproved,
            default => false,
        };
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function buildQuotationStatsCards(): array
    {
        $counts = Quotation::query()
            ->withTrashed()
            ->select('status', DB::raw('COUNT(*) as total'))
            ->groupBy('status')
            ->pluck('total', 'status');

        $cards = [[
            'key' => 'total',
            'label' => 'Total',
            'value' => (int) $counts->sum(),
            'caption' => 'Total',
            'tone' => 'bg-slate-50 text-slate-700 border-slate-100',
        ]];

        $preferredOrder = ['pending', 'approved', 'rejected', 'final', 'draft', 'processed'];
        $statusOrder = collect($preferredOrder)
            ->filter(fn (string $status) => (int) ($counts[$status] ?? 0) > 0)
            ->merge(
                $counts->keys()
                    ->map(fn ($status) => (string) $status)
                    ->filter(fn (string $status) => ! in_array($status, $preferredOrder, true))
            )
            ->values();

        foreach ($statusOrder as $status) {
            $cards[] = [
                'key' => $status,
                'label' => \Illuminate\Support\Str::headline($status),
                'value' => (int) ($counts[$status] ?? 0),
                'caption' => 'Total',
                'tone' => $this->toneForQuotationStatus($status),
            ];
        }

        return $cards;
    }

    private function toneForQuotationStatus(string $status): string
    {
        return match (strtolower($status)) {
            'pending' => 'bg-sky-50 text-sky-700 border-sky-100',
            'approved' => 'bg-emerald-50 text-emerald-700 border-emerald-100',
            'rejected' => 'bg-rose-50 text-rose-700 border-rose-100',
            'final' => 'bg-violet-50 text-violet-700 border-violet-100',
            'draft' => 'bg-slate-50 text-slate-700 border-slate-100',
            'processed' => 'bg-amber-50 text-amber-700 border-amber-100',
            default => 'bg-slate-50 text-slate-700 border-slate-100',
        };
    }

    /**
     * @return array{
     *   manager_approved: bool,
     *   director_approved: bool,
     *   reservation_other_approved: bool,
     *   reservation_count: int,
     *   is_ready: bool,
     *   missing_labels: array<int, string>,
     *   director_approver_id: int|null
     * }
     */
    private function buildApprovalProgress(Quotation $quotation): array
    {
        $approvals = $quotation->relationLoaded('approvals')
            ? $quotation->approvals
            : $quotation->approvals()->get();

        $managerApproved = $approvals->contains(fn ($a) => (string) $a->approval_role === 'manager');
        $directorApprovals = $approvals
            ->filter(fn ($a) => (string) $a->approval_role === 'director')
            ->sortByDesc(fn ($a) => optional($a->approved_at)->getTimestamp() ?? 0)
            ->values();
        $directorApproved = $directorApprovals->isNotEmpty();
        $directorApproverId = $directorApproved ? (int) ($directorApprovals->first()->user_id ?? 0) : null;

        $reservationApprovals = $approvals
            ->filter(fn ($a) => (string) $a->approval_role === 'reservation')
            ->filter(fn ($a) => (int) ($a->user_id ?? 0) !== (int) ($quotation->created_by ?? 0))
            ->values();
        $reservationCount = $reservationApprovals->count();
        $reservationOtherApproved = $reservationCount >= 1;

        $missing = [];
        if (! $managerApproved) {
            $missing[] = 'Manager approval';
        }
        if (! $directorApproved) {
            $missing[] = 'Director approval';
        }
        if (! $reservationOtherApproved) {
            $missing[] = '1 Reservation (non-creator) approval';
        }

        return [
            'manager_approved' => $managerApproved,
            'director_approved' => $directorApproved,
            'reservation_other_approved' => $reservationOtherApproved,
            'reservation_count' => $reservationCount,
            'is_ready' => empty($missing),
            'missing_labels' => $missing,
            'director_approver_id' => $directorApproverId ?: null,
        ];
    }

    /**
     * @return array{
     *   manager_approved: bool,
     *   director_approved: bool,
     *   reservation_other_approved: bool,
     *   reservation_count: int,
     *   is_ready: bool,
     *   missing_labels: array<int, string>,
     *   director_approver_id: int|null
     * }
     */
    private function syncQuotationApprovalStatus(Quotation $quotation): array
    {
        $quotation->loadMissing('approvals');
        $quotation->unsetRelation('approvals');
        $quotation->load('approvals');
        $progress = $this->buildApprovalProgress($quotation);

        if ($progress['is_ready']) {
            $quotation->update([
                'status' => 'approved',
                'approved_by' => $progress['director_approver_id'],
                'approved_at' => now(),
            ]);
        } else {
            $quotation->update([
                'status' => 'pending',
                'approved_by' => null,
                'approved_at' => null,
            ]);
        }
        $quotation->loadMissing('itinerary:id,status');
        if ($quotation->itinerary) {
            $quotation->itinerary->syncLifecycleStatus();
        }

        return $progress;
    }

    private function buildQuotationAuditSnapshot(Quotation $quotation): array
    {
        $items = $quotation->items
            ->map(function (QuotationItem $item): array {
                $meta = is_array($item->serviceable_meta ?? null) ? $item->serviceable_meta : [];
                ksort($meta);

                return [
                    'description' => (string) ($item->description ?? ''),
                    'qty' => (int) ($item->qty ?? 0),
                    'contract_rate' => (float) ($item->contract_rate ?? 0),
                    'markup_type' => (string) ($item->markup_type ?? 'fixed'),
                    'markup' => (float) ($item->markup ?? 0),
                    'unit_price' => (float) ($item->unit_price ?? 0),
                    'discount_type' => (string) ($item->discount_type ?? 'fixed'),
                    'discount' => (float) ($item->discount ?? 0),
                    'day_number' => (int) ($item->day_number ?? 0),
                    'itinerary_item_type' => (string) ($item->itinerary_item_type ?? ''),
                    'serviceable_type' => (string) ($item->serviceable_type ?? ''),
                    'serviceable_id' => (int) ($item->serviceable_id ?? 0),
                    'serviceable_meta' => $meta,
                    'total' => (float) ($item->total ?? 0),
                ];
            })
            ->values()
            ->toArray();

        return [
            'quotation_number' => (string) ($quotation->quotation_number ?? ''),
            'inquiry_id' => (int) ($quotation->inquiry_id ?? 0),
            'itinerary_id' => (int) ($quotation->itinerary_id ?? 0),
            'status' => (string) ($quotation->status ?? ''),
            'validity_date' => optional($quotation->validity_date)->format('Y-m-d'),
            'sub_total' => (float) ($quotation->sub_total ?? 0),
            'discount_type' => (string) ($quotation->discount_type ?? ''),
            'discount_value' => (float) ($quotation->discount_value ?? 0),
            'final_amount' => (float) ($quotation->final_amount ?? 0),
            'items' => $items,
        ];
    }
}



