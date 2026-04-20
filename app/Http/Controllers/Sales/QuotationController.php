<?php

namespace App\Http\Controllers\Sales;

use PDF;
use App\Http\Controllers\Concerns\HandlesActivityTimelineAjax;
use App\Models\Activity;
use App\Models\FoodBeverage;
use App\Models\HotelRoom;
use App\Models\Inquiry;
use App\Models\IslandTransfer;
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
use App\Services\QuotationValidationService;
use App\Support\ImageThumbnailGenerator;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\File;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

class QuotationController extends Controller
{
    use HandlesActivityTimelineAjax;

    public function __construct(
        private readonly InvoiceService $invoiceService,
        private readonly ItineraryQuotationService $itineraryQuotationService,
        private readonly ActivityAuditLogger $activityAuditLogger,
        private readonly QuotationValidationService $quotationValidationService
    )
    {
    }

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $this->autoFinalizeExpiredApprovedQuotations();
        $showNeedsMyApproval = request()->boolean('needs_my_approval');

        $query = Quotation::query()->with(['inquiry.customer', 'creator', 'approvals:id,quotation_id,user_id,approval_role']);
        $this->applyQuotationKeywordFilter($query, (string) request('q'));
        if ($showNeedsMyApproval) {
            $this->applyNeedsMyApprovalFilter($query, auth()->user());
            $statusFilterOptions = ['pending'];
            $statsCards = $this->buildQuotationStatsCards(null, ['pending'], false);
        } else {
            $status = strtolower(trim((string) request('status')));
            if ($status !== '' && in_array($status, Quotation::STATUS_OPTIONS, true)) {
                $query->where('status', $status);
            }
            $statusFilterOptions = Quotation::STATUS_OPTIONS;
            $statsCards = $this->buildQuotationStatsCards(null, null, false);
        }
        $perPage = (int) request('per_page', 10);
        $perPage = $perPage > 0 && $perPage <= 100 ? $perPage : 10;

        $quotations = $query->latest()->paginate($perPage)->withQueryString();
        $authUser = auth()->user();
        $approvalRole = $this->resolveApprovalRoleForUser($authUser);
        $quotations->getCollection()->transform(function (Quotation $quotation) use ($authUser, $approvalRole, $showNeedsMyApproval) {
            $quotation->setAttribute('needs_my_approval_badge', $showNeedsMyApproval
                ? $this->needsMyApprovalForQuotation($quotation, $authUser, $approvalRole)
                : false);
            return $quotation;
        });

        return view('modules.quotations.index', [
            'quotations' => $quotations,
            'statsCards' => $statsCards,
            'isMyQuotationPage' => false,
            'listRouteName' => 'quotations.index',
            'exportScope' => 'all',
            'statusFilterOptions' => $statusFilterOptions,
            'showNeedsMyApproval' => $showNeedsMyApproval,
        ]);
    }

    public function myQuotations()
    {
        $this->autoFinalizeExpiredApprovedQuotations();

        $query = Quotation::query()->withTrashed()->with(['inquiry.customer', 'creator', 'approvals:id,quotation_id,user_id,approval_role']);
        if (Schema::hasColumn('quotations', 'created_by')) {
            $query->where('created_by', (int) auth()->id());
        } else {
            $query->whereRaw('1 = 0');
        }
        $this->applyQuotationKeywordFilter($query, (string) request('q'));
        $status = strtolower(trim((string) request('status')));
        if ($status !== '' && in_array($status, Quotation::STATUS_OPTIONS, true)) {
            $query->where('status', $status);
        }

        $perPage = (int) request('per_page', 10);
        $perPage = $perPage > 0 && $perPage <= 100 ? $perPage : 10;

        $quotations = $query->latest()->paginate($perPage)->withQueryString();
        $quotations->getCollection()->transform(function (Quotation $quotation) {
            $quotation->setAttribute('needs_my_approval_badge', false);
            return $quotation;
        });
        $statsCards = $this->buildQuotationStatsCards((int) auth()->id(), null, true);

        return view('modules.quotations.index', [
            'quotations' => $quotations,
            'statsCards' => $statsCards,
            'isMyQuotationPage' => true,
            'listRouteName' => 'quotations.my',
            'exportScope' => 'my',
            'statusFilterOptions' => Quotation::STATUS_OPTIONS,
            'showNeedsMyApproval' => false,
        ]);
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
        $itineraryInquiryMap = $itineraries->mapWithKeys(function ($itinerary): array {
            $inquiry = $itinerary->inquiry;

            return [
                (string) $itinerary->id => [
                    'inquiry_number' => (string) ($inquiry?->inquiry_number ?? '-'),
                    'customer_name' => (string) ($inquiry?->customer?->name ?? '-'),
                    'status' => (string) ($inquiry?->status ?? '-'),
                    'priority' => (string) ($inquiry?->priority ?? '-'),
                    'source' => (string) ($inquiry?->source ?? '-'),
                    'assigned_user_name' => (string) ($inquiry?->assignedUser?->name ?? '-'),
                    'deadline' => optional($inquiry?->deadline)->format('Y-m-d') ?? '-',
                    'notes' => trim((string) ($inquiry?->notes ?? '')) !== '' ? (string) ($inquiry?->notes ?? '') : '-',
                    'notes_html' => \App\Support\SafeRichText::sanitize((string) ($inquiry?->notes ?? '')),
                ],
            ];
        })->all();

        if ($prefillItineraryId && ! $itineraries->firstWhere('id', $prefillItineraryId)) {
            $prefillItineraryId = null;
        }

        return view('modules.quotations.create', compact('itineraries', 'prefillItineraryId', 'itineraryInquiryMap'));
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
            $this->quotationValidationService->syncValidationRequirementsAndMasterRates($quotation);
            $this->syncLinkedLifecycleStatusesForQuotation($quotation);

            $quotation->load('items');
            $this->activityAuditLogger->logCreated($quotation, $this->buildQuotationAuditSnapshot($quotation), 'Quotation');

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            return back()
                ->withInput()
                ->with('error', 'Failed to save quotation. Please check the data.');
        }

        return redirect()
            ->route('quotations.show', $quotation)
            ->with('success', 'Quotation created successfully.');
    }

    /**
     * Display the specified resource.
     */
    public function show(Request $request, string $id)
    {
        $quotation = Quotation::query()->findOrFail($id);
        $this->autoFinalizeApprovedQuotationIfExpired($quotation);
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
        $this->quotationValidationService->syncValidationRequirements($quotation);
        $approvalProgress = $this->buildApprovalProgress($quotation);
        $validationProgress = $this->quotationValidationService->getProgress($quotation);
        $kpiSummary = $this->computeQuotationKpiSummary($quotation);
        $canValidateQuotation = $this->quotationValidationService->isValidationActor($request->user())
            && ! in_array((string) ($quotation->status ?? ''), ['approved', Quotation::FINAL_STATUS], true)
            && (bool) ($validationProgress['requires_validation'] ?? false);

        $activities = $quotation->activities()
            ->with('user:id,name')
            ->latest()
            ->paginate(5, ['*'], 'activity_page')
            ->withQueryString();

        if ($this->wantsActivityTimelineFragment($request)) {
            return $this->activityTimelineFragmentResponse($activities);
        }

        return view('modules.quotations.show', compact('quotation', 'approvalProgress', 'validationProgress', 'canValidateQuotation', 'activities', 'kpiSummary'));
    }


    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Request $request, string $id)
    {
        $quotation = Quotation::query()->findOrFail($id);
        $this->autoFinalizeApprovedQuotationIfExpired($quotation);
        if ($quotation->isFinal()) {
            return redirect()
                ->route('quotations.show', $quotation)
                ->with('error', 'Final quotation cannot be edited.');
        }
        if (! $this->canManageQuotation($quotation, 'update')) {
            return $this->denyQuotationMutation($quotation);
        }
        $quotation->load(['items', 'itinerary.inquiry.customer', 'itinerary.inquiry.assignedUser', 'itinerary.creator', 'comments.user', 'approvedBy', 'approvalNoteBy', 'approvals.user']);
        $this->quotationValidationService->syncValidationRequirements($quotation);

        $itineraries = $this->availableItinerariesQuery((int) ($quotation->itinerary_id ?? 0))
            ->where(function ($query) use ($quotation) {
                $query->whereDoesntHave('quotation', function (Builder $quotationQuery): void {
                    $quotationQuery->whereNull('deleted_at');
                })
                    ->orWhere('id', $quotation->itinerary_id);
            })
            ->orderByDesc('id')
            ->get(['id', 'title', 'inquiry_id', 'destination', 'duration_days', 'duration_nights', 'is_active', 'status']);

        $approvalProgress = $this->buildApprovalProgress($quotation);
        $validationProgress = $this->quotationValidationService->getProgress($quotation);
        $canValidateQuotation = $this->quotationValidationService->isValidationActor($request->user())
            && ! in_array((string) ($quotation->status ?? ''), ['approved', Quotation::FINAL_STATUS], true)
            && (bool) ($validationProgress['requires_validation'] ?? false);
        $activities = $quotation->activities()
            ->with('user:id,name')
            ->latest()
            ->paginate(5, ['*'], 'activity_page')
            ->withQueryString();

        if ($this->wantsActivityTimelineFragment($request)) {
            return $this->activityTimelineFragmentResponse($activities);
        }

        return view('modules.quotations.edit', compact('quotation', 'itineraries', 'approvalProgress', 'validationProgress', 'canValidateQuotation', 'activities'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $quotation = Quotation::query()->findOrFail($id);
        $this->autoFinalizeApprovedQuotationIfExpired($quotation);
        if ($quotation->isFinal()) {
            return redirect()
                ->route('quotations.show', $quotation)
                ->with('error', 'Final quotation cannot be updated.');
        }
        if (! $this->canManageQuotation($quotation, 'update')) {
            return $this->denyQuotationMutation($quotation);
        }
        $wasApprovedBeforeUpdate = ((string) ($quotation->status ?? '') === 'approved');

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
            $this->quotationValidationService->syncValidationRequirementsAndMasterRates($quotation);
            if ($wasApprovedBeforeUpdate) {
                $quotation->approvals()->delete();
                Quotation::withoutActivityLogging(function () use ($quotation): void {
                    $quotation->update([
                        'status' => 'pending',
                        'approved_by' => null,
                        'approved_at' => null,
                        'approval_note' => null,
                        'approval_note_by' => null,
                    ]);
                });
            }
            $this->syncLinkedLifecycleStatusesForQuotation($quotation, $previousItineraryId);
            $quotation->load('items');
            $this->activityAuditLogger->logUpdated($quotation, $beforeAudit, $this->buildQuotationAuditSnapshot($quotation), 'Quotation');

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            return back()
                ->withInput()
                ->with('error', 'Failed to save quotation. Please check the data.');
        }

        return redirect()
            ->route('quotations.show', $quotation)
            ->with('success', $wasApprovedBeforeUpdate ? 'Quotation updated. Status changed to pending for re-approval.' : 'Quotation updated successfully.');
    }

    public function storeComment(Request $request, Quotation $quotation)
    {
        $this->autoFinalizeApprovedQuotationIfExpired($quotation);
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
        $this->autoFinalizeApprovedQuotationIfExpired($quotation);
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
        $this->autoFinalizeApprovedQuotationIfExpired($quotation);
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
        $this->autoFinalizeApprovedQuotationIfExpired($quotation);
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
        $this->syncLinkedLifecycleStatusesForQuotation($quotation);

        return redirect()
            ->back()
            ->with('success', 'Quotation deactivated successfully.');
    }

    public function toggleStatus($quotation)
    {
        $quotation = Quotation::withTrashed()->findOrFail($quotation);
        $this->autoFinalizeApprovedQuotationIfExpired($quotation);
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
            $this->syncLinkedLifecycleStatusesForQuotation($quotation);

            return redirect()
                ->back()
                ->with('success', 'Quotation activated successfully.');
        }

        $quotation->delete();
        $this->syncLinkedLifecycleStatusesForQuotation($quotation);

        return redirect()
            ->back()
            ->with('success', 'Quotation deactivated successfully.');
    }

    public function generatePDF(Quotation $quotation)
    {
        $this->autoFinalizeApprovedQuotationIfExpired($quotation);
        if (! in_array((string) ($quotation->status ?? ''), ['approved', Quotation::FINAL_STATUS], true)) {
            return redirect()
                ->route('quotations.show', $quotation)
                ->with('error', 'PDF hanya tersedia untuk quotation dengan status approved atau final.');
        }

        $quotation->load(['inquiry.customer', 'items', 'itinerary']);
        if ($quotation->itinerary) {
            $itineraryPdfPayload = $this->buildItineraryPdfPayload($quotation->itinerary);
            $pdf = PDF::loadView('pdf.quotation_with_itinerary', array_merge(
                ['quotation' => $quotation],
                $itineraryPdfPayload
            ))->setPaper('a4', 'portrait');

            return $pdf->stream('quotation-' . Str::slug((string) ($quotation->quotation_number ?: 'document')) . '.pdf');
        }

        $pdf = PDF::loadView('pdf.quotation', compact('quotation'))->setPaper('a4', 'portrait');
        return $pdf->stream('quotation-' . Str::slug((string) ($quotation->quotation_number ?: 'document')) . '.pdf');
    }

    public function exportCsv()
    {
        $this->autoFinalizeExpiredApprovedQuotations();

        $query = Quotation::query()->withTrashed()->with(['inquiry.customer']);
        $scope = strtolower(trim((string) request('scope')));
        if ($scope === 'published') {
            $query->whereIn('status', ['approved', Quotation::FINAL_STATUS]);
        } elseif ($scope === 'my') {
            if (Schema::hasColumn('quotations', 'created_by')) {
                $query->where('created_by', (int) auth()->id());
            } else {
                $query->whereRaw('1 = 0');
            }
        }

        $this->applyQuotationKeywordFilter($query, (string) request('q'));

        $status = strtolower(trim((string) request('status')));
        if ($scope === 'published') {
            if (in_array($status, ['approved', Quotation::FINAL_STATUS], true)) {
                $query->where('status', $status);
            }
        } elseif ($scope === 'my') {
            if ($status !== '' && in_array($status, Quotation::STATUS_OPTIONS, true)) {
                $query->where('status', $status);
            }
        } else {
            $query->when(request('status'), fn ($q) => $q->where('status', request('status')));
        }
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

    private function buildItineraryPdfPayload(Itinerary $itinerary): array
    {
        $itinerary->load([
            'touristAttractions:id,name,location,latitude,longitude,description,gallery_images',
            'itineraryActivities.activity:id,vendor_id,name,activity_type,duration_minutes,adult_publish_rate,child_publish_rate,notes,includes,excludes,gallery_images',
            'itineraryActivities.activity.vendor:id,name,location,city,province,latitude,longitude',
            'itineraryIslandTransfers.islandTransfer:id,vendor_id,name,transfer_type,departure_point_name,arrival_point_name,duration_minutes,notes',
            'itineraryIslandTransfers.islandTransfer.vendor:id,name,location,city,province,latitude,longitude',
            'itineraryFoodBeverages.foodBeverage:id,vendor_id,name,service_type,duration_minutes,publish_rate,meal_period,notes,menu_highlights,gallery_images',
            'itineraryFoodBeverages.foodBeverage.vendor:id,name,location,city,province,latitude,longitude',
            'itineraryTransportUnits.transportUnit:id,name,brand_model,seat_capacity,luggage_capacity,air_conditioned,with_driver,images',
            'itineraryTransportUnits.transportUnit.transport:id,name,transport_type',
            'dayPoints',
            'dayPoints.startAirport:id,name,location,city,province',
            'dayPoints.startHotel:id,name,address,city,province',
            'dayPoints.startHotelRoom:id,hotels_id,rooms,view,cover',
            'dayPoints.endAirport:id,name,location,city,province',
            'dayPoints.endHotel:id,name,address,city,province',
            'dayPoints.endHotelRoom:id,hotels_id,rooms,view,cover',
            'inquiry:id,inquiry_number,customer_id,status,priority,source,deadline,notes',
            'inquiry.customer:id,name,code',
        ]);

        $scheduleByDay = [];
        $dayPointByDay = $itinerary->dayPoints->keyBy(fn ($point) => (int) $point->day_number);
        $transportUnitByDay = $itinerary->itineraryTransportUnits->keyBy(fn ($item) => (int) $item->day_number);
        $toMinutes = static function (?string $time): ?int {
            $value = substr((string) $time, 0, 5);
            if (! preg_match('/^\d{2}:\d{2}$/', $value)) {
                return null;
            }

            return ((int) substr($value, 0, 2) * 60) + (int) substr($value, 3, 2);
        };
        $fromMinutes = static function (?int $minutes): ?string {
            if (! is_int($minutes)) {
                return null;
            }
            $normalized = max(0, $minutes);
            $hours = (int) floor($normalized / 60);
            $mins = $normalized % 60;

            return str_pad((string) $hours, 2, '0', STR_PAD_LEFT) . ':' . str_pad((string) $mins, 2, '0', STR_PAD_LEFT);
        };
        $resolvePoint = function ($dayPoint, string $scope, array $previousEnd = ['name' => 'Not set', 'location' => '-', 'type' => 'Unknown', 'thumbnail_data_uri' => null]): array {
            if (! $dayPoint) {
                return $scope === 'start'
                    ? array_merge($previousEnd, ['label' => $previousEnd['label'] ?? ($previousEnd['name'] ?? 'Not set')])
                    : ['name' => 'Not set', 'location' => '-', 'type' => 'Unknown', 'label' => 'Not set', 'thumbnail_data_uri' => null];
            }
            if ($scope === 'start') {
                $type = (string) ($dayPoint->start_point_type ?? '');
                if ($type === 'previous_day_end') {
                    return $previousEnd;
                }
                if ($type === 'airport') {
                    return [
                        'name' => (string) ($dayPoint->startAirport?->name ?? 'Not set'),
                        'location' => (string) ($dayPoint->startAirport?->location ?? '-'),
                        'type' => 'Airport',
                        'label' => (string) ($dayPoint->startAirport?->name ?? 'Not set'),
                        'thumbnail_data_uri' => null,
                    ];
                }
                if ($type === 'hotel') {
                    $hotelName = (string) ($dayPoint->startHotel?->name ?? 'Not set');
                    $roomName = (string) ($dayPoint->startHotelRoom?->rooms ?? '');
                    $label = $roomName !== ''
                        ? ($hotelName . ' - ' . $roomName)
                        : $hotelName;

                    return [
                        'name' => $label,
                        'location' => (string) ($dayPoint->startHotel?->address ?? '-'),
                        'type' => 'Hotel',
                        'label' => $label,
                        'thumbnail_data_uri' => $this->resolveHotelRoomCoverDataUri($dayPoint->startHotelRoom?->cover),
                    ];
                }

                return ['name' => 'Not set', 'location' => '-', 'type' => 'Unknown', 'label' => 'Not set', 'thumbnail_data_uri' => null];
            }

            $type = (string) ($dayPoint->end_point_type ?? '');
            if ($type === 'airport') {
                return [
                    'name' => (string) ($dayPoint->endAirport?->name ?? 'Not set'),
                    'location' => (string) ($dayPoint->endAirport?->location ?? '-'),
                    'type' => 'Airport',
                    'label' => (string) ($dayPoint->endAirport?->name ?? 'Not set'),
                    'thumbnail_data_uri' => null,
                ];
            }
            if ($type === 'hotel') {
                $hotelName = (string) ($dayPoint->endHotel?->name ?? 'Not set');
                $roomName = (string) ($dayPoint->endHotelRoom?->rooms ?? '');
                $label = $roomName !== ''
                    ? ($hotelName . ' - ' . $roomName)
                    : $hotelName;

                return [
                    'name' => $label,
                    'location' => (string) ($dayPoint->endHotel?->address ?? '-'),
                    'type' => 'Hotel',
                    'label' => $label,
                    'thumbnail_data_uri' => $this->resolveHotelRoomCoverDataUri($dayPoint->endHotelRoom?->cover),
                ];
            }

            return ['name' => 'Not set', 'location' => '-', 'type' => 'Unknown', 'label' => 'Not set', 'thumbnail_data_uri' => null];
        };
        $previousEndPoint = ['name' => 'Not set', 'location' => '-', 'type' => 'Unknown', 'label' => 'Not set', 'thumbnail_data_uri' => null];
        for ($day = 1; $day <= (int) $itinerary->duration_days; $day++) {
            $dayPoint = $dayPointByDay[$day] ?? null;
            $mainExperienceType = (string) ($dayPoint?->main_experience_type ?? '');
            if (! in_array($mainExperienceType, ['attraction', 'activity', 'transfer', 'fnb'], true)) {
                $mainExperienceType = '';
            }
            $mainExperienceId = $mainExperienceType === 'attraction'
                ? (int) ($dayPoint?->main_tourist_attraction_id ?? 0)
                : ($mainExperienceType === 'activity'
                    ? (int) ($dayPoint?->main_activity_id ?? 0)
                    : ($mainExperienceType === 'transfer'
                        ? (int) ($dayPoint?->main_island_transfer_id ?? 0)
                        : ($mainExperienceType === 'fnb'
                        ? (int) ($dayPoint?->main_food_beverage_id ?? 0)
                        : 0)));

            $attractions = $itinerary->touristAttractions
                ->filter(fn ($attraction) => (int) ($attraction->pivot->day_number ?? 0) === $day)
                ->map(function ($attraction) {
                    return [
                        'type' => 'Attraction',
                        'source_type' => 'attraction',
                        'source_id' => (int) $attraction->id,
                        'name' => (string) $attraction->name,
                        'location' => (string) ($attraction->location ?? '-'),
                        'description' => (string) ($attraction->description ?? '-'),
                        'thumbnail_data_uri' => $this->resolveGalleryImageDataUri($attraction->gallery_images ?? []),
                        'pax' => null,
                        'start_time' => $attraction->pivot->start_time ? substr((string) $attraction->pivot->start_time, 0, 5) : '--:--',
                        'end_time' => $attraction->pivot->end_time ? substr((string) $attraction->pivot->end_time, 0, 5) : '--:--',
                        'travel_minutes_to_next' => $attraction->pivot->travel_minutes_to_next,
                        'visit_order' => (int) ($attraction->pivot->visit_order ?? 999999),
                    ];
                });

            $activities = $itinerary->itineraryActivities
                ->filter(fn ($item) => (int) ($item->day_number ?? 0) === $day)
                ->map(function ($item) {
                    return [
                        'type' => 'Activity',
                        'source_type' => 'activity',
                        'source_id' => (int) ($item->activity_id ?? 0),
                        'name' => (string) ($item->activity->name ?? '-'),
                        'location' => (string) ($item->activity->vendor->location ?? '-'),
                        'description' => (string) ($item->activity->notes ?? '-'),
                        'includes' => (string) ($item->activity->includes ?? ''),
                        'excludes' => (string) ($item->activity->excludes ?? ''),
                        'thumbnail_data_uri' => $this->resolveGalleryImageDataUri($item->activity->gallery_images ?? []),
                        'pax' => (int) ($item->pax ?? 0),
                        'pax_adult' => (int) ($item->pax_adult ?? $item->pax ?? 0),
                        'pax_child' => (int) ($item->pax_child ?? 0),
                        'start_time' => $item->start_time ? substr((string) $item->start_time, 0, 5) : '--:--',
                        'end_time' => $item->end_time ? substr((string) $item->end_time, 0, 5) : '--:--',
                        'travel_minutes_to_next' => $item->travel_minutes_to_next,
                        'visit_order' => (int) ($item->visit_order ?? 999999),
                    ];
                });

            $foodBeverages = $itinerary->itineraryFoodBeverages
                ->filter(fn ($item) => (int) ($item->day_number ?? 0) === $day)
                ->map(function ($item) {
                    return [
                        'type' => 'F&B',
                        'source_type' => 'fnb',
                        'source_id' => (int) ($item->food_beverage_id ?? 0),
                        'name' => (string) ($item->foodBeverage->name ?? '-'),
                        'vendor_name' => (string) ($item->foodBeverage->vendor->name ?? '-'),
                        'menu_highlights' => (string) ($item->foodBeverage->menu_highlights ?? ''),
                        'location' => (string) ($item->foodBeverage->vendor->location ?? '-'),
                        'description' => (string) ($item->foodBeverage->notes ?? $item->foodBeverage->menu_highlights ?? '-'),
                        'thumbnail_data_uri' => $this->resolveGalleryImageDataUri($item->foodBeverage->gallery_images ?? []),
                        'publish_rate' => (float) ($item->foodBeverage->publish_rate ?? 0),
                        'currency' => 'IDR',
                        'pax' => (int) ($item->pax ?? 0),
                        'start_time' => $item->start_time ? substr((string) $item->start_time, 0, 5) : '--:--',
                        'end_time' => $item->end_time ? substr((string) $item->end_time, 0, 5) : '--:--',
                        'travel_minutes_to_next' => $item->travel_minutes_to_next,
                        'visit_order' => (int) ($item->visit_order ?? 999999),
                    ];
                });

            $islandTransfers = $itinerary->itineraryIslandTransfers
                ->filter(fn ($item) => (int) ($item->day_number ?? 0) === $day)
                ->map(function ($item) {
                    $transfer = $item->islandTransfer;
                    return [
                        'type' => 'Island Transfer',
                        'source_type' => 'transfer',
                        'source_id' => (int) ($item->island_transfer_id ?? 0),
                        'name' => (string) ($transfer->name ?? '-'),
                        'location' => trim((string) (($transfer->departure_point_name ?? '-') . ' -> ' . ($transfer->arrival_point_name ?? '-'))),
                        'description' => (string) ($transfer->notes ?? '-'),
                        'thumbnail_data_uri' => null,
                        'pax' => (int) ($item->pax ?? 0),
                        'start_time' => $item->start_time ? substr((string) $item->start_time, 0, 5) : '--:--',
                        'end_time' => $item->end_time ? substr((string) $item->end_time, 0, 5) : '--:--',
                        'travel_minutes_to_next' => $item->travel_minutes_to_next,
                        'visit_order' => (int) ($item->visit_order ?? 999999),
                    ];
                });

            $items = $attractions->merge($activities)->merge($islandTransfers)->merge($foodBeverages)
                ->sortBy('visit_order')
                ->values()
                ->map(function (array $item) use ($mainExperienceType, $mainExperienceId) {
                    $item['is_main_experience'] = $mainExperienceType !== ''
                        && $mainExperienceId > 0
                        && (string) ($item['source_type'] ?? '') === $mainExperienceType
                        && (int) ($item['source_id'] ?? 0) === $mainExperienceId;

                    return $item;
                });
            $startPoint = $resolvePoint($dayPoint, 'start', $previousEndPoint);
            $endPoint = $resolvePoint($dayPoint, 'end', ['name' => 'Not set', 'location' => '-', 'type' => 'Unknown']);
            $startTime = $dayPoint && ! empty($dayPoint->day_start_time)
                ? substr((string) $dayPoint->day_start_time, 0, 5)
                : ($items->pluck('start_time')->filter(fn ($time) => $time !== '--:--')->first() ?? '--:--');
            $startTravelMinutes = $dayPoint && $dayPoint->day_start_travel_minutes !== null
                ? max(0, (int) $dayPoint->day_start_travel_minutes)
                : null;
            $lastItem = $items->last();
            $lastEndBaseMinutes = $lastItem ? $toMinutes($lastItem['end_time'] ?? null) : null;
            $lastTravelToEnd = $lastItem ? max(0, (int) ($lastItem['travel_minutes_to_next'] ?? 0)) : 0;
            $startBaseMinutes = $toMinutes($startTime !== '--:--' ? $startTime : null);
            $endTime = $lastEndBaseMinutes !== null
                ? ($fromMinutes($lastEndBaseMinutes + $lastTravelToEnd) ?? '--:--')
                : ($startBaseMinutes !== null
                    ? ($fromMinutes($startBaseMinutes + max(0, (int) ($startTravelMinutes ?? 0))) ?? '--:--')
                    : '--:--');
            $dayTransportItem = $transportUnitByDay[$day] ?? null;
            $dayTransportUnit = $dayTransportItem?->transportUnit;
            $transportMaster = $dayTransportUnit?->transport;
            $transportUnitImage = $dayTransportUnit
                ? $this->resolveGalleryImageDataUri($dayTransportUnit->images ?? [])
                : null;
            $dayTransport = [
                'assigned' => (bool) $dayTransportUnit,
                'unit_name' => (string) ($dayTransportUnit?->name ?? '-'),
                'brand_model' => (string) ($dayTransportUnit?->brand_model ?? '-'),
                'seat_capacity' => $dayTransportUnit?->seat_capacity !== null ? (int) $dayTransportUnit->seat_capacity : null,
                'luggage_capacity' => $dayTransportUnit?->luggage_capacity !== null ? (int) $dayTransportUnit->luggage_capacity : null,
                'currency' => 'IDR',
                'with_driver' => (bool) ($dayTransportUnit?->with_driver ?? false),
                'air_conditioned' => (bool) ($dayTransportUnit?->air_conditioned ?? false),
                'transport_name' => (string) ($transportMaster?->name ?? '-'),
                'transport_type' => (string) ($transportMaster?->transport_type ?? '-'),
                'provider_name' => '-',
                'location' => '-',
                'city' => '-',
                'province' => '-',
                'thumbnail_data_uri' => $transportUnitImage,
            ];
            $timelineItems = collect([
                [
                    'type' => 'Start Point',
                    'name' => $startPoint['name'],
                    'location' => $startPoint['location'],
                    'description' => '-',
                    'thumbnail_data_uri' => $startPoint['thumbnail_data_uri'] ?? null,
                    'pax' => null,
                    'start_time' => $startTime,
                    'end_time' => null,
                    'travel_minutes_to_next' => $startTravelMinutes,
                    'visit_order' => 0,
                    'point_role' => 'start',
                    'point_type_label' => $startPoint['type'] ?? 'Unknown',
                    'is_main_experience' => false,
                ],
            ])->merge($items)->push([
                'type' => 'End Point',
                'name' => $endPoint['name'],
                'location' => $endPoint['location'],
                'description' => '-',
                'thumbnail_data_uri' => $endPoint['thumbnail_data_uri'] ?? null,
                'pax' => null,
                'start_time' => null,
                'end_time' => $endTime,
                'travel_minutes_to_next' => null,
                'visit_order' => 9999999,
                'point_role' => 'end',
                'point_type_label' => $endPoint['type'] ?? 'Unknown',
                'is_main_experience' => false,
            ])->values();

            $scheduleByDay[] = [
                'day' => $day,
                'start_time' => $startTime,
                'end_time' => $endTime,
                'start_travel_minutes' => $startTravelMinutes,
                'start_point_type_label' => $startPoint['label'] ?? ($startPoint['type'] ?? 'Unknown'),
                'end_point_type_label' => $endPoint['label'] ?? ($endPoint['type'] ?? 'Unknown'),
                'transport_unit' => $dayTransport,
                'items' => $timelineItems,
            ];
            $previousEndPoint = $endPoint;
        }

        return [
            'itinerary' => $itinerary,
            'scheduleByDay' => $scheduleByDay,
            'companyName' => (string) config('app.name', 'Voyex CRM'),
            'companyTagline' => (string) env('COMPANY_TAGLINE', 'Travel Itinerary & Experience Planner'),
            'companyLogoDataUri' => $this->resolveCompanyLogoDataUri(),
        ];
    }

    private function resolveGalleryImageDataUri($galleryImages): ?string
    {
        $images = is_array($galleryImages) ? $galleryImages : [];
        foreach ($images as $path) {
            if (! is_string($path) || trim($path) === '') {
                continue;
            }
            $normalizedPath = trim(str_replace('\\', '/', $path), '/');
            $thumbnailPath = ImageThumbnailGenerator::thumbnailPathFor($normalizedPath);
            $thumbnailDataUri = $this->resolveStorageImageDataUri($thumbnailPath);
            if ($thumbnailDataUri) {
                return $thumbnailDataUri;
            }

            if (Storage::disk('public')->exists($normalizedPath)) {
                ImageThumbnailGenerator::generate('public', $normalizedPath, 360, 240);
                $thumbnailDataUri = $this->resolveStorageImageDataUri($thumbnailPath);
                if ($thumbnailDataUri) {
                    return $thumbnailDataUri;
                }
            }

            $originalDataUri = $this->resolveStorageImageDataUri($normalizedPath);
            if ($originalDataUri) {
                return $originalDataUri;
            }
        }

        return null;
    }

    private function resolveHotelRoomCoverDataUri(?string $coverPath): ?string
    {
        $rawPath = trim(str_replace('\\', '/', (string) $coverPath), '/');
        if ($rawPath === '') {
            return null;
        }

        if (Str::startsWith($rawPath, ['http://', 'https://'])) {
            return null;
        }

        if (Str::startsWith($rawPath, 'storage/')) {
            $rawPath = Str::after($rawPath, 'storage/');
        }

        $candidates = [$rawPath];
        if (! Str::contains($rawPath, '/')) {
            $candidates[] = 'hotels/rooms/' . $rawPath;
        }

        foreach (array_values(array_unique($candidates)) as $candidate) {
            $thumbnailPath = ImageThumbnailGenerator::thumbnailPathFor($candidate);
            $thumbnailDataUri = $this->resolveStorageImageDataUri($thumbnailPath);
            if ($thumbnailDataUri) {
                return $thumbnailDataUri;
            }

            if (Storage::disk('public')->exists($candidate)) {
                ImageThumbnailGenerator::generate('public', $candidate, 360, 240);
                $thumbnailDataUri = $this->resolveStorageImageDataUri($thumbnailPath);
                if ($thumbnailDataUri) {
                    return $thumbnailDataUri;
                }
            }

            $originalDataUri = $this->resolveStorageImageDataUri($candidate);
            if ($originalDataUri) {
                return $originalDataUri;
            }
        }

        return null;
    }

    private function resolveStorageImageDataUri(string $path): ?string
    {
        $storage = Storage::disk('public');
        if (! $storage->exists($path)) {
            return null;
        }
        $binary = $storage->get($path);
        if ($binary === '') {
            return null;
        }

        $extension = strtolower((string) pathinfo($path, PATHINFO_EXTENSION));
        $mime = match ($extension) {
            'jpg', 'jpeg' => 'image/jpeg',
            'png' => 'image/png',
            'webp' => 'image/webp',
            'gif' => 'image/gif',
            default => 'application/octet-stream',
        };

        return 'data:' . $mime . ';base64,' . base64_encode($binary);
    }

    private function resolveCompanyLogoDataUri(): string
    {
        $candidates = [
            public_path('images/company-logo.png'),
            public_path('images/logo.png'),
            public_path('logo.png'),
        ];

        foreach ($candidates as $path) {
            if (! File::exists($path)) {
                continue;
            }
            $binary = File::get($path);
            $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));
            $mime = match ($extension) {
                'svg' => 'image/svg+xml',
                'jpg', 'jpeg' => 'image/jpeg',
                'gif' => 'image/gif',
                'webp' => 'image/webp',
                default => 'image/png',
            };

            return 'data:' . $mime . ';base64,' . base64_encode($binary);
        }

        $name = (string) config('app.name', 'VOYEX');
        $initials = strtoupper(substr(preg_replace('/[^A-Za-z0-9]/', '', $name), 0, 3) ?: 'VYX');
        $svg = <<<SVG
<svg xmlns="http://www.w3.org/2000/svg" width="360" height="90" viewBox="0 0 360 90">
  <rect width="360" height="90" rx="14" fill="#0f172a"/>
  <circle cx="48" cy="45" r="24" fill="#1d4ed8"/>
  <text x="48" y="51" text-anchor="middle" font-family="Arial, sans-serif" font-size="16" font-weight="700" fill="#ffffff">{$initials}</text>
  <text x="86" y="40" font-family="Arial, sans-serif" font-size="20" font-weight="700" fill="#ffffff">{$name}</text>
  <text x="86" y="60" font-family="Arial, sans-serif" font-size="11" fill="#cbd5e1">Professional Itinerary</text>
</svg>
SVG;

        return 'data:image/svg+xml;base64,' . base64_encode($svg);
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
        $this->autoFinalizeApprovedQuotationIfExpired($quotation);
        if ($quotation->isFinal()) {
            return redirect()->back()->with('error', 'Final quotation cannot be modified.');
        }
        $this->quotationValidationService->syncValidationRequirements($quotation);
        if (! $this->quotationValidationService->canBeApproved($quotation)) {
            return redirect()
                ->route('quotations.show', $quotation)
                ->with('error', 'Quotation cannot be approved because validation is not completed.');
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
            return redirect()->back()->with('error', 'You do not have permission to approve this quotation.');
        }
        $alreadyApprovedByUser = $quotation->approvals()
            ->where('user_id', $user->id)
            ->exists();
        if ($alreadyApprovedByUser) {
            return redirect()
                ->route('quotations.show', $quotation)
                ->with('error', 'Anda sudah melakukan approval untuk quotation ini.');
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

        if ($note !== '' && $this->canWriteApprovalNote($user)) {
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
        $this->autoFinalizeApprovedQuotationIfExpired($quotation);
        $user = $request->user();
        if (! $user || ! $user->can('quotations.reject')) {
            return redirect()->back()->with('error', 'You do not have permission to reject quotation.');
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
        $this->syncLinkedLifecycleStatusesForQuotation($quotation);

        return redirect()
            ->route('quotations.show', $quotation)
            ->with('success', 'Quotation rejected.');
    }

    public function setPending(Request $request, Quotation $quotation)
    {
        $this->autoFinalizeApprovedQuotationIfExpired($quotation);
        if ($quotation->isFinal()) {
            return redirect()->back()->with('error', 'Final quotation cannot be modified.');
        }
        $user = $request->user();
        if (! $user || ! $this->canSetQuotationPending($user)) {
            return redirect()->back()->with('error', 'You do not have permission to set quotation to pending.');
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
        $this->syncLinkedLifecycleStatusesForQuotation($quotation);

        return redirect()
            ->route('quotations.show', $quotation)
            ->with('success', 'Quotation status changed to pending.');
    }

    public function setFinal(Request $request, Quotation $quotation)
    {
        $this->autoFinalizeApprovedQuotationIfExpired($quotation);

        if ($quotation->isFinal()) {
            return redirect()
                ->route('quotations.show', $quotation)
                ->with('success', 'Quotation is already final.');
        }

        $user = $request->user();
        if (! $user || ! $quotation->isCreator($user)) {
            return redirect()
                ->route('quotations.show', $quotation)
                ->with('error', 'Only quotation creator can set status to final.');
        }

        if ((string) ($quotation->status ?? '') !== 'approved') {
            return redirect()
                ->route('quotations.show', $quotation)
                ->with('error', 'Only approved quotation can be set to final.');
        }

        $quotation->update([
            'status' => Quotation::FINAL_STATUS,
        ]);
        $this->syncLinkedLifecycleStatusesForQuotation($quotation);

        return redirect()
            ->route('quotations.show', $quotation)
            ->with('success', 'Quotation status changed to final.');
    }

    public function updateGlobalDiscount(Request $request, Quotation $quotation)
    {
        $this->autoFinalizeApprovedQuotationIfExpired($quotation);
        if ($quotation->isFinal()) {
            return redirect()->back()->with('error', 'Final quotation cannot be modified.');
        }
        if (! $this->canManageQuotation($quotation, 'update')) {
            return $this->denyQuotationMutation($quotation);
        }
        if (! $this->canApplyGlobalDiscount()) {
            return redirect()->back()->with('error', 'You do not have permission to set global discount.');
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

        $wasApproved = ((string) ($quotation->status ?? '') === 'approved');
        $quotation->update([
            'discount_type' => $discountType,
            'discount_value' => $discountValue,
            'sub_total' => $subTotal,
            'final_amount' => max(0, $subTotal - $discountAmount),
        ]);
        if ($wasApproved) {
            $quotation->approvals()->delete();
            Quotation::withoutActivityLogging(function () use ($quotation): void {
                $quotation->update([
                    'status' => 'pending',
                    'approved_by' => null,
                    'approved_at' => null,
                    'approval_note' => null,
                    'approval_note_by' => null,
                ]);
            });
            $this->syncLinkedLifecycleStatusesForQuotation($quotation);
        }

        return redirect()
            ->route('quotations.edit', $quotation)
            ->with('success', $wasApproved ? 'Global discount updated. Status changed to pending for re-approval.' : 'Global discount updated.');
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
            IslandTransfer::class,
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
            'transfer',
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

    private function computeQuotationKpiSummary(Quotation $quotation): array
    {
        $subTotal = 0.0;
        $itemDiscountTotal = 0.0;

        foreach ($quotation->items as $item) {
            $qty = max(0, (int) ($item->qty ?? 0));
            $itemType = (string) ($item->itinerary_item_type ?? '');
            $isManualItem = $itemType === 'manual';

            $contractRate = max(0, (float) ($item->contract_rate ?? 0));
            $markupType = (string) (($item->markup_type ?? 'fixed') === 'percent' ? 'percent' : 'fixed');
            $markupValue = max(0, (float) ($item->markup ?? 0));

            if ($isManualItem) {
                $unitPrice = max(0, (float) ($item->unit_price ?? 0));
                $baseAmount = $qty * $unitPrice;
            } else {
                $unitPriceFromMarkup = $markupType === 'percent'
                    ? ($contractRate + ($contractRate * ($markupValue / 100)))
                    : ($contractRate + $markupValue);
                $fallbackUnitPrice = max(0, (float) ($item->unit_price ?? 0));
                $effectiveUnitPrice = $contractRate > 0 || $markupValue > 0 ? $unitPriceFromMarkup : $fallbackUnitPrice;
                $baseAmount = $qty * max(0, $effectiveUnitPrice);
            }

            $itemDiscountType = (string) (($item->discount_type ?? 'fixed') === 'percent' ? 'percent' : 'fixed');
            $itemDiscountValue = max(0, (float) ($item->discount ?? 0));
            $itemDiscountAmount = $itemDiscountType === 'percent'
                ? ($baseAmount * (min(100, $itemDiscountValue) / 100))
                : $itemDiscountValue;

            $rowTotal = max(0, $baseAmount - $itemDiscountAmount);
            $appliedItemDiscount = $baseAmount - $rowTotal;

            $subTotal += $rowTotal;
            $itemDiscountTotal += $appliedItemDiscount;
        }

        $globalDiscountType = (string) ($quotation->discount_type ?? '');
        $globalDiscountValue = max(0, (float) ($quotation->discount_value ?? 0));
        $globalDiscountAmount = 0.0;

        if ($globalDiscountType === 'percent') {
            $globalDiscountAmount = $subTotal * (min(100, $globalDiscountValue) / 100);
        } elseif ($globalDiscountType === 'fixed') {
            $globalDiscountAmount = $globalDiscountValue;
        }

        $appliedGlobalDiscount = min($subTotal, $globalDiscountAmount);
        $finalAmount = max(0, $subTotal - $appliedGlobalDiscount);

        return [
            'sub_total' => $subTotal,
            'item_discount_total' => $itemDiscountTotal,
            'global_discount_type' => $globalDiscountType,
            'global_discount_value' => $globalDiscountValue,
            'global_discount_amount' => $appliedGlobalDiscount,
            'final_amount' => $finalAmount,
        ];
    }

    private function assertPricingPermission(array $validated): void
    {
        $hasDiscount = (float) ($validated['discount_value'] ?? 0) > 0;
        if ($hasDiscount && ! $this->canApplyGlobalDiscount()) {
            abort(403, 'You do not have permission to apply discounts.');
        }
    }

    private function canApplyGlobalDiscount(): bool
    {
        $user = auth()->user();
        if (! $user) {
            return false;
        }

        return $user->can('quotations.global_discount');
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

    private function availableItinerariesQuery(?int $includeItineraryId = null): Builder
    {
        $query = Itinerary::query()->with(['inquiry.customer', 'inquiry.assignedUser']);
        $hasIncludedItinerary = $includeItineraryId && $includeItineraryId > 0;

        if (Schema::hasColumn('itineraries', 'is_active')) {
            if ($hasIncludedItinerary) {
                $query->where(function (Builder $builder) use ($includeItineraryId): void {
                    $builder->where(function (Builder $activeBuilder): void {
                        $activeBuilder->where('is_active', true)->orWhereNull('is_active');
                    })->orWhere('id', $includeItineraryId);
                });
            } else {
                $query->where(function (Builder $builder): void {
                    $builder->where('is_active', true)->orWhereNull('is_active');
                });
            }
        }

        if (Schema::hasColumn('itineraries', 'status')) {
            if ($hasIncludedItinerary) {
                $query->where(function (Builder $builder) use ($includeItineraryId): void {
                    $builder->where('status', '!=', Itinerary::FINAL_STATUS)
                        ->orWhere('id', $includeItineraryId);
                });
            } else {
                $query->where('status', '!=', Itinerary::FINAL_STATUS);
            }
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
            ->with('error', 'You do not have permission to modify this quotation.');
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

    private function autoFinalizeExpiredApprovedQuotations(): void
    {
        $expiredApprovedQuotationIds = Quotation::query()
            ->whereNull('deleted_at')
            ->where('status', 'approved')
            ->whereDate('validity_date', '<', now()->toDateString())
            ->pluck('id');

        if ($expiredApprovedQuotationIds->isEmpty()) {
            return;
        }

        Quotation::withoutActivityLogging(function () use ($expiredApprovedQuotationIds): void {
            Quotation::query()
                ->whereIn('id', $expiredApprovedQuotationIds)
                ->update([
                    'status' => Quotation::FINAL_STATUS,
                ]);
        });

        Quotation::query()
            ->whereIn('id', $expiredApprovedQuotationIds)
            ->get()
            ->each(function (Quotation $quotation): void {
                $this->syncLinkedLifecycleStatusesForQuotation($quotation);
            });
    }

    private function autoFinalizeApprovedQuotationIfExpired(Quotation $quotation): bool
    {
        if ((string) ($quotation->status ?? '') !== 'approved') {
            return false;
        }

        $validityDate = $quotation->validity_date;
        if (! $validityDate || ! $validityDate->isBefore(now()->startOfDay())) {
            return false;
        }

        Quotation::withoutActivityLogging(function () use ($quotation): void {
            $quotation->update([
                'status' => Quotation::FINAL_STATUS,
            ]);
        });

        $quotation->refresh();
        $this->syncLinkedLifecycleStatusesForQuotation($quotation);

        return true;
    }

    private function resolveApprovalRoleForUser($user): ?string
    {
        if (! $user) {
            return null;
        }

        if (! $user->can('quotations.approve')) {
            return null;
        }

        if ($user->can('dashboard.director.view')) {
            return 'director';
        }
        if ($user->can('dashboard.manager.view')) {
            return 'manager';
        }
        if ($user->can('dashboard.reservation.view')) {
            return 'reservation';
        }

        return null;
    }

    private function canSetQuotationPending($user): bool
    {
        if (! $user) {
            return false;
        }

        return $user->can('quotations.set_pending');
    }

    private function canWriteApprovalNote($user): bool
    {
        if (! $user) {
            return false;
        }

        return $user->can('quotations.set_pending');
    }

    private function syncLinkedLifecycleStatusesForQuotation(Quotation $quotation, ?int $previousItineraryId = null): void
    {
        $itineraryIds = collect([
            $previousItineraryId ? (int) $previousItineraryId : null,
            $quotation->itinerary_id ? (int) $quotation->itinerary_id : null,
        ])->filter(fn ($id) => is_int($id) && $id > 0)
            ->unique()
            ->values();

        if ($itineraryIds->isNotEmpty()) {
            Itinerary::query()
                ->whereIn('id', $itineraryIds->all())
                ->get()
                ->each(function (Itinerary $itinerary): void {
                    $itinerary->syncLifecycleStatus();
                });
        }

        $inquiryIds = collect([
            $quotation->inquiry_id ? (int) $quotation->inquiry_id : null,
        ]);

        if ($quotation->itinerary_id) {
            $currentItineraryInquiryId = Itinerary::query()
                ->whereKey((int) $quotation->itinerary_id)
                ->value('inquiry_id');
            if ($currentItineraryInquiryId) {
                $inquiryIds->push((int) $currentItineraryInquiryId);
            }
        }

        if ($previousItineraryId) {
            $previousItineraryInquiryId = Itinerary::query()
                ->whereKey((int) $previousItineraryId)
                ->value('inquiry_id');
            if ($previousItineraryInquiryId) {
                $inquiryIds->push((int) $previousItineraryInquiryId);
            }
        }

        $inquiryIds = $inquiryIds
            ->filter(fn ($id) => is_int($id) && $id > 0)
            ->unique()
            ->values();

        if ($inquiryIds->isEmpty()) {
            return;
        }

        Inquiry::query()
            ->whereIn('id', $inquiryIds->all())
            ->get()
            ->each(function (Inquiry $inquiry): void {
                $targetInquiryStatus = $this->resolveInquiryStatusFromLinkedQuotations((int) $inquiry->id);
                if ((string) ($inquiry->status ?? '') === $targetInquiryStatus) {
                    return;
                }

                $inquiry->update([
                    'status' => $targetInquiryStatus,
                ]);
            });
    }

    private function resolveInquiryStatusFromLinkedQuotations(int $inquiryId): string
    {
        if ($inquiryId <= 0) {
            return 'processed';
        }

        $hasFinalQuotation = Quotation::query()
            ->whereNull('deleted_at')
            ->where('status', Quotation::FINAL_STATUS)
            ->where(function (Builder $query) use ($inquiryId): void {
                $query->where('inquiry_id', $inquiryId)
                    ->orWhereHas('itinerary', function (Builder $itineraryQuery) use ($inquiryId): void {
                        $itineraryQuery->where('inquiry_id', $inquiryId);
                    });
            })
            ->exists();

        return $hasFinalQuotation ? Inquiry::FINAL_STATUS : 'processed';
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

        $query->whereDoesntHave('approvals', function (Builder $inner) use ($user): void {
            $inner->where('user_id', (int) $user->id);
        });
        if (Schema::hasColumn('quotations', 'created_by')) {
            $query->whereRaw(
                '(SELECT COUNT(*) FROM quotation_approvals qa'
                .' WHERE qa.quotation_id = quotations.id'
                .' AND (quotations.created_by IS NULL OR qa.user_id <> quotations.created_by)) < 2'
            );
            return;
        }

        $query->whereRaw(
            '(SELECT COUNT(*) FROM quotation_approvals qa'
            .' WHERE qa.quotation_id = quotations.id) < 2'
        );
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
        $nonCreatorApprovalCount = $approvals
            ->filter(function ($approval) use ($quotation): bool {
                if (! Schema::hasColumn('quotations', 'created_by')) {
                    return true;
                }

                return (int) ($approval->user_id ?? 0) !== (int) ($quotation->created_by ?? 0);
            })
            ->count();

        return $nonCreatorApprovalCount < 2;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function buildQuotationStatsCards(?int $creatorId = null, ?array $statusScope = null, bool $includeTrashed = true): array
    {
        $query = Quotation::query();
        if ($includeTrashed) {
            $query->withTrashed();
        }
        if ($creatorId && Schema::hasColumn('quotations', 'created_by')) {
            $query->where('created_by', $creatorId);
        }
        if (is_array($statusScope) && ! empty($statusScope)) {
            $query->whereIn('status', $statusScope);
        }

        $counts = $query
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

    private function applyQuotationKeywordFilter(Builder $query, string $term): void
    {
        $term = trim($term);
        if ($term === '') {
            return;
        }

        $query->where(function (Builder $builder) use ($term): void {
            $builder->where('quotation_number', 'like', "%{$term}%")
                ->orWhereHas('inquiry', function ($inquiryQuery) use ($term) {
                    $inquiryQuery->where('inquiry_number', 'like', "%{$term}%")
                        ->orWhereHas('customer', function ($customerQuery) use ($term) {
                            $customerQuery->where('name', 'like', "%{$term}%");
                        });
                });
        });
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
     *   required_non_creator_approvals: int,
     *   non_creator_approval_count: int,
     *   remaining_non_creator_approvals: int,
     *   manager_approved: bool,
     *   director_approved: bool,
     *   reservation_other_approved: bool,
     *   reservation_count: int,
     *   is_ready: bool,
     *   missing_labels: array<int, string>,
     *   latest_non_creator_approver_id: int|null,
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
            ->sort(function ($left, $right): int {
                $leftTimestamp = optional($left->approved_at)->getTimestamp() ?? 0;
                $rightTimestamp = optional($right->approved_at)->getTimestamp() ?? 0;
                $timestampComparison = $rightTimestamp <=> $leftTimestamp;
                if ($timestampComparison !== 0) {
                    return $timestampComparison;
                }

                return ((int) ($right->id ?? 0)) <=> ((int) ($left->id ?? 0));
            })
            ->values();
        $directorApproved = $directorApprovals->isNotEmpty();
        $directorApproverId = $directorApproved ? (int) ($directorApprovals->first()->user_id ?? 0) : null;

        $reservationApprovals = $approvals
            ->filter(fn ($a) => (string) $a->approval_role === 'reservation')
            ->filter(fn ($a) => (int) ($a->user_id ?? 0) !== (int) ($quotation->created_by ?? 0))
            ->values();
        $reservationCount = $reservationApprovals->count();
        $reservationOtherApproved = $reservationCount >= 1;

        $nonCreatorApprovals = $approvals
            ->filter(function ($a) use ($quotation): bool {
                if (! Schema::hasColumn('quotations', 'created_by')) {
                    return true;
                }

                return (int) ($a->user_id ?? 0) !== (int) ($quotation->created_by ?? 0);
            })
            ->sort(function ($left, $right): int {
                $leftTimestamp = optional($left->approved_at)->getTimestamp() ?? 0;
                $rightTimestamp = optional($right->approved_at)->getTimestamp() ?? 0;
                $timestampComparison = $rightTimestamp <=> $leftTimestamp;
                if ($timestampComparison !== 0) {
                    return $timestampComparison;
                }

                return ((int) ($right->id ?? 0)) <=> ((int) ($left->id ?? 0));
            })
            ->values();
        $requiredNonCreatorApprovals = 2;
        $nonCreatorApprovalCount = $nonCreatorApprovals->count();
        $remainingNonCreatorApprovals = max(0, $requiredNonCreatorApprovals - $nonCreatorApprovalCount);
        $latestNonCreatorApproverId = $nonCreatorApprovals->isNotEmpty()
            ? (int) ($nonCreatorApprovals->first()->user_id ?? 0)
            : null;

        $missing = [];
        if ($remainingNonCreatorApprovals > 0) {
            $missing[] = $remainingNonCreatorApprovals === 1
                ? '1 more non-creator approval'
                : $remainingNonCreatorApprovals . ' more non-creator approvals';
        }

        return [
            'required_non_creator_approvals' => $requiredNonCreatorApprovals,
            'non_creator_approval_count' => $nonCreatorApprovalCount,
            'remaining_non_creator_approvals' => $remainingNonCreatorApprovals,
            'manager_approved' => $managerApproved,
            'director_approved' => $directorApproved,
            'reservation_other_approved' => $reservationOtherApproved,
            'reservation_count' => $reservationCount,
            'is_ready' => empty($missing),
            'missing_labels' => $missing,
            'latest_non_creator_approver_id' => $latestNonCreatorApproverId ?: null,
            'director_approver_id' => $directorApproverId ?: null,
        ];
    }

    /**
     * @return array{
     *   required_non_creator_approvals: int,
     *   non_creator_approval_count: int,
     *   remaining_non_creator_approvals: int,
     *   manager_approved: bool,
     *   director_approved: bool,
     *   reservation_other_approved: bool,
     *   reservation_count: int,
     *   is_ready: bool,
     *   missing_labels: array<int, string>,
     *   latest_non_creator_approver_id: int|null,
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
                'approved_by' => $progress['latest_non_creator_approver_id'],
                'approved_at' => now(),
            ]);
            $this->autoFinalizeApprovedQuotationIfExpired($quotation);
        } else {
            $quotation->update([
                'status' => 'pending',
                'approved_by' => null,
                'approved_at' => null,
            ]);
        }
        $this->syncLinkedLifecycleStatusesForQuotation($quotation);

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
