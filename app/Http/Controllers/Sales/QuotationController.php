<?php

namespace App\Http\Controllers\Sales;

use PDF;
use App\Http\Controllers\Concerns\HandlesActivityTimelineAjax;
use App\Models\Activity;
use App\Models\FoodBeverage;
use App\Models\HotelRoom;
use App\Models\HotelPrice;
use App\Models\Inquiry;
use App\Models\IslandTransfer;
use App\Models\Itinerary;
use App\Models\Quotation;
use App\Models\QuotationCustomerResponse;
use App\Models\QuotationFollowUp;
use App\Models\QuotationItem;
use App\Models\Customer;
use App\Models\Destination;
use App\Models\TouristAttraction;
use App\Models\TransportUnit;
use App\Models\User;
use App\Services\ActivityAuditLogger;
use App\Services\ItineraryQuotationService;
use App\Services\InvoiceService;
use App\Services\ModuleService;
use App\Services\QuotationValidationService;
use App\Services\Quotation\QuotationFollowUpAutomationService;
use App\Services\Quotation\ItineraryQuotationRevisionOrchestrator;
use App\Services\Quotation\QuotationStatusService;
use App\Services\Quotation\QuotationWorkflowService;
use App\Support\QuotationActionResolver;
use App\Support\SafeRichText;
use App\Support\Workflow\QuotationWorkflow;
use App\Support\Workflow\QuotationStatusNormalizer;
use App\Support\ImageThumbnailGenerator;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
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
        private readonly QuotationValidationService $quotationValidationService,
        private readonly QuotationWorkflowService $quotationWorkflowService,
        private readonly ItineraryQuotationRevisionOrchestrator $itineraryQuotationRevisionOrchestrator,
        private readonly QuotationFollowUpAutomationService $quotationFollowUpAutomationService,
        private readonly QuotationActionResolver $quotationActionResolver,
        private readonly QuotationStatusService $quotationStatusService
    )
    {
    }

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $this->autoFinalizeExpiredApprovedQuotations();

        $query = Quotation::query()->with([
            'itinerary:id,title,destination,inquiry_id',
            'inquiry.customer',
            'inquiry.handledBy:id,name',
            'creator',
            'approvals:id,quotation_id,user_id,approval_role',
            'items:id,quotation_id,qty,unit_price,contract_rate,markup_type,markup,discount,discount_type,itinerary_item_type',
        ]);
        $this->applyQuotationKeywordFilter($query, (string) request('q'));
        $this->applyQuotationStatusFilter($query, (string) request('status'));
        $statsCards = $this->buildQuotationStatsCards(null, null, false);
        $quotationLogs = ActivityLog::query()
            ->with('user:id,name')
            ->where('subject_type', (new Quotation())->getMorphClass())
            ->latest('id')
            ->limit(10)
            ->get();

        return view('modules.quotations.index', array_merge(
            $this->buildQuotationIndexViewData(
                $query,
                $statsCards,
                false,
                'quotations.index',
                'all',
                Quotation::STATUS_OPTIONS
            ),
            [
            'quotationLogs' => $quotationLogs,
            ]
        ));
    }

    public function myQuotations()
    {
        $this->autoFinalizeExpiredApprovedQuotations();

        $query = Quotation::query()->withTrashed()->with([
            'itinerary:id,title,destination,inquiry_id',
            'inquiry.customer',
            'creator',
            'approvals:id,quotation_id,user_id,approval_role',
            'items:id,quotation_id,qty,unit_price,contract_rate,markup_type,markup,discount,discount_type,itinerary_item_type',
        ]);
        if (Schema::hasColumn('quotations', 'created_by')) {
            $query->where('created_by', (int) auth()->id());
        } else {
            $query->whereRaw('1 = 0');
        }
        $this->applyQuotationKeywordFilter($query, (string) request('q'));
        $this->applyQuotationStatusFilter($query, (string) request('status'));
        $quotationLogs = ActivityLog::query()
            ->with('user:id,name')
            ->where('subject_type', (new Quotation())->getMorphClass())
            ->latest('id')
            ->limit(10)
            ->get();
        $statsCards = $this->buildQuotationStatsCards((int) auth()->id(), null, true);

        return view('modules.quotations.index', array_merge(
            $this->buildQuotationIndexViewData(
                $query,
                $statsCards,
                true,
                'quotations.my',
                'my',
                Quotation::STATUS_OPTIONS
            ),
            [
            'quotationLogs' => $quotationLogs,
            ]
        ));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $prefillItineraryId = request()->integer('itinerary_id') ?: null;
        $prefillInquiryId = request()->integer('inquiry_id') ?: null;
        $itineraries = $this->availableItinerariesQuery()
            ->orderBy('title')
            ->orderBy('id')
            ->get(['id', 'title', 'destination_id', 'destination', 'duration_days', 'duration_nights', 'is_active', 'status']);
        $destinations = Destination::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name']);
        $this->attachItineraryInquiryReferences($itineraries);
        $itineraryInquiryMap = $this->buildItineraryInquiryMap($itineraries);
        $inquiries = $this->availableInquiriesQuery($prefillInquiryId)
            ->orderByDesc('id')
            ->get(['id', 'inquiry_number', 'customer_id', 'status', 'priority', 'source', 'deadline', 'notes']);
        $customers = Customer::query()
            ->orderBy('name')
            ->orderBy('company_name')
            ->get(['id', 'name', 'company_name', 'email', 'phone']);
        $serviceCatalogs = $this->buildServiceItemCatalogs();

        if ($prefillItineraryId && ! $itineraries->firstWhere('id', $prefillItineraryId)) {
            $prefillItineraryId = null;
        }
        if ($prefillInquiryId && ! $inquiries->firstWhere('id', $prefillInquiryId)) {
            $prefillInquiryId = null;
        }

        return view('modules.quotations.create', compact('itineraries', 'inquiries', 'customers', 'destinations', 'prefillItineraryId', 'prefillInquiryId', 'itineraryInquiryMap', 'serviceCatalogs'));
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
                'nullable',
                'integer',
                Rule::exists('itineraries', 'id')->where(function ($query): void {
                    $query->where('is_active', true);
                }),
            ],
            'customer_id' => ['required', 'integer', 'exists:customers,id'],
            'inquiry_id' => ['required', 'integer', 'exists:inquiries,id'],
            'order_number' => ['required', 'string', 'max:100', 'regex:/^[A-Za-z0-9]+$/'],
            'service_date' => ['required', 'date'],
            'duration_days' => ['required', 'integer', 'min:1'],
            'duration_nights' => ['nullable', 'integer', 'min:0'],
            'pax_adult' => ['required', 'integer', 'min:0'],
            'pax_child' => ['nullable', 'integer', 'min:0'],
            'validity_date' => ['required', 'date'],
            'discount_type' => ['nullable', Rule::in(['percent', 'fixed'])],
            'discount_value' => ['nullable', 'numeric', 'min:0'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.id' => ['nullable', 'integer', 'min:1'],
            'items.*.description' => ['required', 'string', 'max:255'],
            'items.*.qty' => ['required', 'integer', 'min:1'],
            'items.*.contract_rate' => ['nullable', 'numeric', 'min:0'],
            'items.*.rate' => ['nullable', 'numeric', 'min:0'],
            'items.*.markup_type' => ['nullable', Rule::in(['percent', 'fixed'])],
            'items.*.markup' => ['nullable', 'numeric', 'min:0'],
            'items.*.unit_price' => ['required', 'numeric', 'min:0'],
            'items.*.discount_type' => ['nullable', Rule::in(['percent', 'fixed'])],
            'items.*.discount' => ['nullable', 'numeric', 'min:0'],
            'items.*.source_item_id' => ['nullable', 'integer', 'min:1'],
            'items.*.serviceable_type' => ['nullable', Rule::in($this->serviceableTypes())],
            'items.*.serviceable_id' => ['nullable', 'integer', 'min:1'],
            'items.*.day_number' => ['nullable', 'integer', 'min:1'],
            'items.*.sort_order' => ['nullable', 'integer', 'min:0'],
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
        if (((int) ($validated['pax_adult'] ?? 0) + (int) ($validated['pax_child'] ?? 0)) <= 0) {
            throw ValidationException::withMessages([
                'pax_adult' => 'Pax adult and child cannot both be zero.',
            ]);
        }

        $selectedItineraryId = (int) ($validated['itinerary_id'] ?? 0);
        $selectedItineraryId = $selectedItineraryId > 0 ? $selectedItineraryId : null;
        $selectedCustomerId = (int) ($validated['customer_id'] ?? 0);
        $normalizedOrderNumber = $this->normalizeOrderNumber($validated['order_number'] ?? null);
        $selectedInquiryId = (int) ($validated['inquiry_id'] ?? 0);
        $this->assertInquiryEligibleForQuotationGeneration($selectedInquiryId);
        $inquiryId = $this->resolveInquiryIdForQuotation($selectedCustomerId, $selectedInquiryId);
        $this->assertAndClaimInquiryHandler($inquiryId);
        if (! $this->canApplyGlobalDiscount()) {
            $validated['discount_type'] = null;
            $validated['discount_value'] = 0;
        } else {
            $this->assertPricingPermission($validated);
        }

        $validated['quotation_number'] = $this->generateQuotationNumber();
        $creator = auth()->user();

        DB::beginTransaction();
        try {
            $validated['items'] = $this->syncMissingServicePublishRatesFromQuotationItems($validated['items']);
            $totals = $this->computeTotals(
                $validated['items'],
                $validated['discount_type'] ?? null,
                (float) ($validated['discount_value'] ?? 0),
                (string) ($validated['service_date'] ?? '')
            );

            $quotation = Quotation::withoutActivityLogging(function () use ($validated, $inquiryId, $selectedItineraryId, $totals, $normalizedOrderNumber) {
                return Quotation::query()->create([
                    'quotation_number' => $validated['quotation_number'],
                    'order_number' => $normalizedOrderNumber,
                    'inquiry_id' => $inquiryId,
                    'itinerary_id' => $selectedItineraryId,
                    'service_date' => $validated['service_date'],
                    'pax_adult' => (int) ($validated['pax_adult'] ?? 0),
                    'pax_child' => (int) ($validated['pax_child'] ?? 0),
                    'status' => Quotation::STATUS_NEED_VALIDATION,
                    'validity_date' => $validated['validity_date'],
                    'sub_total' => $totals['sub_total'],
                    'discount_type' => $validated['discount_type'] ?? null,
                    'discount_value' => (float) ($validated['discount_value'] ?? 0),
                    'final_amount' => $totals['final_amount'],
                    'approved_by' => null,
                    'approved_at' => null,
                ]);
            });
            foreach ($totals['items'] as $item) {
                $quotation->items()->create($item);
            }
            $this->syncInquiryItineraryReferenceFromQuotation($quotation);
            $this->quotationValidationService->syncValidationRequirementsAndMasterRates($quotation);
            if ($this->quotationUsesApprovalBypass($quotation)) {
                $quotation->approvals()->delete();
                $this->applyPrivilegedCreatorApprovalStatus($quotation);
            }
            $this->syncLinkedLifecycleStatusesForQuotation($quotation);

            $quotation->load('items');
            $this->activityAuditLogger->logCreated($quotation, $this->buildQuotationAuditSnapshot($quotation), 'Quotation');

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            report($e);
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
        $quotation->setAttribute('status', Quotation::normalizeStatus((string) ($quotation->status ?? '')));
        $quotation->load([
            'inquiry.customer',
            'inquiry.handledBy:id,name',
            'inquiry.assignedTo:id,name',
            'itinerary.creator',
            'itinerary.inquiry.customer',
            'itinerary.dayPoints:id,itinerary_id,day_number,break_start_time,break_end_time',
            'items',
            'activities.user',
            'booking.items',
            'booking.invoices.payments',
            'approvedBy',
            'approvalNoteBy',
            'validatedBy',
            'creator',
            'updater',
            'revisionOf',
            'revisions',
            'followUps.creator',
            'followUps.handler',
            'customerResponses.creator',
        ]);
        $this->applyQuotationServiceDescriptions($quotation);
        $this->quotationValidationService->syncValidationRequirements($quotation);
        $quotation->load([
            'followUps.creator',
            'followUps.handler',
            'customerResponses.creator',
        ]);
        $validationProgress = $this->quotationValidationService->getProgress($quotation);
        $kpiSummary = $this->computeQuotationKpiSummary($quotation);
        $groupedItemsByDay = $this->buildGroupedQuotationItemsByDay($quotation);
        $canValidateQuotation = $this->quotationValidationService->isValidationActor($request->user())
            && ! $quotation->isStatus(Quotation::STATUS_SENT, Quotation::STATUS_CUSTOMER_APPROVED, Quotation::FINAL_STATUS, Quotation::STATUS_IN_OPERATION, Quotation::STATUS_COMPLETED)
            && (bool) ($validationProgress['requires_validation'] ?? false);

        $activities = $quotation->activities()
            ->with('user:id,name')
            ->latest()
            ->paginate(5, ['*'], 'activity_page')
            ->withQueryString();

        if ($this->wantsActivityTimelineFragment($request)) {
            return $this->activityTimelineFragmentResponse($activities);
        }

        $revisionHistory = $this->buildRevisionHistory($quotation);
        $followUpHistory = $this->buildFollowUpHistory($quotation);
        $bookingsModuleEnabled = ModuleService::isEnabledStatic('bookings');
        $invoicesModuleEnabled = ModuleService::isEnabledStatic('invoices');
        $workflowOverview = $this->buildQuotationWorkflowOverview(
            $quotation,
            $validationProgress,
            $bookingsModuleEnabled,
            $invoicesModuleEnabled
        );
        $availableActions = $this->quotationActionResolver->availableActions($quotation, $request->user());

        return view('modules.quotations.show', compact('quotation', 'validationProgress', 'canValidateQuotation', 'activities', 'kpiSummary', 'groupedItemsByDay', 'revisionHistory', 'followUpHistory', 'workflowOverview', 'availableActions', 'bookingsModuleEnabled', 'invoicesModuleEnabled'));
    }


    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Request $request, string $id)
    {
        $quotation = Quotation::query()->findOrFail($id);
        $this->autoFinalizeApprovedQuotationIfExpired($quotation);
        $quotation->setAttribute('status', Quotation::normalizeStatus((string) ($quotation->status ?? '')));
        $isRevisionMode = $this->isQuotationRevisionModeRequest($request, $quotation);
        if ($quotation->isFinal()) {
            return redirect()
                ->route('quotations.show', $quotation)
                ->with('error', 'Final quotation cannot be edited.');
        }
        if (! $this->canManageQuotation($quotation, 'update')) {
            return $this->denyQuotationMutation($quotation);
        }
        $quotation->load([
            'items',
            'inquiry.customer',
            'inquiry.creator',
            'itinerary.inquiry.customer',
            'itinerary.inquiry.creator',
            'itinerary.creator',
            'approvedBy',
            'approvalNoteBy',
            'approvals.user',
            'pendingRevisionCustomerResponses.creator',
        ]);
        $this->applyQuotationServiceDescriptions($quotation);
        $this->quotationValidationService->syncValidationRequirements($quotation);
        $quotation->load('pendingRevisionCustomerResponses.creator');

        $itineraries = $this->availableItinerariesQuery((int) ($quotation->itinerary_id ?? 0))
            ->orderBy('title')
            ->orderBy('id')
            ->get(['id', 'title', 'destination_id', 'destination', 'duration_days', 'duration_nights', 'is_active', 'status']);
        $destinations = Destination::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name']);
        $inquiries = $this->availableInquiriesQuery((int) ($quotation->inquiry_id ?? 0), (int) $quotation->id)
            ->orderByDesc('id')
            ->get(['id', 'inquiry_number', 'customer_id', 'status', 'priority', 'source', 'deadline', 'notes']);
        $customers = Customer::query()
            ->orderBy('name')
            ->orderBy('company_name')
            ->get(['id', 'name', 'company_name', 'email', 'phone']);
        $serviceCatalogs = $this->buildServiceItemCatalogs();
        $this->attachItineraryInquiryReferences($itineraries);
        $itineraryInquiryMap = $this->buildItineraryInquiryMap($itineraries);
        $approvalProgress = $this->buildApprovalProgress($quotation);
        $validationProgress = $this->quotationValidationService->getProgress($quotation);
        $canValidateQuotation = $this->quotationValidationService->isValidationActor($request->user())
            && ! $quotation->isStatus(Quotation::STATUS_SENT, Quotation::STATUS_CUSTOMER_APPROVED, Quotation::FINAL_STATUS, Quotation::STATUS_IN_OPERATION, Quotation::STATUS_COMPLETED)
            && (bool) ($validationProgress['requires_validation'] ?? false);
        $activities = $quotation->activities()
            ->with('user:id,name')
            ->latest()
            ->paginate(5, ['*'], 'activity_page')
            ->withQueryString();

        if ($this->wantsActivityTimelineFragment($request)) {
            return $this->activityTimelineFragmentResponse($activities);
        }

        $customerRequestedChanges = $quotation->pendingRevisionCustomerResponses;

        return view('modules.quotations.edit', compact('quotation', 'itineraries', 'inquiries', 'customers', 'destinations', 'approvalProgress', 'validationProgress', 'canValidateQuotation', 'activities', 'itineraryInquiryMap', 'serviceCatalogs', 'customerRequestedChanges', 'isRevisionMode'));
    }

    public function startItineraryRevision(Request $request, Quotation $quotation)
    {
        $this->autoFinalizeApprovedQuotationIfExpired($quotation);
        if ($quotation->isFinal()) {
            return redirect()
                ->route('quotations.show', $quotation)
                ->with('error', 'Final quotation cannot be revised.');
        }
        if (! $this->canManageQuotation($quotation, 'update')) {
            return $this->denyQuotationMutation($quotation);
        }

        try {
            $itineraryRevision = $this->itineraryQuotationRevisionOrchestrator->startRevisionFromQuotation(
                $quotation->fresh(),
                [
                    'status' => 'revised',
                    'revision_reason' => 'quotation_revision',
                ],
                (int) ($request->user()?->id ?? 0)
            );
        } catch (\Throwable $exception) {
            report($exception);

            return redirect()
                ->route('quotations.show', $quotation)
                ->with('error', 'Failed to start itinerary revision. Please try again.');
        }

        return redirect()->route('itineraries.edit', [
            'itinerary' => $itineraryRevision->id,
            'quotation_id' => (int) $quotation->id,
            'return_to_quotation_revise' => 1,
            'revision_mode' => 1,
        ])->with('info', ui_phrase('Revise itinerary first, then save to continue quotation revision.'));
    }

    private function attachItineraryInquiryReferences($itineraries): void
    {
        $itineraries->each(function (Itinerary $itinerary): void {
            $inquiry = $itinerary->inquiryReferences->first();
            if (! $inquiry && (int) ($itinerary->inquiry_id ?? 0) > 0) {
                $inquiry = Inquiry::query()
                    ->with(['customer:id,name,company_name', 'creator:id,name'])
                    ->find((int) $itinerary->inquiry_id);
            }

            $itinerary->setAttribute('reference_inquiry_id', $inquiry?->id ? (int) $inquiry->id : null);
            $itinerary->setAttribute('reference_customer_id', $inquiry?->customer_id ? (int) $inquiry->customer_id : null);
            $itinerary->setAttribute('reference_inquiry_number', (string) ($inquiry?->inquiry_number ?? ''));
            $itinerary->setAttribute('reference_inquiry', $inquiry);
        });
    }

    private function buildItineraryInquiryMap($itineraries): array
    {
        return $itineraries->mapWithKeys(function ($itinerary): array {
            $inquiry = $itinerary->getAttribute('reference_inquiry') ?: $itinerary->inquiryReferences->first();
            if (! $inquiry) {
                return [];
            }

            return [
                (string) $itinerary->id => [
                    'inquiry_number' => (string) ($inquiry?->inquiry_number ?? '-'),
                    'customer_name' => (string) ($inquiry?->customer?->name ?? '-'),
                    'status' => (string) ($inquiry?->status ?? '-'),
                    'priority' => (string) ($inquiry?->priority ?? '-'),
                    'source' => (string) ($inquiry?->source ?? '-'),
                    'creator_name' => ui_user_name($inquiry?->creator),
                    'deadline' => optional($inquiry?->deadline)->format('Y-m-d') ?? '-',
                    'notes' => trim((string) ($inquiry?->notes ?? '')) !== '' ? (string) ($inquiry?->notes ?? '') : '-',
                    'notes_html' => \App\Support\SafeRichText::sanitize((string) ($inquiry?->notes ?? '')),
                ],
            ];
        })->all();
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
        $mustCreateRevision = $quotation->isLockedForDirectEdit();
        $wasApprovedBeforeUpdate = $quotation->isStatus(Quotation::STATUS_CUSTOMER_APPROVED);
        $isRevisionUpdate = $this->isQuotationRevisionModeRequest($request, $quotation);

        $items = collect($request->input('items', []))
            ->filter(fn ($row) => trim((string) ($row['description'] ?? '')) !== '')
            ->values()
            ->all();
        $request->merge(['items' => $items]);

        $validated = $request->validate([
            'itinerary_id' => [
                'nullable',
                'integer',
                Rule::exists('itineraries', 'id')->where(function ($query): void {
                    $query->where('is_active', true);
                }),
            ],
            'customer_id' => ['required', 'integer', 'exists:customers,id'],
            'inquiry_id' => ['required', 'integer', 'exists:inquiries,id'],
            'order_number' => ['required', 'string', 'max:100', 'regex:/^[A-Za-z0-9]+$/'],
            'service_date' => ['required', 'date'],
            'duration_days' => ['required', 'integer', 'min:1'],
            'duration_nights' => ['nullable', 'integer', 'min:0'],
            'pax_adult' => ['required', 'integer', 'min:0'],
            'pax_child' => ['nullable', 'integer', 'min:0'],
            'validity_date' => ['required', 'date'],
            'discount_type' => ['nullable', Rule::in(['percent', 'fixed'])],
            'discount_value' => ['nullable', 'numeric', 'min:0'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.id' => ['nullable', 'integer', 'min:1'],
            'items.*.description' => ['required', 'string', 'max:255'],
            'items.*.qty' => ['required', 'integer', 'min:1'],
            'items.*.contract_rate' => ['nullable', 'numeric', 'min:0'],
            'items.*.rate' => ['nullable', 'numeric', 'min:0'],
            'items.*.markup_type' => ['nullable', Rule::in(['percent', 'fixed'])],
            'items.*.markup' => ['nullable', 'numeric', 'min:0'],
            'items.*.unit_price' => ['required', 'numeric', 'min:0'],
            'items.*.discount_type' => ['nullable', Rule::in(['percent', 'fixed'])],
            'items.*.discount' => ['nullable', 'numeric', 'min:0'],
            'items.*.source_item_id' => ['nullable', 'integer', 'min:1'],
            'items.*.serviceable_type' => ['nullable', Rule::in($this->serviceableTypes())],
            'items.*.serviceable_id' => ['nullable', 'integer', 'min:1'],
            'items.*.day_number' => ['nullable', 'integer', 'min:1'],
            'items.*.sort_order' => ['nullable', 'integer', 'min:0'],
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
        if (((int) ($validated['pax_adult'] ?? 0) + (int) ($validated['pax_child'] ?? 0)) <= 0) {
            throw ValidationException::withMessages([
                'pax_adult' => 'Pax adult and child cannot both be zero.',
            ]);
        }
        $selectedItineraryId = (int) ($validated['itinerary_id'] ?? 0);
        $selectedItineraryId = $selectedItineraryId > 0 ? $selectedItineraryId : null;
        $selectedCustomerId = (int) ($validated['customer_id'] ?? 0);
        $normalizedOrderNumber = $this->normalizeOrderNumber($validated['order_number'] ?? null);
        $existingInquiryId = (int) ($quotation->inquiry_id ?? 0);
        $selectedInquiryId = (int) ($validated['inquiry_id'] ?? $existingInquiryId);
        if ($existingInquiryId <= 0 || $selectedInquiryId !== $existingInquiryId) {
            throw ValidationException::withMessages([
                'inquiry_id' => ui_phrase('Inquiry cannot be changed after quotation is created.'),
            ]);
        }
        $inquiryId = $existingInquiryId;
        $validated['inquiry_id'] = $inquiryId;
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
            $validatedItemStateById = $this->buildValidatedQuotationItemStateMap($quotation);
            $validated['items'] = $this->syncMissingServicePublishRatesFromQuotationItems($validated['items']);
            $totals = $this->computeTotals(
                $validated['items'],
                $validated['discount_type'] ?? null,
                (float) ($validated['discount_value'] ?? 0),
                (string) ($validated['service_date'] ?? '')
            );
            $computedItems = $this->applyValidatedItemStateCarryOver(
                $totals['items'] ?? [],
                $validated['items'] ?? [],
                $validatedItemStateById
            );
            $previousItineraryId = (int) ($quotation->itinerary_id ?? 0);

            if ($mustCreateRevision) {
                DB::rollBack();

                return redirect()
                    ->route('quotations.show', $quotation)
                    ->with('error', 'Use Start Revision before editing sent, approved, or locked quotation.');
            }

            {
                $quotation->loadMissing('items');
                $beforeAudit = $this->buildQuotationAuditSnapshot($quotation);

                Quotation::withoutActivityLogging(function () use ($quotation, $inquiryId, $selectedItineraryId, $validated, $totals, $normalizedOrderNumber): void {
                    $quotation->update([
                        'order_number' => $normalizedOrderNumber,
                        'inquiry_id' => $inquiryId,
                        'itinerary_id' => $selectedItineraryId,
                        'service_date' => $validated['service_date'],
                        'pax_adult' => (int) ($validated['pax_adult'] ?? 0),
                        'pax_child' => (int) ($validated['pax_child'] ?? 0),
                        'validity_date' => $validated['validity_date'],
                        'sub_total' => $totals['sub_total'],
                        'discount_type' => $validated['discount_type'] ?? null,
                        'discount_value' => (float) ($validated['discount_value'] ?? 0),
                        'final_amount' => $totals['final_amount'],
                    ]);
                });
                $quotation->items()->delete();
                foreach ($computedItems as $item) {
                    $quotation->items()->create($item);
                }
                $this->syncInquiryItineraryReferenceFromQuotation($quotation);
                $this->quotationValidationService->syncValidationRequirementsAndMasterRates($quotation);
                $this->syncQuotationStatusWithValidationProgress($quotation, $isRevisionUpdate, (int) ($request->user()?->id ?? 0));
                $skipApprovalWorkflow = $this->quotationUsesApprovalBypass($quotation);
                if ($skipApprovalWorkflow) {
                    $quotation->approvals()->delete();
                    $this->applyPrivilegedCreatorApprovalStatus($quotation);
                } elseif ($wasApprovedBeforeUpdate) {
                    $quotation->approvals()->delete();
                    Quotation::withoutActivityLogging(function () use ($quotation): void {
                        $quotation->update([
                            'status' => Quotation::STATUS_NEED_VALIDATION,
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
            }

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            report($e);
            return back()
                ->withInput()
                ->with('error', 'Failed to save quotation. Please check the data.');
        }

        return redirect()
            ->route('quotations.show', $quotation)
            ->with('success', $isRevisionUpdate ? 'Quotation revision saved. Review validation status before sending again.' : ($wasApprovedBeforeUpdate ? 'Quotation updated. Status changed to pending for re-approval.' : 'Quotation updated successfully.'));
    }

    private function isQuotationRevisionModeRequest(Request $request, Quotation $quotation): bool
    {
        $status = Quotation::normalizeStatus((string) ($quotation->status ?? ''));

        return $request->routeIs('quotations.revise')
            || $request->boolean('revision_mode')
            || in_array($status, [Quotation::STATUS_REVISION_REQUESTED, Quotation::STATUS_UNDER_REVISION, Quotation::STATUS_NEED_REVALIDATION, Quotation::STATUS_PENDING_REVALIDATION, 'booking_issue'], true)
            || (string) ($quotation->approval_status ?? '') === 'revision_requested';
    }

    private function finalizeQuotationRevisionSave(Quotation $quotation, ?int $actorId = null): void
    {
        $this->syncQuotationStatusWithValidationProgress($quotation, true, $actorId);
    }

    private function syncQuotationStatusWithValidationProgress(Quotation $quotation, bool $isRevisionUpdate, ?int $actorId = null): void
    {
        $quotation->refresh();
        $currentStatus = Quotation::normalizeStatus((string) ($quotation->status ?? ''));
        if (in_array($currentStatus, [
            Quotation::STATUS_SENT,
            Quotation::STATUS_CUSTOMER_APPROVED,
            Quotation::STATUS_BOOKING_CREATED,
            Quotation::STATUS_IN_OPERATION,
            Quotation::STATUS_COMPLETED,
            Quotation::STATUS_CANCELLED,
            Quotation::STATUS_LOST,
        ], true)) {
            return;
        }

        $this->quotationStatusService->syncStatus($quotation);
        $quotation->refresh();
        $progress = $this->quotationStatusService->validationProgress($quotation);
        $this->quotationWorkflowService->syncDimensions($quotation, $actorId ?: null, [
            'action' => $isRevisionUpdate ? 'save_quotation_revision' : 'sync_validation_progress_status',
            'validation_status' => (string) ($quotation->validation_status ?? ''),
            'total_required' => (int) ($progress['required'] ?? 0),
            'total_validated' => (int) ($progress['validated'] ?? 0),
            'validation_progress' => (int) ($progress['percent'] ?? 0),
        ]);
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
        if ($quotation->isStatus(Quotation::STATUS_CUSTOMER_APPROVED)) {
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
        abort_unless(auth()->user()?->canManageActivationActions(), 403);
        $quotation = Quotation::withTrashed()->findOrFail($quotation);
        $this->autoFinalizeApprovedQuotationIfExpired($quotation);
        if ($quotation->isFinal()) {
            return redirect()
                ->route('quotations.show', $quotation)
                ->with('error', 'Final quotation cannot be changed.');
        }
        if ($quotation->isStatus(Quotation::STATUS_CUSTOMER_APPROVED)) {
            return redirect()
                ->route('quotations.show', $quotation)
                ->with('error', 'Approved quotation cannot be deactivated.');
        }
        if (! $this->canManageQuotation($quotation, 'delete')) {
            return $this->denyQuotationMutation($quotation);
        }

        if ($quotation->trashed()) {
            $quotation->restore();
            $this->quotationWorkflowService->syncDimensions($quotation, (int) auth()->id(), ['action' => 'quotation_restored']);
            $this->syncLinkedLifecycleStatusesForQuotation($quotation);

            return redirect()
                ->back()
                ->with('success', 'Quotation activated successfully.');
        }

        $quotation->delete();
        $this->quotationWorkflowService->syncDimensions($quotation, (int) auth()->id(), ['action' => 'quotation_deactivated']);
        $this->syncLinkedLifecycleStatusesForQuotation($quotation);

        return redirect()
            ->back()
            ->with('success', 'Quotation deactivated successfully.');
    }

    public function generatePDF(Request $request, Quotation $quotation)
    {
        $pdfLocale = $this->normalizePdfLocale((string) $request->query('locale', app()->getLocale()));
        $previousLocale = app()->getLocale();
        app()->setLocale($pdfLocale);
        $pdfFontConfig = $this->resolvePdfFontConfig($pdfLocale);

        try {
        $this->autoFinalizeApprovedQuotationIfExpired($quotation);
        $canPreviewQuotationPdf = in_array((string) ($quotation->validation_status ?? ''), ['valid', 'validated'], true)
            || $quotation->isStatus(
                Quotation::STATUS_VALIDATED,
                'ready_to_send',
                Quotation::STATUS_SENT,
                Quotation::STATUS_CUSTOMER_APPROVED,
                Quotation::FINAL_STATUS
            );
        if (! $canPreviewQuotationPdf) {
            return redirect()
                ->route('quotations.show', $quotation)
                ->with('error', 'PDF hanya tersedia setelah quotation valid atau sudah dikirim.');
        }

        $quotation->load(['inquiry.customer', 'itinerary', 'items']);
        $this->applyQuotationServiceDescriptions($quotation);
        $this->applyQuotationPdfItemPresentation($quotation);
        $kpiSummary = $this->computeQuotationKpiSummary($quotation);
        $groupedItemsByDay = $this->buildGroupedQuotationItemsByDay($quotation);

        $pdf = PDF::loadView('pdf.quotation', [
            'quotation' => $quotation,
            'kpiSummary' => $kpiSummary,
            'groupedItemsByDay' => $groupedItemsByDay,
            'pdfFontFamilyCss' => $pdfFontConfig['family_css'],
            'pdfFontFaceCss' => $pdfFontConfig['font_face_css'],
            'pdfLocale' => $pdfLocale,
        ])->setPaper('a4', 'portrait');
        if (($pdfFontConfig['default_font'] ?? '') !== '') {
            $pdf->setOption(['defaultFont' => $pdfFontConfig['default_font']]);
        }
        $this->registerPdfFonts($pdf, $pdfFontConfig);

        return $pdf->stream('quotation-' . Str::slug((string) ($quotation->quotation_number ?: 'document')) . '.pdf');
        } finally {
            app()->setLocale($previousLocale);
        }
    }

    public function exportCsv()
    {
        $this->autoFinalizeExpiredApprovedQuotations();

        $query = Quotation::query()->withTrashed()->with(['inquiry.customer']);
        $scope = strtolower(trim((string) request('scope')));
        if ($scope === 'published') {
            $query->whereIn('status', [Quotation::STATUS_APPROVED, Quotation::STATUS_CUSTOMER_APPROVED, Quotation::FINAL_STATUS, Quotation::LEGACY_FINAL_STATUS, 'accepted', 'converted']);
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
            if (in_array($status, [Quotation::STATUS_APPROVED, Quotation::STATUS_CUSTOMER_APPROVED, Quotation::FINAL_STATUS, Quotation::LEGACY_FINAL_STATUS, 'accepted', 'converted'], true)) {
                $query->whereIn('status', $this->quotationStatusAliasesForQuery($status));
            }
        } elseif ($scope === 'my') {
            if ($status !== '' && in_array($status, Quotation::STATUS_OPTIONS, true)) {
                $query->whereIn('status', $this->quotationStatusAliasesForQuery($status));
            }
        } else {
            $query->when($status !== '', fn ($q) => $q->whereIn('status', $this->quotationStatusAliasesForQuery($status)));
        }
        $quotations = $query->latest()->get();

        return response()->streamDownload(function () use ($quotations) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, [
                'quotation_number',
                'order_number',
                'service_date',
                'pax_adult',
                'pax_child',
                'inquiry_number',
                'customer_name',
                'status',
                'validity_date',
                'final_amount',
            ]);
            foreach ($quotations as $quotation) {
                fputcsv($handle, [
                    $quotation->quotation_number,
                    $quotation->order_number ?? '',
                    optional($quotation->service_date)->format('Y-m-d'),
                    (int) ($quotation->pax_adult ?? 0),
                    (int) ($quotation->pax_child ?? 0),
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
            'itineraryIslandTransfers.islandTransfer:id,vendor_id,name,transfer_type,departure_point_name,arrival_point_name,duration_minutes,notes,gallery_images',
            'itineraryIslandTransfers.islandTransfer.vendor:id,name,location,city,province,latitude,longitude',
            'itineraryFoodBeverages.foodBeverage:id,vendor_id,name,service_type,duration_minutes,adult_publish_rate,child_publish_rate,publish_rate,meal_period,notes,menu_highlights,gallery_images',
            'itineraryFoodBeverages.foodBeverage.vendor:id,name,location,city,province,latitude,longitude',
            'itineraryTransportUnits.transportUnit:id,name,brand_model,seat_capacity,luggage_capacity,air_conditioned,with_driver,images',
            'itineraryTransportUnits.transportUnit.transport:id,name,transport_type',
            'dayPoints',
            'dayPoints.startAirport:id,name,location,city,province,cover',
            'dayPoints.startHotel:id,name,address,city,province',
            'dayPoints.startHotelRoom:id,hotels_id,rooms,view,cover',
            'dayPoints.endAirport:id,name,location,city,province,cover',
            'dayPoints.endHotel:id,name,address,city,province',
            'dayPoints.endHotelRoom:id,hotels_id,rooms,view,cover',
            'inquiry:id,inquiry_number,customer_id,status,priority,source,deadline,notes',
            'inquiry.customer:id,name,code',
        ]);

        $scheduleByDay = [];
        $dayPointByDay = $itinerary->dayPoints->keyBy(fn ($point) => (int) $point->day_number);
        $transportUnitsByDay = $itinerary->itineraryTransportUnits->groupBy(fn ($item) => (int) $item->day_number);
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
                        'thumbnail_data_uri' => $this->resolveAirportCoverDataUri($dayPoint->startAirport?->cover),
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
                    'thumbnail_data_uri' => $this->resolveAirportCoverDataUri($dayPoint->endAirport?->cover),
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
                })
                ->values()
                ->toBase();

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
                })
                ->values()
                ->toBase();

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
                        'publish_rate' => (float) ($item->foodBeverage->adult_publish_rate ?? $item->foodBeverage->publish_rate ?? 0),
                        'currency' => 'IDR',
                        'pax' => (int) ($item->pax ?? 0),
                        'start_time' => $item->start_time ? substr((string) $item->start_time, 0, 5) : '--:--',
                        'end_time' => $item->end_time ? substr((string) $item->end_time, 0, 5) : '--:--',
                        'travel_minutes_to_next' => $item->travel_minutes_to_next,
                        'visit_order' => (int) ($item->visit_order ?? 999999),
                    ];
                })
                ->values()
                ->toBase();

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
                        'thumbnail_data_uri' => $this->resolveGalleryImageDataUri($transfer->gallery_images ?? []),
                        'pax' => (int) ($item->pax ?? 0),
                        'start_time' => $item->start_time ? substr((string) $item->start_time, 0, 5) : '--:--',
                        'end_time' => $item->end_time ? substr((string) $item->end_time, 0, 5) : '--:--',
                        'travel_minutes_to_next' => $item->travel_minutes_to_next,
                        'visit_order' => (int) ($item->visit_order ?? 999999),
                    ];
                })
                ->values()
                ->toBase();

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
            $breakStartTime = $dayPoint && ! empty($dayPoint->break_start_time)
                ? substr((string) $dayPoint->break_start_time, 0, 5)
                : null;
            $breakEndTime = $dayPoint && ! empty($dayPoint->break_end_time)
                ? substr((string) $dayPoint->break_end_time, 0, 5)
                : null;
            $dayTransportItems = $transportUnitsByDay->get($day, collect());
            $dayTransports = $dayTransportItems
                ->map(function ($transportItem) {
                    $dayTransportUnit = $transportItem?->transportUnit;
                    $transportMaster = $dayTransportUnit?->transport;

                    return [
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
                        'thumbnail_data_uri' => $dayTransportUnit
                            ? $this->resolveGalleryImageDataUri($dayTransportUnit->images ?? [])
                            : null,
                    ];
                })
                ->values()
                ->all();
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
                'break_start_time' => $breakStartTime,
                'break_end_time' => $breakEndTime,
                'start_travel_minutes' => $startTravelMinutes,
                'start_point_type_label' => $startPoint['label'] ?? ($startPoint['type'] ?? 'Unknown'),
                'end_point_type_label' => $endPoint['label'] ?? ($endPoint['type'] ?? 'Unknown'),
                'transport_units' => $dayTransports,
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

    private function resolveAirportCoverDataUri(?string $coverPath): ?string
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
            $candidates[] = 'airports/covers/' . $rawPath;
            $candidates[] = 'airports/cover/' . $rawPath;
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

    private function normalizePdfLocale(string $locale): string
    {
        $normalized = str_replace('-', '_', trim($locale));
        $supported = array_keys((array) config('app.supported_locales', []));
        if (in_array($normalized, $supported, true)) {
            return $normalized;
        }

        return (string) config('app.locale', 'en');
    }

    /**
     * @return array{family_css:string,font_face_css:string,default_font:string,font_path:string|null,font_url:string|null}
     */
    private function resolvePdfFontConfig(string $locale): array
    {
        $fallback = [
            'family_css' => "'DejaVu Sans', Arial, sans-serif",
            'font_face_css' => '',
            'default_font' => 'DejaVu Sans',
            'font_path' => null,
            'font_url' => null,
        ];

        if (! in_array($locale, ['zh_Hant', 'zh_Hans'], true)) {
            return $fallback;
        }

        $fontPath = $this->resolvePdfCjkFontPath($locale);
        if (! $fontPath) {
            return $fallback;
        }

        $extension = strtolower((string) pathinfo($fontPath, PATHINFO_EXTENSION));
        $format = $extension === 'otf' ? 'opentype' : 'truetype';
        $fontUrl = $this->toFileUrl($fontPath);

        return [
            'family_css' => "'VoyexPdfCjk', 'DejaVu Sans', Arial, sans-serif",
            'font_face_css' => "@font-face { font-family: 'VoyexPdfCjk'; font-style: normal; font-weight: 400; src: url('{$fontUrl}') format('{$format}'); } @font-face { font-family: 'VoyexPdfCjk'; font-style: normal; font-weight: 700; src: url('{$fontUrl}') format('{$format}'); }",
            'default_font' => 'VoyexPdfCjk',
            'font_path' => $fontPath,
            'font_url' => $fontUrl,
        ];
    }

    /**
     * @param  array{default_font:string,font_path:string|null,font_url:string|null}  $pdfFontConfig
     */
    private function registerPdfFonts($pdf, array $pdfFontConfig): void
    {
        $fontPath = (string) ($pdfFontConfig['font_path'] ?? '');
        if ($fontPath === '' || ! File::exists($fontPath)) {
            return;
        }

        $fontDirectory = storage_path('fonts');
        if (! File::isDirectory($fontDirectory)) {
            File::makeDirectory($fontDirectory, 0755, true);
        }

        $fontUrl = (string) ($pdfFontConfig['font_url'] ?? '');
        if ($fontUrl === '') {
            $fontUrl = $this->toFileUrl($fontPath);
        }

        $fontFamily = (string) ($pdfFontConfig['default_font'] ?? 'VoyexPdfCjk');
        if ($fontFamily === '') {
            $fontFamily = 'VoyexPdfCjk';
        }

        $fontMetrics = $pdf->getDomPDF()->getFontMetrics();
        foreach ([400, 700] as $weight) {
            $fontMetrics->registerFont([
                'family' => $fontFamily,
                'weight' => $weight,
                'style' => 'normal',
            ], $fontUrl);
        }
    }

    private function resolvePdfCjkFontPath(string $locale): ?string
    {
        $byLocale = [
            'zh_Hant' => [
                'NotoSansTC-Regular.ttf',
                'NotoSansTC-VariableFont_wght.ttf',
                'NotoSerifTC-Regular.ttf',
                'SourceHanSansTC-Regular.otf',
                'SourceHanSerifTC-Regular.otf',
                'msjh.ttc',
                'mingliub.ttc',
            ],
            'zh_Hans' => [
                'NotoSansSC-Regular.ttf',
                'NotoSansSC-VariableFont_wght.ttf',
                'NotoSerifSC-Regular.ttf',
                'SourceHanSansSC-Regular.otf',
                'SourceHanSerifSC-Regular.otf',
                'msyh.ttc',
                'simsun.ttc',
            ],
        ];
        $fileNames = $byLocale[$locale] ?? [];
        if ($fileNames === []) {
            return null;
        }

        $basePaths = [
            resource_path('fonts/cjk'),
            storage_path('fonts/cjk'),
            storage_path('fonts'),
            public_path('fonts/cjk'),
            public_path('fonts'),
            'C:\Windows\Fonts',
        ];

        foreach ($fileNames as $fileName) {
            foreach ($basePaths as $basePath) {
                $fullPath = rtrim($basePath, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $fileName;
                if (File::exists($fullPath)) {
                    return $fullPath;
                }
            }
        }

        return null;
    }

    private function toFileUrl(string $path): string
    {
        $normalized = str_replace('\\', '/', $path);
        if (preg_match('/^[A-Za-z]:\//', $normalized) === 1) {
            return 'file://' . $normalized;
        }

        return 'file://' . $normalized;
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
        return $this->markAsCustomerApproved($request, $quotation);
    }

    public function markAsSent(Request $request, Quotation $quotation)
    {
        $this->autoFinalizeApprovedQuotationIfExpired($quotation);
        if (! $this->canManageQuotation($quotation, 'update')) {
            return $this->denyQuotationMutation($quotation);
        }

        $this->quotationStatusService->syncStatus($quotation);
        $quotation->refresh();
        if (! QuotationStatusNormalizer::isReadyToSend((string) ($quotation->status ?? ''))) {
            return redirect()
                ->route('quotations.show', $quotation)
                ->with('error', 'Quotation cannot be marked as ready to send because validation is not 100% complete.');
        }

        if (! $this->quotationWorkflowService->canTransition($quotation, Quotation::STATUS_SENT)) {
            return redirect()->route('quotations.show', $quotation)->with('error', 'Quotation cannot be marked as sent from current status.');
        }

        $this->quotationWorkflowService->transition(
            $quotation,
            Quotation::STATUS_SENT,
            (int) ($request->user()?->id ?? 0) ?: null,
            'mark_sent'
        );
        $this->quotationFollowUpAutomationService->ensureInitialFollowUpNotification($quotation);
        $this->syncLinkedLifecycleStatusesForQuotation($quotation);

        return redirect()->route('quotations.show', $quotation)->with('success', 'Quotation marked as sent.');
    }

    public function markAsCustomerApproved(Request $request, Quotation $quotation)
    {
        $this->autoFinalizeApprovedQuotationIfExpired($quotation);
        $user = $request->user();
        if (! $user || ! $user->can('quotations.approve')) {
            return redirect()->route('quotations.show', $quotation)->with('error', 'You do not have permission to mark customer approved.');
        }

        if (! $this->quotationWorkflowService->canUsePostSentAction($quotation, Quotation::STATUS_APPROVED)) {
            return redirect()->route('quotations.show', $quotation)->with('error', 'Quotation can be customer approved only from sent status.');
        }

        $validated = $request->validate([
            'approval_note' => ['nullable', 'string', 'max:2000'],
        ]);
        $note = trim((string) ($validated['approval_note'] ?? ''));

        DB::transaction(function () use ($quotation, $user, $note): void {
            $quotation->update([
                'approved_by' => $user->id,
                'approved_at' => now(),
                'approval_note' => $note === '' ? null : $note,
                'approval_note_by' => $note === '' ? null : $user->id,
                'approval_note_at' => $note === '' ? null : now(),
            ]);
            $this->quotationWorkflowService->transition(
                $quotation,
                Quotation::STATUS_APPROVED,
                (int) $user->id,
                'customer_approved',
                ['approval_note_present' => $note !== '']
            );
        });
        $this->syncLinkedLifecycleStatusesForQuotation($quotation);

        return redirect()->route('quotations.show', $quotation)->with('success', 'Quotation marked as customer approved.');
    }

    public function requestRevision(Request $request, Quotation $quotation)
    {
        $this->autoFinalizeApprovedQuotationIfExpired($quotation);
        $user = $request->user();
        if (! $user || ! $this->canManageQuotation($quotation, 'update')) {
            return $this->denyQuotationMutation($quotation);
        }
        $logicalStatus = QuotationStatusNormalizer::normalize((string) ($quotation->status ?? ''));
        if (in_array($logicalStatus, [Quotation::STATUS_APPROVED, Quotation::STATUS_CONVERTED_TO_BOOKING, Quotation::STATUS_BOOKING_CREATED, Quotation::STATUS_IN_OPERATION, Quotation::STATUS_COMPLETED, Quotation::STATUS_LOST, Quotation::STATUS_REJECTED, Quotation::STATUS_CANCELLED], true)) {
            return redirect()->route('quotations.show', $quotation)->with('error', 'Approved or closed quotation cannot be revised without official reopen.');
        }
        if ($logicalStatus === Quotation::STATUS_UNDER_REVISION) {
            return redirect()->route('quotations.edit', $quotation)->with('info', 'This quotation is already under revision.');
        }
        if (! in_array($logicalStatus, [Quotation::STATUS_READY_TO_SEND, Quotation::STATUS_REVISION_REQUESTED, Quotation::STATUS_NEED_REVALIDATION, Quotation::STATUS_PENDING_REVALIDATION], true)) {
            return redirect()->route('quotations.show', $quotation)->with('error', 'Revision can only be started before approval while quotation is ready or waiting revision.');
        }

        $quotation = DB::transaction(function () use ($quotation, $user): Quotation {
            $quotation->refresh();
            $nextRevisionNumber = max(1, (int) ($quotation->revision_number ?? 1)) + 1;

            $this->quotationWorkflowService->transition(
                $quotation,
                Quotation::STATUS_UNDER_REVISION,
                (int) $user->id,
                'start_revision',
                [
                    'revision_reason_present' => false,
                    'revision_number' => $nextRevisionNumber,
                ]
            );

            $patch = [
                'revision_number' => $nextRevisionNumber,
                'is_current_revision' => true,
                'revision_reason' => null,
                'updated_by' => (int) $user->id,
                'updated_at' => now(),
            ];
            $safePatch = [];
            foreach ($patch as $column => $value) {
                if (Schema::hasColumn('quotations', $column)) {
                    $safePatch[$column] = $value;
                }
            }
            if ($safePatch !== []) {
                DB::table('quotations')->where('id', (int) $quotation->id)->update($safePatch);
            }

            $quotation->refresh();
            $this->writeInPlaceRevisionLog($quotation, $nextRevisionNumber, null, (int) $user->id);

            return $quotation;
        });

        return redirect()
            ->route('quotations.edit', ['quotation' => $quotation->id, 'revision_mode' => 1])
            ->with('success', 'Quotation revision started.');
    }

    public function cancel(Request $request, Quotation $quotation)
    {
        $this->autoFinalizeApprovedQuotationIfExpired($quotation);
        $user = $request->user();
        if (! $user || ! $this->canManageQuotation($quotation, 'update')) {
            return $this->denyQuotationMutation($quotation);
        }
        if (! $this->quotationWorkflowService->canUsePostSentAction($quotation, Quotation::STATUS_CANCELLED)) {
            return redirect()->route('quotations.show', $quotation)->with('error', 'Quotation can only be cancelled from sent status.');
        }

        $validated = $request->validate([
            'cancellation_reason' => ['nullable', 'string', 'max:2000'],
        ]);
        $reason = trim((string) ($validated['cancellation_reason'] ?? ''));

        DB::transaction(function () use ($quotation, $user, $reason): void {
            $this->quotationWorkflowService->transition(
                $quotation,
                Quotation::STATUS_CANCELLED,
                (int) $user->id,
                'cancel',
                ['cancellation_reason_present' => $reason !== '']
            );
            $this->syncInquiryStatusFromQuotation($quotation, Quotation::STATUS_CANCELLED);
        });
        $this->syncLinkedLifecycleStatusesForQuotation($quotation);

        return redirect()
            ->route('quotations.show', $quotation)
            ->with('success', 'Quotation marked as cancelled.');
    }

    public function reject(Request $request, Quotation $quotation)
    {
        $this->autoFinalizeApprovedQuotationIfExpired($quotation);
        $user = $request->user();
        if (! $user || ! $user->can('quotations.reject')) {
            return redirect()->back()->with('error', 'You do not have permission to reject quotation.');
        }
        if (! $this->quotationWorkflowService->canUsePostSentAction($quotation, Quotation::STATUS_LOST)) {
            return redirect()->route('quotations.show', $quotation)->with('error', 'Quotation can only be marked lost from sent status.');
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

        DB::transaction(function () use ($quotation, $user, $note): void {
            $quotation->approvals()->delete();
            $quotation->update([
                'approved_by' => null,
                'approved_at' => null,
                'approval_note' => $note,
                'approval_note_by' => $user->id,
                'approval_note_at' => now(),
            ]);
            $this->quotationWorkflowService->transition(
                $quotation,
                Quotation::STATUS_LOST,
                (int) $user->id,
                'reject',
                ['approval_note_present' => true]
            );
            $this->syncInquiryStatusFromQuotation($quotation, Quotation::STATUS_LOST);
        });
        $this->syncLinkedLifecycleStatusesForQuotation($quotation);

        return redirect()
            ->route('quotations.show', $quotation)
            ->with('success', 'Quotation marked as lost.');
    }

    public function setPending(Request $request, Quotation $quotation)
    {
        $this->autoFinalizeApprovedQuotationIfExpired($quotation);
        $user = $request->user();
        if (! $user || ! $this->canSetQuotationPending($user)) {
            return redirect()->back()->with('error', 'You do not have permission to set quotation to pending.');
        }

        if (! $quotation->isStatus(Quotation::STATUS_SENT, Quotation::STATUS_CUSTOMER_APPROVED, Quotation::FINAL_STATUS)) {
            return redirect()->back()->with('error', 'Only sent, approved, or final quotation can be set to pending.');
        }

        $payload = [
            'approved_by' => null,
            'approved_at' => null,
            'status' => Quotation::STATUS_NEED_VALIDATION,
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

        $targetPendingStatus = $quotation->isStatus(Quotation::STATUS_SENT) ? 'pending' : Quotation::STATUS_NEED_VALIDATION;
        $canSetPendingFromCurrentStatus = $quotation->isStatus(Quotation::STATUS_SENT)
            ? $this->quotationWorkflowService->canUsePostSentAction($quotation, $targetPendingStatus)
            : $this->quotationWorkflowService->canTransition($quotation, $targetPendingStatus);
        if (! $canSetPendingFromCurrentStatus) {
            return redirect()->route('quotations.show', $quotation)->with('error', 'Quotation cannot be set to pending from current status.');
        }

        DB::transaction(function () use ($quotation, $payload, $user, $targetPendingStatus): void {
            $quotation->approvals()->delete();
            unset($payload['status']);
            if ($payload !== []) {
                $quotation->update($payload);
            }
            $this->quotationWorkflowService->transition(
                $quotation,
                $targetPendingStatus,
                (int) $user->id,
                'set_pending'
            );
        });
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
                ->with('success', 'Quotation is already in final downstream status.');
        }

        $user = $request->user();
        if (! $user || ! $quotation->isCreator($user)) {
            return redirect()
                ->route('quotations.show', $quotation)
                ->with('error', 'Only quotation creator can move this quotation to final downstream status.');
        }

        if (! $quotation->isStatus(Quotation::STATUS_CUSTOMER_APPROVED)) {
            return redirect()
                ->route('quotations.show', $quotation)
                ->with('error', 'Only customer approved quotation can be moved to final downstream status.');
        }

        if (! $this->quotationWorkflowService->canTransition($quotation, Quotation::FINAL_STATUS)) {
            return redirect()->route('quotations.show', $quotation)->with('error', 'Quotation cannot be moved to final downstream status from current status.');
        }

        $this->quotationWorkflowService->transition(
            $quotation,
            Quotation::FINAL_STATUS,
            (int) $user->id,
            'set_final'
        );
        $this->syncLinkedLifecycleStatusesForQuotation($quotation);

        return redirect()
            ->route('quotations.show', $quotation)
            ->with('success', 'Quotation status changed to final downstream status.');
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

        $wasApproved = $quotation->isStatus(Quotation::STATUS_CUSTOMER_APPROVED);
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
                    'status' => Quotation::STATUS_NEED_VALIDATION,
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

    public function cancelItemFree(Request $request, Quotation $quotation, QuotationItem $item)
    {
        return $this->cancelQuotationItem($request, $quotation, $item, QuotationItem::STATUS_CANCELLED_FREE);
    }

    public function cancelItemWithCharge(Request $request, Quotation $quotation, QuotationItem $item)
    {
        return $this->cancelQuotationItem($request, $quotation, $item, QuotationItem::STATUS_CANCELLED_WITH_CHARGE);
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

    private function buildServiceItemCatalogs(): array
    {
        $activities = Activity::query()
            ->with(['vendor:id,name,destination_id,city,province,location', 'vendor.destination:id,name,city,province'])
            ->where('is_active', true)
            ->orderBy('name')
            ->get([
                'id',
                'name',
                'vendor_id',
                'adult_publish_rate',
                'child_publish_rate',
                'adult_contract_rate',
                'child_contract_rate',
                'adult_markup_type',
                'adult_markup',
                'child_markup_type',
                'child_markup',
            ])
            ->map(function (Activity $activity): array {
                $vendorName = trim((string) ($activity->vendor?->name ?? ''));
                $city = $this->resolveCityLabel(
                    trim((string) ($activity->vendor?->city ?? '')),
                    trim((string) ($activity->vendor?->province ?? '')),
                    trim((string) ($activity->vendor?->destination?->city ?? '')),
                    trim((string) ($activity->vendor?->destination?->province ?? ''))
                );

                return [
                    'id' => (int) $activity->id,
                    'label' => $this->composeServiceItemLabel((string) ($activity->name ?? ''), $vendorName, $city),
                    'description_label' => $this->formatServiceDescription('Activity', (string) ($activity->name ?? ''), $vendorName),
                    'vendor_name' => $vendorName,
                    'vendor_region' => $city,
                    'rate' => (float) ($activity->adult_publish_rate ?? 0),
                    'contract_rate' => (float) ($activity->adult_contract_rate ?? 0),
                    'adult_rate' => (float) ($activity->adult_publish_rate ?? 0),
                    'adult_contract_rate' => (float) ($activity->adult_contract_rate ?? 0),
                    'adult_markup_type' => (string) ($activity->adult_markup_type ?? 'fixed'),
                    'adult_markup' => (float) ($activity->adult_markup ?? 0),
                    'child_rate' => (float) ($activity->child_publish_rate ?? $activity->adult_publish_rate ?? 0),
                    'child_contract_rate' => (float) ($activity->child_contract_rate ?? $activity->adult_contract_rate ?? 0),
                    'child_markup_type' => (string) ($activity->child_markup_type ?? $activity->adult_markup_type ?? 'fixed'),
                    'child_markup' => (float) ($activity->child_markup ?? $activity->adult_markup ?? 0),
                    'destination_id' => (int) ($activity->vendor?->destination_id ?? 0),
                ];
            })
            ->values();

        $islandTransfers = IslandTransfer::query()
            ->with(['vendor:id,name,destination_id,city,province,location', 'vendor.destination:id,name,city,province'])
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'vendor_id', 'publish_rate', 'contract_rate'])
            ->map(function (IslandTransfer $transfer): array {
                $vendorName = trim((string) ($transfer->vendor?->name ?? ''));
                $city = $this->resolveCityLabel(
                    trim((string) ($transfer->vendor?->city ?? '')),
                    trim((string) ($transfer->vendor?->destination?->city ?? ''))
                );

                return [
                    'id' => (int) $transfer->id,
                    'label' => $this->composeServiceItemLabel((string) ($transfer->name ?? ''), $vendorName, $city),
                    'description_label' => $this->formatServiceDescription('Island Transfer', (string) ($transfer->name ?? '')),
                    'rate' => (float) ($transfer->publish_rate ?? 0),
                    'contract_rate' => (float) ($transfer->contract_rate ?? 0),
                    'destination_id' => (int) ($transfer->vendor?->destination_id ?? 0),
                ];
            })
            ->values();

        $foodBeverages = FoodBeverage::query()
            ->with(['vendor:id,name,destination_id,city,province,location', 'vendor.destination:id,name,city,province'])
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'vendor_id', 'adult_publish_rate', 'adult_contract_rate', 'publish_rate', 'contract_rate'])
            ->map(function (FoodBeverage $fnb): array {
                $vendorName = trim((string) ($fnb->vendor?->name ?? ''));
                $city = $this->resolveCityLabel(
                    trim((string) ($fnb->vendor?->city ?? '')),
                    trim((string) ($fnb->vendor?->destination?->city ?? ''))
                );

                return [
                    'id' => (int) $fnb->id,
                    'label' => $this->composeServiceItemLabel((string) ($fnb->name ?? ''), $vendorName, $city),
                    'description_label' => $this->formatServiceDescription('F&B', (string) ($fnb->name ?? '')),
                    'vendor_name' => $vendorName,
                    'vendor_region' => $city,
                    'rate' => (float) ($fnb->adult_publish_rate ?? $fnb->publish_rate ?? 0),
                    'contract_rate' => (float) ($fnb->adult_contract_rate ?? $fnb->contract_rate ?? 0),
                    'adult_rate' => (float) ($fnb->adult_publish_rate ?? $fnb->publish_rate ?? 0),
                    'adult_contract_rate' => (float) ($fnb->adult_contract_rate ?? $fnb->contract_rate ?? 0),
                    'adult_markup_type' => (string) ($fnb->adult_markup_type ?? $fnb->markup_type ?? 'fixed'),
                    'adult_markup' => (float) ($fnb->adult_markup ?? $fnb->markup ?? 0),
                    'child_rate' => (float) ($fnb->child_publish_rate ?? $fnb->adult_publish_rate ?? $fnb->publish_rate ?? 0),
                    'child_contract_rate' => (float) ($fnb->child_contract_rate ?? $fnb->adult_contract_rate ?? $fnb->contract_rate ?? 0),
                    'child_markup_type' => (string) ($fnb->child_markup_type ?? $fnb->adult_markup_type ?? $fnb->markup_type ?? 'fixed'),
                    'child_markup' => (float) ($fnb->child_markup ?? $fnb->adult_markup ?? $fnb->markup ?? 0),
                    'destination_id' => (int) ($fnb->vendor?->destination_id ?? 0),
                ];
            })
            ->values();

        $transportUnits = TransportUnit::query()
            ->with(['vendor:id,name,destination_id,city,province,location', 'vendor.destination:id,name,city,province'])
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'vendor_id', 'publish_rate', 'contract_rate'])
            ->map(function (TransportUnit $unit): array {
                $vendorName = trim((string) ($unit->vendor?->name ?? ''));
                $city = $this->resolveCityLabel(
                    trim((string) ($unit->vendor?->city ?? '')),
                    trim((string) ($unit->vendor?->destination?->city ?? ''))
                );

                return [
                    'id' => (int) $unit->id,
                    'label' => $this->composeServiceItemLabel((string) ($unit->name ?? ''), $vendorName, $city),
                    'description_label' => $this->formatServiceDescription('Transport', (string) ($unit->name ?? '')),
                    'rate' => (float) ($unit->publish_rate ?? 0),
                    'contract_rate' => (float) ($unit->contract_rate ?? 0),
                    'destination_id' => (int) ($unit->vendor?->destination_id ?? 0),
                ];
            })
            ->values();

        $touristAttractions = TouristAttraction::query()
            ->with('destination:id,name,city,province')
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'city', 'destination_id', 'publish_rate_per_pax', 'contract_rate_per_pax'])
            ->map(function (TouristAttraction $attraction): array {
                $city = $this->resolveCityLabel(
                    trim((string) ($attraction->city ?? '')),
                    trim((string) ($attraction->destination?->city ?? '')),
                );

                return [
                    'id' => (int) $attraction->id,
                    'label' => $this->composeServiceItemLabel((string) ($attraction->name ?? ''), null, $city),
                    'description_label' => $this->formatServiceDescription('Attraction', (string) ($attraction->name ?? '')),
                    'rate' => (float) ($attraction->publish_rate_per_pax ?? 0),
                    'contract_rate' => (float) ($attraction->contract_rate_per_pax ?? 0),
                    'destination_id' => (int) ($attraction->destination_id ?? 0),
                ];
            })
            ->values();

        $latestRoomPrices = HotelPrice::query()
            ->select(['rooms_id', 'publish_rate', 'contract_rate'])
            ->orderByDesc('end_date')
            ->orderByDesc('start_date')
            ->orderByDesc('id')
            ->get()
            ->groupBy('rooms_id')
            ->map(fn ($rows) => $rows->first());

        $hotelRooms = HotelRoom::query()
            ->with('hotel:id,name,destination_id')
            ->whereHas('hotel', function ($query): void {
                $query->where('status', 'active');
            })
            ->where('status', 'active')
            ->orderBy('id')
            ->get(['id', 'hotels_id', 'rooms', 'view'])
            ->map(function (HotelRoom $room) use ($latestRoomPrices): array {
                $price = $latestRoomPrices->get((int) $room->id);
                $hotelName = (string) ($room->hotel?->name ?? '-');
                $roomName = trim((string) ($room->rooms ?? ''));
                $viewName = trim((string) ($room->view ?? ''));
                $itemName = $hotelName;
                if ($roomName !== '') {
                    $itemName .= ' - ' . $roomName;
                }
                if ($viewName !== '') {
                    $itemName .= ' (' . $viewName . ')';
                }
                $descriptionName = $hotelName;
                if ($roomName !== '') {
                    $descriptionName .= ' - ' . $roomName;
                }
                $city = $this->resolveCityLabel(trim((string) ($room->hotel?->city ?? '')));

                return [
                    'id' => (int) $room->id,
                    'label' => $this->composeServiceItemLabel($itemName, null, $city),
                    'description_label' => $this->formatServiceDescription('Hotel', $descriptionName),
                    'rate' => (float) ($price->publish_rate ?? 0),
                    'contract_rate' => (float) ($price->contract_rate ?? 0),
                    'destination_id' => (int) ($room->hotel?->destination_id ?? 0),
                ];
            })
            ->values();

        $activities = $this->sortServiceCatalogByLabel($activities->all());
        $islandTransfers = $this->sortServiceCatalogByLabel($islandTransfers->all());
        $foodBeverages = $this->sortServiceCatalogByLabel($foodBeverages->all());
        $transportUnits = $this->sortServiceCatalogByLabel($transportUnits->all());
        $touristAttractions = $this->sortServiceCatalogByLabel($touristAttractions->all());
        $hotelRooms = $this->sortServiceCatalogByLabel($hotelRooms->all());

        return [
            'activities' => $activities,
            'island_transfers' => $islandTransfers,
            'food_beverages' => $foodBeverages,
            'transport_units' => $transportUnits,
            'tourist_attractions' => $touristAttractions,
            'hotel_rooms' => $hotelRooms,
        ];
    }

    /**
     * @param array<int, array<string, mixed>> $items
     * @return array<int, array<string, mixed>>
     */
    private function sortServiceCatalogByLabel(array $items): array
    {
        usort($items, function (array $a, array $b): int {
            return strcasecmp((string) ($a['label'] ?? ''), (string) ($b['label'] ?? ''));
        });

        return array_values($items);
    }

    private function composeServiceItemLabel(string $itemName, ?string $vendorName = null, ?string $city = null): string
    {
        $cleanItemName = trim($itemName) !== '' ? trim($itemName) : '-';
        $cleanVendorName = trim((string) $vendorName);
        $cleanCity = trim((string) $city);
        $parts = [$cleanItemName];

        if ($cleanVendorName !== '') {
            $parts[] = $cleanVendorName;
        }
        if ($cleanCity !== '') {
            $parts[] = $cleanCity;
        }

        return implode(' - ', $parts);
    }

    private function formatServiceDescription(string $serviceType, string $serviceName, ?string $vendorName = null): string
    {
        $cleanType = trim($serviceType) !== '' ? trim($serviceType) : 'Service';
        $cleanName = trim($serviceName) !== '' ? trim($serviceName) : '-';
        $cleanVendorName = trim((string) $vendorName);
        if ($cleanVendorName !== '' && in_array($cleanType, ['Activity', 'F&B'], true)) {
            $cleanName = $this->appendVendorToServiceName($cleanName, $cleanVendorName);
        }

        return $cleanType . ': ' . $cleanName;
    }

    private function formatFoodBeverageDescription(string $serviceName, string $paxType = '', ?string $vendorName = null, ?string $vendorRegion = null): string
    {
        return $this->formatPaxAwareServiceDescription('F&B', $serviceName, $paxType, $vendorName, $vendorRegion);
    }

    private function formatActivityDescription(string $serviceName, string $paxType = '', ?string $vendorName = null, ?string $vendorRegion = null): string
    {
        return $this->formatPaxAwareServiceDescription('Activity', $serviceName, $paxType, $vendorName, $vendorRegion);
    }

    private function formatPaxAwareServiceDescription(string $serviceType, string $serviceName, string $paxType = '', ?string $vendorName = null, ?string $vendorRegion = null): string
    {
        $cleanName = $this->sanitizePaxAwareServiceText($serviceName);
        if ($cleanName === '') {
            $cleanName = '-';
        }
        $cleanVendorName = $this->sanitizePaxAwareServiceText((string) $vendorName);
        $cleanVendorRegion = $this->sanitizePaxAwareServiceText((string) $vendorRegion);
        $normalizedPaxType = strtolower(trim($paxType));

        if (in_array($normalizedPaxType, ['adult', 'child'], true)) {
            $cleanName .= ' (' . strtoupper($normalizedPaxType) . ')';
        }

        $parts = [$cleanName];
        if ($cleanVendorName !== '') {
            $parts[] = $cleanVendorName;
        }
        if ($cleanVendorRegion !== '') {
            $parts[] = $cleanVendorRegion;
        }

        return trim($serviceType) . ': ' . implode(' - ', $parts);
    }

    /**
     * @return array{service_name:string,vendor_name:string,vendor_region:string,pax_type:string}
     */
    private function normalizeFoodBeverageDescriptionParts(array $item): array
    {
        return $this->normalizePaxAwareServiceDescriptionParts($item, FoodBeverage::class);
    }

    /**
     * @return array{service_name:string,vendor_name:string,vendor_region:string,pax_type:string}
     */
    private function normalizeActivityDescriptionParts(array $item): array
    {
        return $this->normalizePaxAwareServiceDescriptionParts($item, Activity::class);
    }

    /**
     * @return array{service_name:string,vendor_name:string,vendor_region:string,pax_type:string}
     */
    private function normalizePaxAwareServiceDescriptionParts(array $item, string $serviceableType): array
    {
        $meta = $this->normalizeServiceableMeta($item['serviceable_meta'] ?? null) ?? [];
        $rawDescription = trim((string) ($item['description'] ?? ''));
        $descriptionBody = preg_replace('/^[^:]+:\s*/', '', $rawDescription) ?: $rawDescription;
        $segments = array_values(array_filter(array_map(
            fn ($segment) => $this->sanitizePaxAwareServiceText((string) $segment),
            preg_split('/\s+-\s+/', $descriptionBody) ?: []
        ), fn ($segment) => $segment !== ''));

        $masterData = $this->resolvePaxAwareMasterDescriptionData($item, $serviceableType);
        $serviceName = $this->sanitizePaxAwareServiceText((string) ($masterData['service_name'] ?? ''));
        $vendorName = $this->sanitizePaxAwareServiceText((string) ($masterData['vendor_name'] ?? ''));
        $vendorRegion = $this->sanitizePaxAwareServiceText((string) ($masterData['vendor_region'] ?? ''));
        $paxType = strtolower(trim((string) ($meta['pax_type'] ?? '')));

        if ($paxType === '' && preg_match('/\((adult|child)\)/i', $descriptionBody, $matches) === 1) {
            $paxType = strtolower((string) ($matches[1] ?? ''));
        }

        if ($serviceName === '') {
            $serviceName = $segments[0] ?? '';
        }
        if ($vendorName === '') {
            foreach (array_slice($segments, 1) as $segment) {
                if ($segment !== '' && strcasecmp($segment, $serviceName) !== 0) {
                    $vendorName = $segment;
                    break;
                }
            }
        }

        if ($vendorRegion === '') {
            foreach (array_slice($segments, 1) as $segment) {
                if ($segment !== ''
                    && strcasecmp($segment, $serviceName) !== 0
                    && strcasecmp($segment, $vendorName) !== 0) {
                    $vendorRegion = $segment;
                    break;
                }
            }
        }

        return [
            'service_name' => $serviceName,
            'vendor_name' => $vendorName,
            'vendor_region' => $vendorRegion,
            'pax_type' => $paxType,
        ];
    }

    /**
     * @return array{service_name:string,vendor_name:string,vendor_region:string}
     */
    private function resolveFoodBeverageMasterDescriptionData(array $item): array
    {
        return $this->resolvePaxAwareMasterDescriptionData($item, FoodBeverage::class);
    }

    /**
     * @return array{service_name:string,vendor_name:string,vendor_region:string}
     */
    private function resolveActivityMasterDescriptionData(array $item): array
    {
        return $this->resolvePaxAwareMasterDescriptionData($item, Activity::class);
    }

    /**
     * @return array{service_name:string,vendor_name:string,vendor_region:string}
     */
    private function resolvePaxAwareMasterDescriptionData(array $item, string $expectedType): array
    {
        $serviceableType = (string) ($item['serviceable_type'] ?? '');
        $serviceableId = (int) ($item['serviceable_id'] ?? 0);
        if ($serviceableType !== $expectedType || $serviceableId <= 0) {
            return [
                'service_name' => '',
                'vendor_name' => '',
                'vendor_region' => '',
            ];
        }

        $model = $expectedType::query()
            ->with(['vendor:id,name,city,province,destination_id', 'vendor.destination:id,city,province'])
            ->find($serviceableId);

        if (! $model) {
            return [
                'service_name' => '',
                'vendor_name' => '',
                'vendor_region' => '',
            ];
        }

        return [
            'service_name' => trim((string) ($model->name ?? '')),
            'vendor_name' => trim((string) ($model->vendor?->name ?? '')),
            'vendor_region' => $this->resolveCityLabel(
                trim((string) ($model->vendor?->city ?? '')),
                trim((string) ($model->vendor?->province ?? '')),
                trim((string) ($model->vendor?->destination?->city ?? '')),
                trim((string) ($model->vendor?->destination?->province ?? '')),
            ),
        ];
    }

    private function sanitizeFoodBeverageText(string $value): string
    {
        return $this->sanitizePaxAwareServiceText($value);
    }

    private function sanitizePaxAwareServiceText(string $value): string
    {
        $clean = trim($value);
        if ($clean === '') {
            return '';
        }

        $segments = array_values(array_filter(array_map('trim', preg_split('/\s+-\s+/', $clean) ?: []), fn ($segment) => $segment !== ''));
        $normalizedSegments = [];

        foreach ($segments as $segment) {
            $normalized = trim((string) preg_replace('/\s*\((adult|child)\)\s*$/i', '', $segment));
            if ($normalized === '') {
                continue;
            }
            $alreadyExists = collect($normalizedSegments)->contains(fn ($existing) => strcasecmp((string) $existing, $normalized) === 0);
            if (! $alreadyExists) {
                $normalizedSegments[] = $normalized;
            }
        }

        return trim(implode(' - ', $normalizedSegments));
    }

    private function appendVendorToServiceName(string $serviceName, string $vendorName): string
    {
        $cleanName = trim($serviceName) !== '' ? trim($serviceName) : '-';
        $cleanVendorName = trim($vendorName);
        if ($cleanVendorName === '') {
            return $cleanName;
        }
        if (preg_match('/\s-\s' . preg_quote($cleanVendorName, '/') . '$/i', $cleanName) === 1) {
            return $cleanName;
        }

        return $cleanName . ' - ' . $cleanVendorName;
    }

    private function quotationServiceTypeLabel(?string $itineraryItemType, ?string $serviceableType = null): string
    {
        return match ((string) $itineraryItemType) {
            'transport_day' => 'Transport',
            'attraction' => 'Attraction',
            'activity' => 'Activity',
            'transfer' => 'Island Transfer',
            'fnb' => 'F&B',
            'hotel_day_end' => 'Hotel',
            default => match ((string) $serviceableType) {
                TransportUnit::class => 'Transport',
                TouristAttraction::class => 'Attraction',
                Activity::class => 'Activity',
                IslandTransfer::class => 'Island Transfer',
                FoodBeverage::class => 'F&B',
                HotelRoom::class => 'Hotel',
                default => 'Service',
            },
        };
    }

    private function normalizeQuotationServiceDescription(array $item): array
    {
        $serviceType = $this->quotationServiceTypeLabel(
            $item['itinerary_item_type'] ?? null,
            $item['serviceable_type'] ?? null,
        );
        $rawDescription = trim((string) ($item['description'] ?? ''));
        $serviceName = preg_replace('/^[^:]+:\s*/', '', $rawDescription) ?: $rawDescription;
        if ($serviceType === 'Hotel') {
            $serviceName = trim((string) preg_replace('/\s*\([^)]*\)/', '', $serviceName));
        }
        if ($serviceType === 'Activity') {
            $parts = $this->normalizeActivityDescriptionParts($item);
            $item['description'] = $this->formatActivityDescription(
                $parts['service_name'],
                $parts['pax_type'],
                $parts['vendor_name'],
                $parts['vendor_region'],
            );

            return $item;
        }
        if ($serviceType === 'F&B') {
            $parts = $this->normalizeFoodBeverageDescriptionParts($item);
            $item['description'] = $this->formatFoodBeverageDescription(
                $parts['service_name'],
                $parts['pax_type'],
                $parts['vendor_name'],
                $parts['vendor_region'],
            );

            return $item;
        }
        $item['description'] = $this->formatServiceDescription(
            $serviceType,
            $serviceName,
            $this->resolveQuotationServiceVendorName($item)
        );

        return $item;
    }

    private function applyQuotationServiceDescriptions(Quotation $quotation): void
    {
        if (! $quotation->relationLoaded('items')) {
            return;
        }

        $quotation->items->each(function (QuotationItem $item): void {
            $payload = [
                'description' => (string) ($item->description ?? ''),
                'serviceable_type' => $item->serviceable_type,
                'serviceable_id' => $item->serviceable_id,
                'serviceable_meta' => $item->serviceable_meta,
                'itinerary_item_type' => $item->itinerary_item_type,
            ];
            $normalized = $this->normalizeQuotationServiceDescription($payload);
            $item->setAttribute('description', $normalized['description'] ?? $item->description);
        });
    }

    private function applyQuotationPdfItemPresentation(Quotation $quotation): void
    {
        if (! $quotation->relationLoaded('items')) {
            return;
        }

        $foodBeverageIds = $quotation->items
            ->filter(fn (QuotationItem $item): bool => (string) ($item->serviceable_type ?? '') === FoodBeverage::class)
            ->pluck('serviceable_id')
            ->filter(fn ($id): bool => (int) $id > 0)
            ->map(fn ($id): int => (int) $id)
            ->unique()
            ->values();

        $menuHighlightsLookup = $foodBeverageIds->isEmpty()
            ? collect()
            : FoodBeverage::query()
                ->whereIn('id', $foodBeverageIds->all())
                ->pluck('menu_highlights', 'id');

        $quotation->items->each(function (QuotationItem $item) use ($menuHighlightsLookup): void {
            if ((string) ($item->serviceable_type ?? '') !== FoodBeverage::class) {
                $item->setAttribute('pdf_menu_highlights', '');

                return;
            }

            $meta = $this->normalizeServiceableMeta($item->serviceable_meta ?? null) ?? [];
            $rawMenuHighlights = (string) (
                $meta['menu_highlights']
                ?? $meta['menu_highlight']
                ?? $menuHighlightsLookup->get((int) ($item->serviceable_id ?? 0), '')
            );

            $item->setAttribute(
                'pdf_menu_highlights',
                $this->normalizeQuotationPdfMenuHighlights($rawMenuHighlights)
            );
        });
    }

    private function normalizeQuotationPdfMenuHighlights(?string $value): string
    {
        $safe = SafeRichText::sanitize($value);
        if ($safe === '') {
            return '';
        }

        $withBreaks = preg_replace('/<br\s*\/?>/i', "\n", $safe) ?? $safe;
        $withBreaks = preg_replace('/<\/(p|div|li|ul|ol|blockquote|h2|h3)>/i', "\n", $withBreaks) ?? $withBreaks;
        $plain = trim(html_entity_decode(strip_tags($withBreaks), ENT_QUOTES | ENT_HTML5, 'UTF-8'));
        if ($plain === '') {
            return '';
        }

        $plain = str_replace(["\r\n", "\r", '•', '·'], "\n", $plain);
        $segments = array_values(array_filter(array_map(
            static fn (string $segment): string => trim(preg_replace('/\s+/', ' ', $segment) ?? $segment),
            preg_split('/[\n;]+/', $plain) ?: []
        ), static fn (string $segment): bool => $segment !== ''));

        $uniqueSegments = [];
        foreach ($segments as $segment) {
            $alreadyExists = collect($uniqueSegments)->contains(
                static fn (string $existing): bool => strcasecmp($existing, $segment) === 0
            );
            if (! $alreadyExists) {
                $uniqueSegments[] = $segment;
            }
        }

        return implode(' | ', $uniqueSegments);
    }

    private function resolveQuotationServiceVendorName(array $item): string
    {
        $serviceableType = (string) ($item['serviceable_type'] ?? '');
        if (! in_array($serviceableType, [Activity::class, FoodBeverage::class], true)) {
            return '';
        }

        $meta = $this->normalizeServiceableMeta($item['serviceable_meta'] ?? null) ?? [];
        $vendorName = trim((string) ($meta['vendor_name'] ?? ''));
        if ($vendorName !== '') {
            return $vendorName;
        }

        $serviceableId = (int) ($item['serviceable_id'] ?? 0);
        if ($serviceableId <= 0) {
            return '';
        }

        $model = $serviceableType::query()
            ->with('vendor:id,name,city,province,destination_id')
            ->find($serviceableId);

        return trim((string) ($model?->vendor?->name ?? ''));
    }

    private function resolveQuotationServiceVendorRegion(array $item): string
    {
        $serviceableType = (string) ($item['serviceable_type'] ?? '');
        if (! in_array($serviceableType, [Activity::class, FoodBeverage::class], true)) {
            return '';
        }

        $meta = $this->normalizeServiceableMeta($item['serviceable_meta'] ?? null) ?? [];
        $vendorRegion = trim((string) ($meta['vendor_region'] ?? ''));
        if ($vendorRegion !== '') {
            return $vendorRegion;
        }

        $serviceableId = (int) ($item['serviceable_id'] ?? 0);
        if ($serviceableId <= 0) {
            return '';
        }

        $model = $serviceableType::query()
            ->with(['vendor:id,name,city,province,destination_id', 'vendor.destination:id,city,province'])
            ->find($serviceableId);

        return $this->resolveCityLabel(
            trim((string) ($model?->vendor?->city ?? '')),
            trim((string) ($model?->vendor?->province ?? '')),
            trim((string) ($model?->vendor?->destination?->city ?? '')),
            trim((string) ($model?->vendor?->destination?->province ?? '')),
        );
    }

    private function resolveCityLabel(?string ...$parts): string
    {
        foreach ($parts as $part) {
            $clean = trim((string) $part);
            if ($clean !== '') {
                return $clean;
            }
        }

        return '';
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

    /**
     * @param  array<int, array<string, mixed>>  $items
     * @return array<int, array<string, mixed>>
     */
    private function syncMissingServicePublishRatesFromQuotationItems(array $items): array
    {
        return array_map(function (array $item): array {
            $rate = max(0, (float) ($item['rate'] ?? $item['unit_price'] ?? 0));
            $serviceableType = (string) ($item['serviceable_type'] ?? '');
            $serviceableId = (int) ($item['serviceable_id'] ?? 0);
            if ($rate <= 0 || $serviceableType === '' || $serviceableId <= 0) {
                return $item;
            }

            $ratePayload = $this->buildDefaultRatePayload($rate);
            $synced = match ($serviceableType) {
                Activity::class => $this->syncMissingActivityPublishRate($serviceableId, $item, $ratePayload),
                TouristAttraction::class => $this->syncMissingSimplePublishRate(
                    TouristAttraction::class,
                    $serviceableId,
                    'publish_rate_per_pax',
                    'contract_rate_per_pax',
                    $ratePayload
                ),
                IslandTransfer::class => $this->syncMissingSimplePublishRate(
                    IslandTransfer::class,
                    $serviceableId,
                    'publish_rate',
                    'contract_rate',
                    $ratePayload
                ),
                FoodBeverage::class => $this->syncMissingFoodBeveragePublishRate(
                    $serviceableId,
                    $item,
                    $ratePayload
                ),
                TransportUnit::class => $this->syncMissingSimplePublishRate(
                    TransportUnit::class,
                    $serviceableId,
                    'publish_rate',
                    'contract_rate',
                    $ratePayload
                ),
                HotelRoom::class => $this->syncMissingHotelRoomPublishRate($serviceableId, $item, $ratePayload),
                default => null,
            };

            if (! is_array($synced)) {
                return $item;
            }

            $item['contract_rate'] = $synced['contract_rate'];
            $item['markup_type'] = $synced['markup_type'];
            $item['markup'] = $synced['markup'];
            $item['unit_price'] = $synced['publish_rate'];
            $item['rate'] = $synced['publish_rate'];
            if (isset($synced['serviceable_meta']) && is_array($synced['serviceable_meta'])) {
                $item['serviceable_meta'] = $synced['serviceable_meta'];
            }

            return $item;
        }, $items);
    }

    /**
     * @return array{publish_rate: float, contract_rate: float, markup_type: string, markup: float}
     */
    private function buildDefaultRatePayload(float $publishRate): array
    {
        $markup = 10.0;
        $contractRate = $publishRate / (1 + ($markup / 100));

        return [
            'publish_rate' => round($publishRate, 2),
            'contract_rate' => round($contractRate, 2),
            'markup_type' => 'percent',
            'markup' => $markup,
        ];
    }

    /**
     * @return array{publish_rate: float, contract_rate: float, markup_type: string, markup: float}
     */
    private function buildRatePayloadFromMaster(float $publishRate, float $contractRate, ?string $markupType, float $markup): array
    {
        return [
            'publish_rate' => max(0, round($publishRate, 2)),
            'contract_rate' => max(0, round($contractRate, 2)),
            'markup_type' => $markupType === 'percent' ? 'percent' : 'fixed',
            'markup' => max(0, round($markup, 2)),
        ];
    }

    /**
     * @param  class-string<\Illuminate\Database\Eloquent\Model>  $modelClass
     * @return array{publish_rate: float, contract_rate: float, markup_type: string, markup: float}|null
     */
    private function syncMissingSimplePublishRate(
        string $modelClass,
        int $serviceableId,
        string $publishColumn,
        string $contractColumn,
        array $ratePayload
    ): ?array {
        $model = $modelClass::query()->find($serviceableId);
        if (! $model) {
            return null;
        }
        if ((float) ($model->{$publishColumn} ?? 0) > 0) {
            return $this->buildRatePayloadFromMaster(
                (float) ($model->{$publishColumn} ?? 0),
                (float) ($model->{$contractColumn} ?? 0),
                (string) ($model->markup_type ?? 'fixed'),
                (float) ($model->markup ?? 0)
            );
        }

        $model->forceFill([
            $publishColumn => $ratePayload['publish_rate'],
            $contractColumn => $ratePayload['contract_rate'],
            'markup_type' => $ratePayload['markup_type'],
            'markup' => $ratePayload['markup'],
        ])->save();

        return $ratePayload;
    }

    /**
     * @return array{publish_rate: float, contract_rate: float, markup_type: string, markup: float}|null
     */
    private function syncMissingActivityPublishRate(int $activityId, array $item, array $ratePayload): ?array
    {
        $activity = Activity::query()->find($activityId);
        if (! $activity) {
            return null;
        }

        $meta = $this->normalizeServiceableMeta($item['serviceable_meta'] ?? null) ?? [];
        $paxType = (string) ($meta['pax_type'] ?? 'adult');
        $isChild = $paxType === 'child';
        $publishColumn = $isChild ? 'child_publish_rate' : 'adult_publish_rate';
        $contractColumn = $isChild ? 'child_contract_rate' : 'adult_contract_rate';
        $markupTypeColumn = $isChild ? 'child_markup_type' : 'adult_markup_type';
        $markupColumn = $isChild ? 'child_markup' : 'adult_markup';

        if ((float) ($activity->{$publishColumn} ?? 0) > 0) {
            return $this->buildRatePayloadFromMaster(
                (float) ($activity->{$publishColumn} ?? 0),
                (float) ($activity->{$contractColumn} ?? 0),
                (string) ($activity->{$markupTypeColumn} ?? 'fixed'),
                (float) ($activity->{$markupColumn} ?? 0)
            );
        }

        $activity->forceFill([
            $publishColumn => $ratePayload['publish_rate'],
            $contractColumn => $ratePayload['contract_rate'],
            $markupTypeColumn => $ratePayload['markup_type'],
            $markupColumn => $ratePayload['markup'],
        ])->save();

        return $ratePayload;
    }

    /**
     * @return array{publish_rate: float, contract_rate: float, markup_type: string, markup: float}|null
     */
    private function syncMissingFoodBeveragePublishRate(int $foodBeverageId, array $item, array $ratePayload): ?array
    {
        $foodBeverage = FoodBeverage::query()->find($foodBeverageId);
        if (! $foodBeverage) {
            return null;
        }

        $meta = $this->normalizeServiceableMeta($item['serviceable_meta'] ?? null) ?? [];
        $paxType = (string) ($meta['pax_type'] ?? 'adult');
        $isChild = $paxType === 'child';
        $publishColumn = $isChild ? 'child_publish_rate' : 'adult_publish_rate';
        $contractColumn = $isChild ? 'child_contract_rate' : 'adult_contract_rate';
        $markupTypeColumn = $isChild ? 'child_markup_type' : 'adult_markup_type';
        $markupColumn = $isChild ? 'child_markup' : 'adult_markup';

        if ((float) ($foodBeverage->{$publishColumn} ?? 0) > 0) {
            return $this->buildRatePayloadFromMaster(
                (float) ($foodBeverage->{$publishColumn} ?? ($foodBeverage->publish_rate ?? 0)),
                (float) ($foodBeverage->{$contractColumn} ?? ($foodBeverage->contract_rate ?? 0)),
                (string) ($foodBeverage->{$markupTypeColumn} ?? ($foodBeverage->adult_markup_type ?? ($foodBeverage->markup_type ?? 'fixed'))),
                (float) ($foodBeverage->{$markupColumn} ?? ($foodBeverage->adult_markup ?? ($foodBeverage->markup ?? 0)))
            );
        }

        $payload = [
            $publishColumn => $ratePayload['publish_rate'],
            $contractColumn => $ratePayload['contract_rate'],
            $markupTypeColumn => $ratePayload['markup_type'],
            $markupColumn => $ratePayload['markup'],
        ];
        if (! $isChild) {
            $payload['publish_rate'] = $ratePayload['publish_rate'];
            $payload['contract_rate'] = $ratePayload['contract_rate'];
            $payload['markup_type'] = $ratePayload['markup_type'];
            $payload['markup'] = $ratePayload['markup'];
        }

        $foodBeverage->forceFill($payload)->save();

        return $ratePayload;
    }

    /**
     * @return array{publish_rate: float, contract_rate: float, markup_type: string, markup: float, serviceable_meta: array<string, mixed>}|null
     */
    private function syncMissingHotelRoomPublishRate(int $roomId, array $item, array $ratePayload): ?array
    {
        $room = HotelRoom::query()->find($roomId);
        if (! $room) {
            return null;
        }

        $meta = $this->normalizeServiceableMeta($item['serviceable_meta'] ?? null) ?? [];
        $hotelPrice = null;
        $hotelPriceId = (int) ($meta['hotel_price_id'] ?? 0);
        if ($hotelPriceId > 0) {
            $hotelPrice = HotelPrice::query()
                ->whereKey($hotelPriceId)
                ->where('rooms_id', $roomId)
                ->first();
        }
        if (! $hotelPrice) {
            $hotelPrice = HotelPrice::query()
                ->where('rooms_id', $roomId)
                ->orderByDesc('end_date')
                ->orderByDesc('start_date')
                ->orderByDesc('id')
                ->first();
        }
        if ($hotelPrice && (float) ($hotelPrice->publish_rate ?? 0) > 0) {
            $meta['hotel_price_id'] = (int) $hotelPrice->id;

            return array_merge(
                $this->buildRatePayloadFromMaster(
                    (float) ($hotelPrice->publish_rate ?? 0),
                    (float) ($hotelPrice->contract_rate ?? 0),
                    (string) ($hotelPrice->markup_type ?? 'fixed'),
                    (float) ($hotelPrice->markup ?? 0)
                ),
                ['serviceable_meta' => $meta]
            );
        }
        if ((int) ($room->hotels_id ?? 0) <= 0) {
            return null;
        }

        $today = now()->toDateString();
        $endOfYear = now()->endOfYear()->toDateString();
        $payload = [
            'hotels_id' => (int) ($room->hotels_id ?? 0),
            'rooms_id' => $roomId,
            'start_date' => $today,
            'end_date' => $endOfYear,
            'contract_rate' => $ratePayload['contract_rate'],
            'markup_type' => $ratePayload['markup_type'],
            'markup' => $ratePayload['markup'],
            'publish_rate' => $ratePayload['publish_rate'],
        ];

        if ($hotelPrice) {
            $hotelPrice->forceFill($payload)->save();
        } else {
            $hotelPrice = HotelPrice::query()->create($payload);
        }

        $meta['hotel_price_id'] = (int) $hotelPrice->id;

        return array_merge($ratePayload, [
            'serviceable_meta' => $meta,
        ]);
    }

    private function computeTotals(array $items, ?string $discountType, float $discountValue, ?string $quotationServiceDate = null): array
    {
        $subTotal = 0;
        $normalizedItems = [];
        $serviceDateBase = null;
        if (is_string($quotationServiceDate) && trim($quotationServiceDate) !== '') {
            try {
                $serviceDateBase = \Illuminate\Support\Carbon::parse($quotationServiceDate)->startOfDay();
            } catch (\Throwable) {
                $serviceDateBase = null;
            }
        }

        foreach ($items as $item) {
            $item = $this->normalizeQuotationServiceDescription($item);
            $qty = (int) $item['qty'];
            $contractRate = (float) ($item['contract_rate'] ?? 0);
            $markupType = ($item['markup_type'] ?? 'fixed') === 'percent' ? 'percent' : 'fixed';
            $markup = (float) ($item['markup'] ?? 0);
            $providedUnitPrice = max(0, (float) ($item['unit_price'] ?? 0));
            $providedRate = max(0, (float) ($item['rate'] ?? 0));
            $itemType = (string) ($item['itinerary_item_type'] ?? '');
            $isManualItem = $itemType === 'manual';
            $hasRateInput = array_key_exists('rate', $item)
                && $item['rate'] !== null
                && $item['rate'] !== '';
            $hasContractRateInput = array_key_exists('contract_rate', $item)
                && $item['contract_rate'] !== null
                && $item['contract_rate'] !== '';

            $unitPriceFromMarkup = $markupType === 'percent'
                ? ($contractRate + ($contractRate * ($markup / 100)))
                : ($contractRate + $markup);
            if ($hasRateInput) {
                $unitPrice = $providedRate;
            } elseif ($isManualItem) {
                $unitPrice = $providedUnitPrice > 0
                    ? ($qty > 0 ? ($providedUnitPrice / $qty) : $providedUnitPrice)
                    : 0;
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
                'status' => QuotationItem::STATUS_ACTIVE,
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
            $sortOrder = (int) ($item['sort_order'] ?? 0);
            if ($sortOrder > 0) {
                $normalized['sort_order'] = $sortOrder;
            }
            if ($serviceDateBase) {
                $serviceDate = $serviceDateBase->copy();
                if ($dayNumber > 1) {
                    $serviceDate->addDays($dayNumber - 1);
                }
                $normalized['service_date'] = $serviceDate->toDateString();
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

    /**
     * @return array<int, array<string, mixed>>
     */
    private function buildValidatedQuotationItemStateMap(Quotation $quotation): array
    {
        $quotation->loadMissing('items');

        return $quotation->items
            ->filter(fn (QuotationItem $item): bool => (bool) ($item->is_validated ?? false))
            ->mapWithKeys(function (QuotationItem $item): array {
                return [
                    (int) $item->id => [
                        'serviceable_type' => (string) ($item->serviceable_type ?? ''),
                        'serviceable_id' => (int) ($item->serviceable_id ?? 0),
                        'is_validation_required' => (bool) ($item->is_validation_required ?? false),
                        'is_validated' => true,
                        'validated_at' => $item->validated_at,
                        'validated_by' => $item->validated_by,
                        'validation_notes' => $item->validation_notes,
                        'last_validated_contract_rate' => $item->last_validated_contract_rate,
                        'last_validated_markup_type' => $item->last_validated_markup_type,
                        'last_validated_markup' => $item->last_validated_markup,
                        'status' => $item->status,
                    ],
                ];
            })
            ->all();
    }

    /**
     * @param array<int, array<string, mixed>> $normalizedItems
     * @param array<int, array<string, mixed>> $submittedItems
     * @param array<int, array<string, mixed>> $validatedItemStateById
     *
     * @return array<int, array<string, mixed>>
     */
    private function applyValidatedItemStateCarryOver(array $normalizedItems, array $submittedItems, array $validatedItemStateById): array
    {
        foreach ($normalizedItems as $index => $item) {
            $sourceItemId = (int) ($submittedItems[$index]['id'] ?? 0);
            if ($sourceItemId <= 0) {
                $sourceItemId = (int) ($submittedItems[$index]['source_item_id'] ?? 0);
            }
            if ($sourceItemId <= 0 || ! isset($validatedItemStateById[$sourceItemId])) {
                continue;
            }

            $sourceState = $validatedItemStateById[$sourceItemId];
            if (! $this->isSameValidatedServiceItem($item, $sourceState)) {
                continue;
            }

            $normalizedItems[$index] = array_merge($item, [
                'is_validation_required' => (bool) ($sourceState['is_validation_required'] ?? false),
                'is_validated' => true,
                'validated_at' => $sourceState['validated_at'] ?? null,
                'validated_by' => $sourceState['validated_by'] ?? null,
                'validation_notes' => $sourceState['validation_notes'] ?? null,
                'last_validated_contract_rate' => $sourceState['last_validated_contract_rate'] ?? null,
                'last_validated_markup_type' => $sourceState['last_validated_markup_type'] ?? null,
                'last_validated_markup' => $sourceState['last_validated_markup'] ?? null,
                'status' => $sourceState['status'] ?: QuotationItem::STATUS_VALIDATED,
            ]);
        }

        return $normalizedItems;
    }

    /**
     * @param array<string, mixed> $item
     * @param array<string, mixed> $sourceState
     */
    private function isSameValidatedServiceItem(array $item, array $sourceState): bool
    {
        $sourceType = (string) ($sourceState['serviceable_type'] ?? '');
        $sourceId = (int) ($sourceState['serviceable_id'] ?? 0);

        if ($sourceType === '' || $sourceId <= 0) {
            return true;
        }

        return (string) ($item['serviceable_type'] ?? '') === $sourceType
            && (int) ($item['serviceable_id'] ?? 0) === $sourceId;
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

    private function resolveInquiryIdFromItinerary(?int $itineraryId): ?int
    {
        if (! $itineraryId || $itineraryId <= 0) {
            return null;
        }

        $itinerary = Itinerary::query()
            ->with(['inquiryReferences' => function ($query): void {
                $query->select(['inquiries.id'])
                    ->orderByDesc('inquiry_itinerary_references.id');
            }])
            ->select(['id', 'inquiry_id'])
            ->find($itineraryId);
        if (! $itinerary) {
            throw ValidationException::withMessages([
                'itinerary_id' => 'Selected itinerary not found.',
            ]);
        }

        // Primary source-of-truth: inquiry_itinerary_references.
        // Legacy fallback: itineraries.inquiry_id (for backward-compatible historical rows).
        $inquiryId = (int) ($itinerary->inquiryReferences->first()?->id ?? 0);
        if ($inquiryId <= 0) {
            $inquiryId = (int) ($itinerary->inquiry_id ?? 0);
        }
        return $inquiryId > 0 ? $inquiryId : null;
    }

    private function resolveInquiryIdForQuotation(int $customerId, ?int $selectedInquiryId = null): ?int
    {
        if ($customerId <= 0) {
            throw ValidationException::withMessages([
                'customer_id' => 'Customer/Agent is required.',
            ]);
        }

        $manualInquiryId = (int) ($selectedInquiryId ?? 0);
        if ($manualInquiryId <= 0) {
            throw ValidationException::withMessages([
                'inquiry_id' => ui_phrase('Please select inquiry handled by you before generating quotation.'),
            ]);
        }

        return $manualInquiryId;
    }

    private function normalizeOrderNumber(?string $value): ?string
    {
        $trimmed = trim((string) $value);
        if ($trimmed === '') {
            return null;
        }

        return strtoupper($trimmed);
    }

    private function buildRevisionHistory(Quotation $quotation)
    {
        if (Schema::hasTable('quotation_revisions')) {
            $revisionLogs = DB::table('quotation_revisions')
                ->where('quotation_id', (int) $quotation->id)
                ->orderBy('version')
                ->orderBy('id')
                ->get();

            if ($revisionLogs->isNotEmpty()) {
                $original = clone $quotation;
                $original->setAttribute('revision_number', 1);
                $original->setAttribute('revision_reason', null);
                $original->setAttribute('created_at', $quotation->created_at);
                $original->setAttribute('updated_at', $quotation->created_at);

                return collect([$original])
                    ->merge($revisionLogs->map(fn (object $log): Quotation => $this->quotationRevisionHistoryModelFromLog($quotation, $log)))
                    ->map(fn (Quotation $revision): Quotation => $this->decorateRevisionHistoryRow($revision));
            }
        }

        if (! Schema::hasColumn('quotations', 'revision_number')) {
            return collect([$this->decorateRevisionHistoryRow($quotation)]);
        }

        $revisionRootId = (int) ($quotation->revision_of_id ?? 0);
        if ($revisionRootId <= 0) {
            $revisionRootId = (int) $quotation->id;
        }

        return Quotation::query()
            ->where(function (Builder $query) use ($revisionRootId): void {
                $query->where('id', $revisionRootId);
                if (Schema::hasColumn('quotations', 'revision_of_id')) {
                    $query->orWhere('revision_of_id', $revisionRootId);
                }
            })
            ->with(['creator:id,name', 'updater:id,name'])
            ->orderBy('revision_number')
            ->orderBy('id')
            ->get()
            ->map(fn (Quotation $revision): Quotation => $this->decorateRevisionHistoryRow($revision));
    }

    private function buildFollowUpHistory(Quotation $quotation)
    {
        $quotation->loadMissing(['followUps.creator', 'followUps.handler']);

        return $quotation->followUps
            ->map(function (QuotationFollowUp $followUp): QuotationFollowUp {
                $effectiveAt = $followUp->follow_up_at ?? $followUp->created_at;
                $channel = trim((string) ($followUp->channel ?? ''));
                $channelKey = strtolower($channel);
                $followUpType = trim((string) ($followUp->follow_up_type ?? ''));
                if ($followUpType === '') {
                    $followUpType = $channelKey === 'system' ? 'quotation_sent' : 'customer_follow_up';
                }

                $title = match ($followUpType) {
                    'quotation_sent' => ui_phrase('Quotation Sent'),
                    'customer_follow_up' => ui_phrase('Customer Follow-up'),
                    default => \Illuminate\Support\Str::headline(str_replace('_', ' ', $followUpType)),
                };
                $channelLabel = $channelKey === 'system'
                    ? ui_phrase('System')
                    : ($channel !== '' ? $channel : ui_phrase('Manual'));
                $note = trim((string) ($followUp->follow_up_note ?? ''));
                if ($note === '' && $followUpType === 'quotation_sent') {
                    $note = ui_phrase('Quotation sent to customer. Waiting for response.');
                }
                if ($note === '' && $followUpType === 'customer_follow_up') {
                    $note = ui_phrase('Follow-up recorded.');
                }

                $followUp->setAttribute('history_effective_at', $effectiveAt);
                $followUp->setAttribute('history_title', $title);
                $followUp->setAttribute('history_channel_label', $channelLabel);
                $followUp->setAttribute('history_type_key', $followUpType);
                $followUp->setAttribute('history_kind_label', $channelKey === 'system' ? ui_phrase('System') : ui_phrase('Manual'));
                $followUp->setAttribute('history_note', $note);
                $followUp->setAttribute('history_actor_name', ui_user_name($followUp->creator));
                $followUp->setAttribute('history_handler_name', ui_user_name($followUp->handler));
                $followUp->setAttribute(
                    'history_sort_key',
                    (((int) optional($effectiveAt)->getTimestamp()) * 1000000) + (int) ($followUp->id ?? 0)
                );

                return $followUp;
            })
            ->sortBy(fn (QuotationFollowUp $followUp) => (int) ($followUp->getAttribute('history_sort_key') ?? 0), SORT_REGULAR, true)
            ->values();
    }

    private function buildGroupedQuotationItemsByDay(Quotation $quotation)
    {
        $quotation->loadMissing('items');

        return $quotation->items
            ->sort(function ($left, $right) {
                $leftDay = (int) ($left->day_number ?? 0);
                $rightDay = (int) ($right->day_number ?? 0);

                $leftDayRank = $leftDay > 0 ? 0 : 1;
                $rightDayRank = $rightDay > 0 ? 0 : 1;
                if ($leftDayRank !== $rightDayRank) {
                    return $leftDayRank <=> $rightDayRank;
                }

                if ($leftDayRank === 0 && $leftDay !== $rightDay) {
                    return $leftDay <=> $rightDay;
                }

                $leftSortOrder = (int) ($left->sort_order ?? 0);
                $rightSortOrder = (int) ($right->sort_order ?? 0);
                if ($leftSortOrder > 0 && $rightSortOrder > 0 && $leftSortOrder !== $rightSortOrder) {
                    return $leftSortOrder <=> $rightSortOrder;
                }

                $leftMeta = is_array($left->serviceable_meta ?? null) ? $left->serviceable_meta : [];
                $rightMeta = is_array($right->serviceable_meta ?? null) ? $right->serviceable_meta : [];

                $normalizeTimeToMinutes = static function ($value): int {
                    $time = trim((string) $value);
                    if (! preg_match('/^\d{2}:\d{2}$/', $time)) {
                        return PHP_INT_MAX;
                    }

                    return ((int) substr($time, 0, 2) * 60) + (int) substr($time, 3, 2);
                };

                $leftVisitOrder = isset($leftMeta['visit_order']) && is_numeric($leftMeta['visit_order'])
                    ? (int) $leftMeta['visit_order']
                    : PHP_INT_MAX;
                $rightVisitOrder = isset($rightMeta['visit_order']) && is_numeric($rightMeta['visit_order'])
                    ? (int) $rightMeta['visit_order']
                    : PHP_INT_MAX;
                if ($leftVisitOrder !== $rightVisitOrder) {
                    return $leftVisitOrder <=> $rightVisitOrder;
                }

                $leftStartMinutes = $normalizeTimeToMinutes($leftMeta['start_time'] ?? null);
                $rightStartMinutes = $normalizeTimeToMinutes($rightMeta['start_time'] ?? null);
                if ($leftStartMinutes !== $rightStartMinutes) {
                    return $leftStartMinutes <=> $rightStartMinutes;
                }

                return (int) ($left->id ?? 0) <=> (int) ($right->id ?? 0);
            })
            ->groupBy(function ($item) {
                $dayNumber = (int) ($item->day_number ?? 0);

                return $dayNumber > 0 ? $dayNumber : 'without_day';
            })
            ->sortKeysUsing(function ($left, $right) {
                if ($left === 'without_day') {
                    return 1;
                }
                if ($right === 'without_day') {
                    return -1;
                }

                return (int) $left <=> (int) $right;
            });
    }

    private function quotationRevisionHistoryModelFromLog(Quotation $quotation, object $log): Quotation
    {
        $revision = clone $quotation;
        $revision->setAttribute('revision_number', (int) ($log->version ?? 1));
        $revision->setAttribute('revision_reason', (string) ($log->revision_reason ?? ''));
        $revision->setAttribute('created_at', $log->revision_requested_at ?? $log->created_at ?? $quotation->updated_at);
        $revision->setAttribute('updated_at', $log->updated_at ?? $log->revision_requested_at ?? $quotation->updated_at);

        return $revision;
    }

    private function decorateRevisionHistoryRow(Quotation $revision): Quotation
    {
        $revision->loadMissing('items');
        $revisionNumber = max(0, (int) ($revision->revision_number ?? 0));
        $progress = $this->quotationStatusService->validationProgress($revision);
        $status = QuotationStatusNormalizer::normalize((string) ($revision->status ?? 'draft'));
        $linkedResponse = $this->linkedRevisionCustomerResponse($revision);
        $latestLog = $this->latestRevisionWorkflowLog($revision);

        $revision->setAttribute('revision_label', $revisionNumber <= 1 ? ui_phrase('Original') : ui_phrase('Revision') . ' ' . $revisionNumber);
        $revision->setAttribute('revision_status_label', $this->revisionStatusLabel($status, (string) ($revision->validation_status ?? '')));
        $revision->setAttribute('revision_trigger_label', $this->revisionTriggerLabel($revision, $linkedResponse, $latestLog));
        $revision->setAttribute('revision_started_by_name', ui_user_name($revision->creator));
        $revision->setAttribute('revision_started_at', $revision->created_at);
        $revision->setAttribute('revision_finished_by_name', $latestLog?->changed_by ? $this->userNameById((int) $latestLog->changed_by) : ui_user_name($revision->updater));
        $revision->setAttribute('revision_finished_at', $latestLog?->changed_at ?? $revision->updated_at);
        $revision->setAttribute('revision_customer_response', $linkedResponse);
        $revision->setAttribute('revision_progress_text', sprintf(
            '%d%%, %d of %d items validated',
            (int) ($progress['percent'] ?? 0),
            (int) ($progress['validated'] ?? 0),
            (int) ($progress['required'] ?? 0)
        ));
        $revision->setAttribute('revision_changed_summary', $this->revisionChangedSummary($revision, $progress));

        return $revision;
    }

    private function linkedRevisionCustomerResponse(Quotation $revision): ?QuotationCustomerResponse
    {
        if (! Schema::hasTable('quotation_customer_responses')) {
            return null;
        }

        if (Schema::hasColumn('quotation_customer_responses', 'quotation_revision_id')) {
            $linked = QuotationCustomerResponse::query()
                ->where('quotation_revision_id', (int) $revision->id)
                ->latest('response_at')
                ->latest('id')
                ->first();
            if ($linked) {
                return $linked;
            }
        }

        return QuotationCustomerResponse::query()
            ->where('quotation_id', (int) ($revision->revision_of_id ?: $revision->id))
            ->where('requires_revision', true)
            ->where('response_at', '<=', $revision->created_at ?? now())
            ->latest('response_at')
            ->latest('id')
            ->first();
    }

    private function latestRevisionWorkflowLog(Quotation $revision): ?object
    {
        if (! Schema::hasTable('quotation_status_logs')) {
            return null;
        }

        return DB::table('quotation_status_logs')
            ->where('quotation_id', (int) $revision->id)
            ->whereIn('action', ['start_revision', 'save_quotation_revision', 'validation_finalize', 'customer_response_revision_requested'])
            ->orderByDesc('changed_at')
            ->orderByDesc('id')
            ->first();
    }

    private function revisionStatusLabel(string $status, string $validationStatus): string
    {
        return match ($status) {
            'under_revision' => ui_phrase('Under Revision'),
            'need_revalidation' => ui_phrase('Waiting Revalidation'),
            'ready_to_send' => ui_phrase('Ready To Send'),
            'sent' => ui_phrase('Sent to Customer'),
            'approved' => ui_phrase('Approved'),
            default => $validationStatus === QuotationValidationService::STATUS_VALID
                ? ui_phrase('Ready To Send')
                : ui_phrase('Original Quotation'),
        };
    }

    private function revisionTriggerLabel(Quotation $revision, ?QuotationCustomerResponse $response, ?object $latestLog): string
    {
        if ($response && (bool) ($response->requires_revision ?? false)) {
            return ui_phrase('Customer response request revision');
        }
        if (! empty($revision->revision_reason)) {
            return (string) $revision->revision_reason;
        }
        if ($latestLog && ! empty($latestLog->action)) {
            return ui_phrase(Str::headline((string) $latestLog->action));
        }

        return ((int) ($revision->revision_number ?? 1)) <= 1
            ? ui_phrase('Original quotation')
            : ui_phrase('Manual edit');
    }

    private function revisionChangedSummary(Quotation $revision, array $progress): string
    {
        $items = $revision->relationLoaded('items') ? $revision->items : collect();
        $required = (int) ($progress['required'] ?? 0);
        $validated = (int) ($progress['validated'] ?? 0);
        $pending = max(0, $required - $validated);
        $totalItems = $items->count();

        return sprintf(
            '%d items, %d need revalidation, %d validated',
            $totalItems,
            $pending,
            $validated
        );
    }

    private function userNameById(int $userId): string
    {
        if ($userId <= 0 || ! Schema::hasTable('users')) {
            return '-';
        }

        return (string) (User::query()->whereKey($userId)->value('name') ?: '-');
    }

    private function writeInPlaceRevisionLog(Quotation $quotation, int $revisionNumber, ?string $revisionReason, int $actorId): void
    {
        if (! Schema::hasTable('quotation_revisions')) {
            return;
        }

        DB::table('quotation_revisions')->insert([
            'quotation_id' => (int) $quotation->id,
            'parent_quotation_id' => (int) ($quotation->revision_of_id ?: $quotation->id),
            'created_from_revision_id' => (int) $quotation->id,
            'quotation_number' => (string) ($quotation->quotation_number ?? ''),
            'version' => $revisionNumber,
            'revision_reason' => $revisionReason,
            'revision_requested_by' => $actorId > 0 ? $actorId : null,
            'revision_requested_at' => now(),
            'created_by' => $actorId > 0 ? $actorId : null,
            'metadata' => json_encode([
                'mode' => 'in_place',
                'status' => (string) ($quotation->status ?? ''),
                'validation_status' => (string) ($quotation->validation_status ?? ''),
            ]),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function availableItinerariesQuery(?int $includeItineraryId = null): Builder
    {
        return Itinerary::query()
            ->with(['inquiryReferences' => function ($query): void {
                $query->select([
                    'inquiries.id',
                    'inquiries.inquiry_number',
                    'inquiries.customer_id',
                    'inquiries.status',
                    'inquiries.priority',
                    'inquiries.source',
                    'inquiries.deadline',
                    'inquiries.notes',
                ])
                    ->with(['customer:id,name,company_name', 'creator:id,name'])
                    ->orderByDesc('inquiry_itinerary_references.id');
            }])
            ->where('is_active', true);
    }

    private function availableInquiriesQuery(?int $includeInquiryId = null, ?int $currentQuotationId = null): Builder
    {
        $user = auth()->user();
        $query = Inquiry::query()->with([
            'customer',
            'creator',
            'itineraries' => function ($query): void {
                $query->select(['itineraries.id'])
                    ->orderByDesc('itineraries.id');
            },
        ])->withCount('quotation');
        $hasIncludedInquiry = $includeInquiryId && $includeInquiryId > 0;
        $today = now()->toDateString();

        if ($hasIncludedInquiry) {
            $query->where(function (Builder $builder) use ($today, $includeInquiryId): void {
                $builder->whereDate('deadline', '>=', $today)
                    ->orWhereNull('deadline')
                    ->orWhere('id', $includeInquiryId);
            });
        } else {
            $query->where(function (Builder $builder) use ($today): void {
                $builder->whereDate('deadline', '>=', $today)
                    ->orWhereNull('deadline');
            });
        }

        if (Schema::hasColumn('inquiries', 'status')) {
            if ($hasIncludedInquiry) {
                $query->where(function (Builder $builder) use ($includeInquiryId): void {
                    $builder->where('status', '!=', Inquiry::FINAL_STATUS)
                        ->orWhere('id', $includeInquiryId);
                });
            } else {
                $query->where('status', '!=', Inquiry::FINAL_STATUS);
            }
        }

        if ($user && (Schema::hasColumn('inquiries', 'handled_by') || Schema::hasColumn('inquiries', 'assigned_to'))) {
            $query->where(function (Builder $handlerQuery) use ($user): void {
                if (Schema::hasColumn('inquiries', 'handled_by')) {
                    $handlerQuery->whereNull('handled_by')
                        ->orWhere('handled_by', (int) $user->id);
                }

                if (Schema::hasColumn('inquiries', 'assigned_to')) {
                    Schema::hasColumn('inquiries', 'handled_by')
                        ? $handlerQuery->orWhere('assigned_to', (int) $user->id)
                        : $handlerQuery->whereNull('assigned_to')->orWhere('assigned_to', (int) $user->id);
                }
            });
        }

        $currentQuotationId = $currentQuotationId && $currentQuotationId > 0 ? $currentQuotationId : null;
        $query->whereDoesntHave('quotation', function (Builder $quotationQuery) use ($currentQuotationId): void {
            if ($currentQuotationId) {
                $quotationQuery->whereKeyNot($currentQuotationId);
            }
        });

        return $query;
    }

    private function assertInquiryEligibleForQuotationGeneration(int $inquiryId, ?int $currentQuotationId = null): void
    {
        $user = auth()->user();
        if (! $user) {
            throw ValidationException::withMessages([
                'inquiry_id' => ui_phrase('You do not have permission to handle this inquiry.'),
            ]);
        }

        $inquiry = Inquiry::query()->find($inquiryId);
        if (! $inquiry) {
            throw ValidationException::withMessages([
                'inquiry_id' => ui_phrase('Selected inquiry was not found.'),
            ]);
        }

        if (Schema::hasColumn('inquiries', 'status') && (string) ($inquiry->status ?? '') === Inquiry::FINAL_STATUS) {
            throw ValidationException::withMessages([
                'inquiry_id' => ui_phrase('Selected inquiry is final and cannot be used for quotation generation.'),
            ]);
        }

        $currentQuotationId = $currentQuotationId && $currentQuotationId > 0 ? $currentQuotationId : null;
        $hasOtherQuotation = $inquiry->quotation()
            ->when($currentQuotationId, function (Builder $query) use ($currentQuotationId): void {
                $query->whereKeyNot($currentQuotationId);
            })
            ->exists();
        if ($hasOtherQuotation) {
            throw ValidationException::withMessages([
                'inquiry_id' => ui_phrase('Selected inquiry already has a quotation and cannot be used for another quotation.'),
            ]);
        }

        $handledBy = Schema::hasColumn('inquiries', 'handled_by')
            ? (int) ($inquiry->handled_by ?? 0)
            : 0;
        $assignedTo = Schema::hasColumn('inquiries', 'assigned_to')
            ? (int) ($inquiry->assigned_to ?? 0)
            : 0;

        $isUnHandled = $handledBy <= 0 && $assignedTo <= 0;
        $isOwnedByUser = $handledBy === (int) $user->id
            || ($handledBy <= 0 && $assignedTo === (int) $user->id);

        if (! $isUnHandled && ! $isOwnedByUser) {
            throw ValidationException::withMessages([
                'inquiry_id' => ui_phrase('Selected inquiry must be handled by you or still unhandled.'),
            ]);
        }
    }

    private function assertAndClaimInquiryHandler(?int $inquiryId): void
    {
        $user = auth()->user();
        if (
            ! $user
            || ! $inquiryId
            || $inquiryId <= 0
            || (! Schema::hasColumn('inquiries', 'handled_by') && ! Schema::hasColumn('inquiries', 'assigned_to'))
        ) {
            return;
        }

        if (! $user->hasAnyRole(['Reservation', 'Manager', 'Director'])) {
            throw ValidationException::withMessages([
                'inquiry_id' => ui_phrase('Only reservation, manager, or director can handle inquiry.'),
            ]);
        }

        $inquiry = Inquiry::query()->find($inquiryId);
        if (! $inquiry) {
            throw ValidationException::withMessages([
                'inquiry_id' => ui_phrase('Selected inquiry was not found.'),
            ]);
        }

        $currentHandler = (int) ($inquiry->handled_by ?? $inquiry->assigned_to ?? 0);
        if ($currentHandler > 0 && $currentHandler !== (int) $user->id) {
            throw ValidationException::withMessages([
                'inquiry_id' => ui_phrase('Inquiry is already handled by another user.'),
            ]);
        }

        if ($currentHandler <= 0) {
            $updatePayload = [];
            if (Schema::hasColumn('inquiries', 'handled_by')) {
                $updatePayload['handled_by'] = (int) $user->id;
            }
            if (Schema::hasColumn('inquiries', 'assigned_to')) {
                $updatePayload['assigned_to'] = (int) $user->id;
            }
            if ($updatePayload !== []) {
                $inquiry->update($updatePayload);
            }
        }
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

        if (! $user->can($ability, $quotation)) {
            return false;
        }

        if (Schema::hasColumn('quotations', 'created_by')
            && (int) ($quotation->created_by ?? 0) === (int) $user->id) {
            return true;
        }

        $inquiry = $quotation->inquiry;
        $handlerId = 0;
        if (Schema::hasColumn('quotations', 'handled_by')) {
            $handlerId = (int) ($quotation->handled_by ?? 0);
        }
        if ($handlerId <= 0 && $inquiry && Schema::hasColumn('inquiries', 'handled_by')) {
            $handlerId = (int) ($inquiry->handled_by ?? 0);
        }
        if ($handlerId <= 0 && $inquiry && Schema::hasColumn('inquiries', 'assigned_to')) {
            $handlerId = (int) ($inquiry->assigned_to ?? 0);
        }
        if ($handlerId <= 0 && Schema::hasColumn('quotations', 'created_by')) {
            $handlerId = (int) ($quotation->created_by ?? 0);
        }
        if ($handlerId <= 0 && $inquiry && Schema::hasColumn('inquiries', 'created_by')) {
            $handlerId = (int) ($inquiry->created_by ?? 0);
        }

        return $handlerId > 0 && $handlerId === (int) $user->id;
    }

    private function denyQuotationMutation(Quotation $quotation)
    {
        return redirect()
            ->route('quotations.show', $quotation)
            ->with('error', 'You do not have permission to modify this quotation.');
    }

    private function cancelQuotationItem(Request $request, Quotation $quotation, QuotationItem $item, string $targetStatus)
    {
        if ((int) ($item->quotation_id ?? 0) !== (int) ($quotation->id ?? 0)) {
            abort(404);
        }
        if (! $this->canManageQuotation($quotation, 'update')) {
            return $this->denyQuotationMutation($quotation);
        }

        $currentStatus = (string) ($item->status ?? QuotationItem::STATUS_ACTIVE);
        if (in_array($currentStatus, [QuotationItem::STATUS_USED, QuotationItem::STATUS_CANCELLED_FREE, QuotationItem::STATUS_CANCELLED_WITH_CHARGE], true)) {
            return redirect()->route('quotations.show', $quotation)->with('error', ui_phrase('Quotation item status is locked.'));
        }

        $validated = [];
        if ($targetStatus === QuotationItem::STATUS_CANCELLED_WITH_CHARGE) {
            $validated = $request->validate([
                'cancellation_fee_type' => ['required', Rule::in(['fixed', 'percent'])],
                'cancellation_fee_value' => ['required', 'numeric', 'min:0'],
                'cancellation_reason' => ['nullable', 'string', 'max:2000'],
            ]);
        } else {
            $validated = $request->validate([
                'cancellation_reason' => ['nullable', 'string', 'max:2000'],
            ]);
        }

        $feeType = $targetStatus === QuotationItem::STATUS_CANCELLED_WITH_CHARGE
            ? (string) ($validated['cancellation_fee_type'] ?? 'fixed')
            : null;
        $feeValue = $targetStatus === QuotationItem::STATUS_CANCELLED_WITH_CHARGE
            ? (float) ($validated['cancellation_fee_value'] ?? 0)
            : null;

        $baseAmount = max(0, (float) (($item->qty ?? 0) * ($item->unit_price ?? 0)));
        $feeAmount = null;
        if ($targetStatus === QuotationItem::STATUS_CANCELLED_WITH_CHARGE) {
            $feeAmount = $feeType === 'percent'
                ? max(0, $baseAmount * ($feeValue / 100))
                : max(0, $feeValue);
        }

        $item->update([
            'status' => $targetStatus,
            'cancellation_fee_type' => $feeType,
            'cancellation_fee_value' => $feeValue,
            'cancellation_fee_amount' => $feeAmount,
            'cancellation_reason' => trim((string) ($validated['cancellation_reason'] ?? '')) ?: null,
        ]);

        $quotation->logActivity('quotation_item_status_changed', $item, [
            'quotation_id' => (int) $quotation->id,
            'quotation_item_id' => (int) $item->id,
            'from_status' => $currentStatus,
            'to_status' => $targetStatus,
            'cancellation_fee_type' => $feeType,
            'cancellation_fee_value' => $feeValue,
            'cancellation_fee_amount' => $feeAmount,
        ]);
        $this->quotationWorkflowService->syncDimensions($quotation, (int) ($request->user()?->id ?? 0) ?: null, [
            'action' => 'quotation_item_cancelled',
            'quotation_item_id' => (int) $item->id,
            'item_status' => $targetStatus,
        ]);

        return redirect()
            ->route('quotations.show', $quotation)
            ->with('success', ui_phrase('Quotation item status updated.'));
    }

    public function itineraryItems(Itinerary $itinerary)
    {
        $items = collect($this->itineraryQuotationService->buildItems($itinerary))
            ->map(fn (array $item): array => $this->normalizeQuotationServiceDescription($item))
            ->values()
            ->all();
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
        // Status data cleanup must be explicit through quotations:normalize-status.
    }

    private function autoFinalizeApprovedQuotationIfExpired(Quotation $quotation): bool
    {
        // Status data cleanup must be explicit through quotations:normalize-status.
        return false;
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
            $currentItineraryInquiryId = $this->resolveInquiryIdFromItinerary((int) $quotation->itinerary_id);
            if ($currentItineraryInquiryId) {
                $inquiryIds->push((int) $currentItineraryInquiryId);
            }
        }

        if ($previousItineraryId) {
            $previousItineraryInquiryId = $this->resolveInquiryIdFromItinerary((int) $previousItineraryId);
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
            return 'quotation_in_progress';
        }

        $linkedQuotationScope = function (Builder $query) use ($inquiryId): void {
            $query->where('inquiry_id', $inquiryId)
                ->orWhereHas('itinerary', function (Builder $itineraryQuery) use ($inquiryId): void {
                    $itineraryQuery->whereHas('inquiryReferences', function (Builder $inquiryQuery) use ($inquiryId): void {
                        $inquiryQuery->where('inquiries.id', $inquiryId);
                    });
                });
        };

        $hasFinalQuotation = Quotation::query()
            ->whereNull('deleted_at')
            ->where('status', Quotation::FINAL_STATUS)
            ->where($linkedQuotationScope)
            ->exists();
        if ($hasFinalQuotation) {
            return Inquiry::FINAL_STATUS;
        }

        $hasActiveQuotation = Quotation::query()
            ->whereNull('deleted_at')
            ->whereNotIn('status', [Quotation::STATUS_CANCELLED, Quotation::STATUS_LOST])
            ->where($linkedQuotationScope)
            ->exists();
        if ($hasActiveQuotation) {
            return 'quotation_in_progress';
        }

        $hasCancelledQuotation = Quotation::query()
            ->whereNull('deleted_at')
            ->where('status', Quotation::STATUS_CANCELLED)
            ->where($linkedQuotationScope)
            ->exists();
        if ($hasCancelledQuotation) {
            return 'cancelled';
        }

        $hasLostQuotation = Quotation::query()
            ->whereNull('deleted_at')
            ->where('status', Quotation::STATUS_LOST)
            ->where($linkedQuotationScope)
            ->exists();

        return $hasLostQuotation ? 'lost' : 'quotation_in_progress';
    }

    private function syncInquiryStatusFromQuotation(Quotation $quotation, string $quotationStatus): void
    {
        if (! Schema::hasTable('inquiries') || ! Schema::hasColumn('inquiries', 'status')) {
            return;
        }

        $inquiryId = (int) ($quotation->inquiry_id ?? 0);
        if ($inquiryId <= 0) {
            return;
        }

        $targetInquiryStatus = match (Quotation::normalizeStatus($quotationStatus)) {
            Quotation::STATUS_CANCELLED => 'cancelled',
            Quotation::STATUS_LOST => 'lost',
            default => null,
        };
        if ($targetInquiryStatus === null || ! in_array($targetInquiryStatus, Inquiry::STATUS_OPTIONS, true)) {
            return;
        }

        Inquiry::query()
            ->where('id', $inquiryId)
            ->whereNotIn('status', [Inquiry::FINAL_STATUS])
            ->update(['status' => $targetInquiryStatus]);
    }

    private function syncInquiryItineraryReferenceFromQuotation(Quotation $quotation): void
    {
        $inquiryId = (int) ($quotation->inquiry_id ?? 0);
        $itineraryId = (int) ($quotation->itinerary_id ?? 0);
        if ($inquiryId <= 0 || $itineraryId <= 0) {
            return;
        }

        // An itinerary can be reused by many quotations, so the pivot acts as
        // the itinerary's primary inquiry reference only. Quotation saves must
        // not overwrite an existing primary reference owned by another inquiry.
        $existingReference = DB::table('inquiry_itinerary_references')
            ->where('itinerary_id', $itineraryId)
            ->first(['id', 'inquiry_id']);
        $timestamp = now();
        $actorId = (int) (auth()->id() ?? 0) ?: null;

        if ($existingReference) {
            if ((int) ($existingReference->inquiry_id ?? 0) !== $inquiryId) {
                return;
            }

            DB::table('inquiry_itinerary_references')
                ->where('id', (int) $existingReference->id)
                ->update([
                    'created_by' => $actorId,
                    'updated_at' => $timestamp,
                ]);

            return;
        }

        DB::table('inquiry_itinerary_references')->insertOrIgnore([
            'inquiry_id' => $inquiryId,
            'itinerary_id' => $itineraryId,
            'created_by' => $actorId,
            'created_at' => $timestamp,
            'updated_at' => $timestamp,
        ]);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function buildQuotationIndexViewData(
        Builder $query,
        array $statsCards,
        bool $isMyQuotationPage,
        string $listRouteName,
        string $exportScope,
        array $statusFilterOptions
    ): array {
        $bookingsModuleEnabled = ModuleService::isEnabledStatic('bookings');
        $perPage = (int) request('per_page', 10);
        $perPage = $perPage > 0 && $perPage <= 100 ? $perPage : 10;

        $today = now()->toDateString();
        $finalStatusAliases = $this->quotationStatusAliasesForQuery(Quotation::FINAL_STATUS);
        $nonFinalQuery = (clone $query)->whereNotIn('status', $finalStatusAliases);
        $upcomingQuotations = (clone $nonFinalQuery)
            ->where(function (Builder $builder) use ($today): void {
                $builder->whereDate('service_date', '>=', $today)
                    ->orWhereNull('service_date');
            })
            ->latest()
            ->paginate($perPage, ['*'], 'upcoming_page')
            ->withQueryString();
        $expiredQuotations = (clone $nonFinalQuery)
            ->whereDate('service_date', '<', $today)
            ->latest()
            ->paginate($perPage, ['*'], 'expired_page')
            ->withQueryString();
        $finalQuotations = (clone $query)
            ->whereIn('status', $finalStatusAliases)
            ->latest()
            ->paginate($perPage, ['*'], 'final_page')
            ->withQueryString();

        $upcomingQuotations->getCollection()->transform(
            fn (Quotation $quotation) => $this->transformQuotationForIndex($quotation)
        );
        $expiredQuotations->getCollection()->transform(
            fn (Quotation $quotation) => $this->transformQuotationForIndex($quotation)
        );
        $finalQuotations->getCollection()->transform(
            fn (Quotation $quotation) => $this->transformQuotationForIndex($quotation)
        );

        $quotationSections = [
            [
                'key' => 'upcoming',
                'title' => ui_phrase('Upcoming Quotations'),
                'paginator' => $upcomingQuotations,
            ],
            [
                'key' => 'passed',
                'title' => ui_phrase('Passed Quotation'),
                'paginator' => $expiredQuotations,
            ],
            [
                'key' => 'final',
                'title' => $bookingsModuleEnabled ? ui_phrase('Converted Quotations') : ui_phrase('Final Quotations'),
                'paginator' => $finalQuotations,
            ],
        ];

        $availableTabKeys = collect($quotationSections)->pluck('key')->all();
        $activeQuotationTab = (string) request('tab', 'upcoming');
        if (! in_array($activeQuotationTab, $availableTabKeys, true)) {
            $activeQuotationTab = 'upcoming';
        }

        $activeQuotationSection = collect($quotationSections)->firstWhere('key', $activeQuotationTab);
        $activeQuotationSection = is_array($activeQuotationSection) ? $activeQuotationSection : $quotationSections[0];

        $quotationTabs = collect($quotationSections)
            ->map(function (array $section) use ($listRouteName, $activeQuotationTab): array {
                return [
                    'key' => (string) ($section['key'] ?? ''),
                    'label' => (string) ($section['title'] ?? ''),
                    'url' => route(
                        $listRouteName,
                        array_merge(request()->except(['upcoming_page', 'expired_page', 'final_page']), [
                            'tab' => $section['key'],
                        ])
                    ),
                    'is_active' => (string) ($section['key'] ?? '') === $activeQuotationTab,
                ];
            })
            ->all();

        return [
            'isMyQuotationPage' => $isMyQuotationPage,
            'listRouteName' => $listRouteName,
            'exportScope' => $exportScope,
            'bookingsModuleEnabled' => $bookingsModuleEnabled,
            'statusFilterOptions' => $this->filterQuotationIndexStatusOptions($statusFilterOptions, $bookingsModuleEnabled),
            'quotationMetrics' => $this->buildQuotationIndexMetrics($statsCards),
            'quotationTabs' => $quotationTabs,
            'activeQuotationTab' => $activeQuotationTab,
            'activeQuotationSection' => $activeQuotationSection,
            'activeQuotationRows' => $this->buildQuotationIndexRows(
                $activeQuotationSection['paginator'],
                $bookingsModuleEnabled
            ),
            'perPageOptions' => [10, 25, 50, 100],
            'canManageActivationActions' => auth()->user()?->canManageActivationActions() === true,
        ];
    }

    private function transformQuotationForIndex(Quotation $quotation): Quotation
    {
        $quotation->setAttribute('status', Quotation::normalizeStatus((string) ($quotation->status ?? '')));
        $quotation->setAttribute('needs_my_approval_badge', false);
        $kpiSummary = $this->computeQuotationKpiSummary($quotation);
        $quotation->setAttribute('display_final_amount', (float) ($kpiSummary['final_amount'] ?? 0));

        return $quotation;
    }

    private function buildQuotationIndexRows($paginator, bool $bookingsModuleEnabled): array
    {
        $user = auth()->user();
        $firstItem = (int) ($paginator->firstItem() ?? 1);

        return $paginator->getCollection()->values()->map(function (Quotation $quotation, int $index) use ($user, $firstItem, $bookingsModuleEnabled): array {
            $status = Quotation::normalizeStatus((string) ($quotation->status ?? ''));
            $nonEditableStatuses = [
                Quotation::STATUS_SENT,
                Quotation::STATUS_CUSTOMER_APPROVED,
                Quotation::FINAL_STATUS,
                Quotation::STATUS_IN_OPERATION,
                Quotation::STATUS_COMPLETED,
            ];
            $pdfStatuses = [
                Quotation::STATUS_CUSTOMER_APPROVED,
                Quotation::FINAL_STATUS,
            ];

            return [
                'quotation' => $quotation,
                'row_number' => $firstItem + $index,
                'order_number' => trim((string) ($quotation->order_number ?? '')) !== ''
                    ? (string) $quotation->order_number
                    : '-',
                'itinerary_label' => trim((string) ($quotation->itinerary?->title ?? '')) !== ''
                    ? (string) $quotation->itinerary->title
                    : '-',
                'itinerary_id' => (int) ($quotation->itinerary_id ?? 0),
                'creator' => $quotation->creator,
                'handled_by_name' => trim((string) ($quotation->inquiry?->handledBy?->name ?? '')) !== ''
                    ? (string) $quotation->inquiry->handledBy->name
                    : '-',
                'display_final_amount' => (float) ($quotation->display_final_amount ?? 0),
                'status_badge' => $quotation->trashed()
                    ? 'inactive'
                    : $this->resolveQuotationIndexDisplayStatus($status, $bookingsModuleEnabled),
                'show_url' => route('quotations.show', $quotation),
                'edit_url' => route('quotations.edit', $quotation),
                'pdf_url' => route('quotations.pdf', $quotation),
                'toggle_url' => route('quotations.toggle-status', $quotation->id),
                'can_edit' => (bool) ($user?->can('update', $quotation) ?? false)
                    && ! in_array($status, $nonEditableStatuses, true),
                'can_open_pdf' => in_array($status, $pdfStatuses, true),
                'can_delete' => (bool) ($user?->can('delete', $quotation) ?? false)
                    && ! in_array($status, $pdfStatuses, true)
                    && ($user?->canManageActivationActions() === true),
            ];
        })->all();
    }

    private function buildQuotationIndexMetrics(array $statsCards): array
    {
        $statsByKey = collect($statsCards)->keyBy('key');

        return [
            'total' => (int) ($statsByKey->get('total')['value'] ?? 0),
            'need_validation' => (int) ($statsByKey->get('need_validation')['value'] ?? 0),
            'sent' => (int) ($statsByKey->get('sent')['value'] ?? 0),
            'approved' => (int) ($statsByKey->get('approved')['value'] ?? 0),
        ];
    }

    private function filterQuotationIndexStatusOptions(array $statusFilterOptions, bool $bookingsModuleEnabled): array
    {
        return collect($statusFilterOptions)
            ->reject(fn ($status) => ! $bookingsModuleEnabled && in_array((string) $status, [
                'converted_to_booking',
                'booking_created',
                'booking_in_progress',
                'booking_issue',
            ], true))
            ->values()
            ->all();
    }

    private function resolveQuotationIndexDisplayStatus(string $status, bool $bookingsModuleEnabled): string
    {
        if (! $bookingsModuleEnabled && in_array($status, [
            'converted_to_booking',
            'booking_created',
            'booking_in_progress',
            'booking_issue',
        ], true)) {
            return 'approved';
        }

        return $status;
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
            $query->whereIn('status', collect($statusScope)->flatMap(fn (string $status): array => $this->quotationStatusAliasesForQuery($status))->unique()->values()->all());
        }

        $rawCounts = $query
            ->select('status', DB::raw('COUNT(*) as total'))
            ->groupBy('status')
            ->pluck('total', 'status');
        $counts = collect();
        foreach ($rawCounts as $status => $total) {
            $logicalStatus = QuotationStatusNormalizer::normalize((string) $status);
            $counts[$logicalStatus] = (int) ($counts[$logicalStatus] ?? 0) + (int) $total;
        }

        $cards = [[
            'key' => 'total',
            'label' => 'Total',
            'value' => (int) $counts->sum(),
            'caption' => 'Total',
            'tone' => 'bg-slate-50 text-slate-700 border-slate-100',
        ]];

        $preferredOrder = ['need_validation', 'ready_to_send', 'sent', 'under_revision', 'need_revalidation', 'approved', 'converted_to_booking', 'draft', 'lost', 'cancelled'];
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
        if (mb_strlen($term) < 3) {
            $query->whereRaw('1 = 0');
            return;
        }

        $query->where(function (Builder $builder) use ($term): void {
            $builder->where('quotation_number', 'like', "%{$term}%")
                ->orWhere('order_number', 'like', "%{$term}%")
                ->orWhereHas('inquiry', function ($inquiryQuery) use ($term) {
                    $inquiryQuery->where('inquiry_number', 'like', "%{$term}%")
                        ->orWhereHas('customer', function ($customerQuery) use ($term) {
                            $customerQuery->where('name', 'like', "%{$term}%");
                        });
                });
        });
    }

    /**
     * @return array<int, string>
     */
    private function quotationStatusAliasesForQuery(string $status): array
    {
        $logicalStatus = QuotationStatusNormalizer::normalize($status);

        return match ($logicalStatus) {
            Quotation::STATUS_NEED_VALIDATION => [Quotation::STATUS_NEED_VALIDATION, Quotation::STATUS_PENDING_VALIDATION],
            Quotation::STATUS_NEED_REVALIDATION => [Quotation::STATUS_NEED_REVALIDATION, Quotation::STATUS_PENDING_REVALIDATION],
            Quotation::STATUS_READY_TO_SEND => [Quotation::STATUS_READY_TO_SEND, Quotation::STATUS_VALIDATED, 'valid'],
            Quotation::STATUS_APPROVED => [Quotation::STATUS_APPROVED, Quotation::STATUS_CUSTOMER_APPROVED, 'accepted'],
            Quotation::STATUS_CONVERTED_TO_BOOKING => [Quotation::STATUS_CONVERTED_TO_BOOKING, Quotation::STATUS_BOOKING_CREATED, 'converted'],
            Quotation::STATUS_REJECTED => [Quotation::STATUS_REJECTED],
            default => [$logicalStatus],
        };
    }

    private function applyQuotationStatusFilter(Builder $query, string $status): void
    {
        $status = strtolower(trim($status));
        if ($status === '') {
            return;
        }

        if (! in_array($status, array_merge(Quotation::STATUS_OPTIONS, Quotation::LEGACY_STATUS_OPTIONS, ['accepted', 'converted', 'valid']), true)) {
            return;
        }

        $query->whereIn('status', $this->quotationStatusAliasesForQuery($status));
    }

    /**
     * @param  array<string, mixed>  $validationProgress
     * @return array<string, mixed>
     */
    private function buildQuotationWorkflowOverview(
        Quotation $quotation,
        array $validationProgress,
        bool $bookingsModuleEnabled,
        bool $invoicesModuleEnabled
    ): array {
        $quotationStatus = Quotation::normalizeStatus((string) ($quotation->status ?? ''));
        $logicalQuotationStatus = QuotationStatusNormalizer::normalize($quotationStatus);
        $validationStatus = (string) ($quotation->validation_status ?? ($validationProgress['status'] ?? 'pending'));
        $booking = $quotation->booking;
        $latestInvoice = $booking?->invoices
            ? $booking->invoices->sortByDesc('id')->first()
            : $booking?->invoice;
        $invoiceStatus = $latestInvoice ? (string) ($latestInvoice->status ?? 'issued') : 'not_created';
        $bookingStatus = $booking ? (string) ($booking->status ?? 'created') : 'not_created';
        $operationStatus = $this->deriveQuotationOperationStatus($bookingStatus);
        $paymentStatus = $this->deriveQuotationPaymentStatus($latestInvoice);
        $approvalStatus = $this->deriveQuotationApprovalStatus($quotation);
        $displayLogicalQuotationStatus = $this->displayQuotationStatusForWorkflow(
            $logicalQuotationStatus,
            $bookingsModuleEnabled
        );
        $currentStage = $this->deriveQuotationCurrentStage(
            $quotationStatus,
            $validationStatus,
            $approvalStatus,
            $bookingStatus,
            $invoiceStatus,
            $paymentStatus,
            $operationStatus,
            $bookingsModuleEnabled,
            $invoicesModuleEnabled
        );

        $responsibleUser = $quotation->inquiry?->handledBy
            ?? $quotation->inquiry?->assignedTo
            ?? $quotation->creator
            ?? $quotation->validatedBy
            ?? $quotation->approvedBy;

        $risks = $this->buildQuotationRiskIndicators(
            $quotation,
            $validationProgress,
            $invoiceStatus,
            $paymentStatus,
            $bookingStatus,
            $bookingsModuleEnabled,
            $invoicesModuleEnabled
        );
        $nextAction = $this->deriveQuotationNextAction(
            $quotation,
            $quotationStatus,
            $validationStatus,
            $approvalStatus,
            $bookingStatus,
            $invoiceStatus,
            $paymentStatus,
            $operationStatus,
            $bookingsModuleEnabled,
            $invoicesModuleEnabled
        );

        $statusCards = [
            [
                'label' => ui_phrase('Current Status'),
                'value' => $displayLogicalQuotationStatus,
                'tone' => $this->workflowToneForStatus($displayLogicalQuotationStatus),
            ],
            [
                'label' => ui_phrase('Validation Status'),
                'value' => $validationStatus,
                'tone' => $this->workflowToneForStatus($validationStatus),
            ],
            [
                'label' => ui_phrase('Approval Status'),
                'value' => $approvalStatus,
                'tone' => $this->workflowToneForStatus($approvalStatus),
            ],
        ];

        if ($bookingsModuleEnabled) {
            $statusCards[] = [
                'label' => ui_phrase('Booking Status'),
                'value' => $bookingStatus,
                'tone' => $this->workflowToneForStatus($bookingStatus),
            ];
        }

        if ($invoicesModuleEnabled) {
            $statusCards[] = [
                'label' => ui_phrase('Invoice Status'),
                'value' => $invoiceStatus,
                'tone' => $this->workflowToneForStatus($invoiceStatus),
            ];
            $statusCards[] = [
                'label' => ui_phrase('Payment Status'),
                'value' => $paymentStatus,
                'tone' => $this->workflowToneForStatus($paymentStatus),
            ];
        }

        if ($bookingsModuleEnabled) {
            $statusCards[] = [
                'label' => ui_phrase('Operation Status'),
                'value' => $operationStatus,
                'tone' => $this->workflowToneForStatus($operationStatus),
            ];
        }

        return [
            'current_stage' => $currentStage,
            'next_action' => $nextAction,
            'notice' => $this->buildQuotationWorkflowNotice(
                $quotation,
                $quotationStatus,
                $logicalQuotationStatus,
                $validationStatus,
                $approvalStatus,
                $bookingStatus,
                $invoiceStatus,
                $paymentStatus,
                $operationStatus,
                $nextAction,
                $bookingsModuleEnabled,
                $invoicesModuleEnabled
            ),
            'responsible_user' => $responsibleUser,
            'responsible_label' => $responsibleUser?->name ?? '-',
            'status_cards' => $statusCards,
            'meta' => [
                [
                    'label' => ui_phrase('Current Stage'),
                    'value' => $currentStage,
                ],
                [
                    'label' => ui_phrase('Next Action'),
                    'value' => $nextAction,
                ],
                [
                    'label' => ui_phrase('Pending Validation Items'),
                    'value' => max(0, (int) ($validationProgress['total_required'] ?? 0) - (int) ($validationProgress['total_validated'] ?? 0)),
                ],
                [
                    'label' => ui_phrase('PIC / Handled By'),
                    'value' => $responsibleUser?->name ?? '-',
                ],
                [
                    'label' => ui_phrase('Revision Number'),
                    'value' => 'v' . (int) ($quotation->revision_number ?? 1),
                ],
                [
                    'label' => ui_phrase('Validity Date'),
                    'value' => optional($quotation->validity_date)->format('Y-m-d') ?? '-',
                ],
            ],
            'risks' => $risks,
        ];
    }

    private function deriveQuotationApprovalStatus(Quotation $quotation): string
    {
        $approvalStatusColumn = trim((string) ($quotation->approval_status ?? ''));
        if ($approvalStatusColumn !== '') {
            return $approvalStatusColumn;
        }

        if ($quotation->isStatus(Quotation::STATUS_CANCELLED)) {
            return 'cancelled';
        }
        if ($quotation->isStatus(Quotation::STATUS_LOST)) {
            return 'lost';
        }
        if ($quotation->isStatus(Quotation::STATUS_CUSTOMER_APPROVED, Quotation::FINAL_STATUS, Quotation::STATUS_IN_OPERATION, Quotation::STATUS_COMPLETED)
            || $quotation->approved_at) {
            return 'approved';
        }
        if ($quotation->isStatus(Quotation::STATUS_SENT)) {
            return 'waiting_customer';
        }

        return 'not_ready';
    }

    private function deriveQuotationPaymentStatus($invoice): string
    {
        if (! $invoice) {
            return 'not_invoiced';
        }

        $invoiceStatus = (string) ($invoice->status ?? '');
        if (in_array($invoiceStatus, ['paid', 'overpaid'], true)) {
            return $invoiceStatus;
        }

        $payments = $invoice->relationLoaded('payments') ? $invoice->payments : collect();
        if ($payments->contains(fn ($payment): bool => in_array((string) ($payment->status ?? ''), ['pending', 'waiting_confirmation'], true))) {
            return 'waiting_confirmation';
        }
        if ((float) ($invoice->paid_amount ?? 0) > 0 || $payments->contains(fn ($payment): bool => (string) ($payment->status ?? '') === 'confirmed')) {
            return 'partially_paid';
        }

        return 'unpaid';
    }

    private function deriveQuotationOperationStatus(string $bookingStatus): string
    {
        return match ($bookingStatus) {
            'ready_to_operate' => 'ready_to_operate',
            'in_operation' => 'in_operation',
            'service_completed' => 'service_completed',
            'reconciliation' => 'reconciliation',
            'completed_settled', 'closed' => 'completed',
            'cancelled' => 'cancelled',
            default => 'not_started',
        };
    }

    private function deriveQuotationCurrentStage(
        string $quotationStatus,
        string $validationStatus,
        string $approvalStatus,
        string $bookingStatus,
        string $invoiceStatus,
        string $paymentStatus,
        string $operationStatus,
        bool $bookingsModuleEnabled,
        bool $invoicesModuleEnabled
    ): string {
        if (in_array($quotationStatus, [Quotation::STATUS_CANCELLED, Quotation::STATUS_LOST], true)) {
            return QuotationWorkflow::label($quotationStatus);
        }
        if ($bookingsModuleEnabled && in_array($operationStatus, ['in_operation', 'service_completed', 'reconciliation', 'completed'], true)) {
            return QuotationWorkflow::label($operationStatus);
        }
        if ($invoicesModuleEnabled && ! in_array($paymentStatus, ['not_invoiced', 'unpaid'], true)) {
            return ui_phrase('Payment');
        }
        if ($invoicesModuleEnabled && ! in_array($invoiceStatus, ['not_created', 'draft'], true)) {
            return ui_phrase('Invoice');
        }
        if ($bookingsModuleEnabled && ! in_array($bookingStatus, ['not_created', 'created'], true)) {
            return ui_phrase('Booking');
        }
        if ($approvalStatus === 'approved') {
            return ui_phrase('Approved');
        }
        if ($quotationStatus === Quotation::STATUS_SENT || $approvalStatus === 'waiting_customer') {
            return ui_phrase('Sent');
        }
        if ($validationStatus === QuotationValidationService::STATUS_VALID || $quotationStatus === Quotation::STATUS_VALIDATED) {
            return ui_phrase('Ready to Send');
        }
        if ($quotationStatus === Quotation::STATUS_PENDING_VALIDATION || in_array($validationStatus, ['partial', 'pending'], true)) {
            return ui_phrase('Validation');
        }

        return ui_phrase('Quotation Draft');
    }

    private function deriveQuotationNextAction(
        Quotation $quotation,
        string $quotationStatus,
        string $validationStatus,
        string $approvalStatus,
        string $bookingStatus,
        string $invoiceStatus,
        string $paymentStatus,
        string $operationStatus,
        bool $bookingsModuleEnabled,
        bool $invoicesModuleEnabled
    ): string {
        if ($quotation->isStatus(Quotation::STATUS_CANCELLED, Quotation::STATUS_LOST, Quotation::STATUS_COMPLETED)) {
            return ui_phrase('View summary and audit history.');
        }
        if ($bookingsModuleEnabled && $operationStatus === 'reconciliation') {
            return ui_phrase('Finalize reconciliation and generate final invoice.');
        }
        if ($bookingsModuleEnabled && $operationStatus === 'service_completed') {
            return ui_phrase('Review actual usage before final invoice.');
        }
        if ($bookingsModuleEnabled && $operationStatus === 'in_operation') {
            return ui_phrase('Monitor operation and record adjustments if needed.');
        }
        if ($invoicesModuleEnabled && in_array($paymentStatus, ['unpaid', 'partially_paid', 'waiting_confirmation'], true)) {
            return ui_phrase('Follow up payment status.');
        }
        if ($invoicesModuleEnabled && in_array($invoiceStatus, ['draft', 'issued', 'partially_paid'], true)) {
            return ui_phrase('Continue invoice and payment process.');
        }
        if ($bookingsModuleEnabled && ! in_array($bookingStatus, ['not_created', 'cancelled'], true)) {
            return ui_phrase('Continue booking operation process.');
        }
        if ($approvalStatus === 'approved') {
            if (! $bookingsModuleEnabled) {
                return ui_phrase('Review approved quotation only.');
            }

            return ui_phrase('Create or review booking from approved quotation.');
        }
        if ($quotationStatus === Quotation::STATUS_SENT) {
            return ui_phrase('Wait for customer approval, revision request, lost, or cancellation decision.');
        }
        if ($validationStatus === QuotationValidationService::STATUS_VALID || $quotationStatus === Quotation::STATUS_VALIDATED) {
            return ui_phrase('Mark quotation as sent when ready.');
        }
        if ($quotationStatus === Quotation::STATUS_PENDING_VALIDATION || in_array($validationStatus, ['pending', 'partial'], true)) {
            return ui_phrase('Complete quotation item validation.');
        }

        return ui_phrase('Submit quotation for validation.');
    }

    /**
     * @return array{title: string, message: string, type: string}|null
     */
    private function buildQuotationWorkflowNotice(
        Quotation $quotation,
        string $quotationStatus,
        string $logicalQuotationStatus,
        string $validationStatus,
        string $approvalStatus,
        string $bookingStatus,
        string $invoiceStatus,
        string $paymentStatus,
        string $operationStatus,
        string $nextAction,
        bool $bookingsModuleEnabled,
        bool $invoicesModuleEnabled
    ): ?array {
        $pendingRevisionResponses = $quotation->relationLoaded('customerResponses')
            ? $quotation->customerResponses
                ->where('requires_revision', true)
                ->where('is_used_for_revision', false)
                ->count()
            : 0;
        $statusLabel = QuotationWorkflow::label($logicalQuotationStatus);

        if (in_array($logicalQuotationStatus, ['cancelled', 'lost', 'rejected'], true)) {
            return [
                'title' => ui_phrase('Quotation Closed'),
                'message' => ui_phrase('This quotation is closed as :status. Mutation actions are disabled; use history for audit context.', ['status' => $statusLabel]),
                'type' => 'danger',
            ];
        }

        if ($logicalQuotationStatus === 'completed') {
            if (! $bookingsModuleEnabled) {
                return [
                    'title' => ui_phrase('Quotation Completed'),
                    'message' => $invoicesModuleEnabled
                        ? ui_phrase('This quotation has completed the workflow. Keep the record unchanged and use linked invoice or activity history for review.')
                        : ui_phrase('This quotation has completed the workflow. Keep the record unchanged and use activity history for review.'),
                    'type' => 'info',
                ];
            }

            return [
                'title' => ui_phrase('Quotation Completed'),
                'message' => ui_phrase('This quotation has completed the workflow. Keep the record unchanged and use linked booking, invoice, or operation history for review.'),
                'type' => 'info',
            ];
        }

        if ($pendingRevisionResponses > 0) {
            return [
                'title' => ui_phrase('Customer Revision Pending'),
                'message' => ui_phrase(':count customer revision response(s) still need to be handled. Start or continue revision, then mark handled responses in the revision sidebar before sending again.', ['count' => $pendingRevisionResponses]),
                'type' => 'warning',
            ];
        }

        if ($logicalQuotationStatus === 'sent') {
            return [
                'title' => ui_phrase('Quotation Sent'),
                'message' => ui_phrase('This quotation has been sent to the customer. Direct editing is locked; record follow-up or customer response to continue the workflow.'),
                'type' => 'info',
            ];
        }

        if (in_array($logicalQuotationStatus, ['approved', 'customer_approved'], true) || $approvalStatus === 'approved') {
            if (! $bookingsModuleEnabled) {
                return [
                    'title' => ui_phrase('Approved Quotation'),
                    'message' => ui_phrase('This quotation is approved. Quotation edits are locked and downstream operational actions are hidden while the related module is unavailable.'),
                    'type' => 'info',
                ];
            }

            return [
                'title' => ui_phrase('Customer Approved'),
                'message' => ui_phrase('The customer has approved this quotation. Quotation edits are locked; continue with booking preparation.'),
                'type' => 'info',
            ];
        }

        if (in_array($logicalQuotationStatus, ['converted_to_booking', 'booking_created', 'booking_in_progress', 'booking_issue'], true)
            || ! in_array($bookingStatus, ['not_created', 'created'], true)) {
            if (! $bookingsModuleEnabled) {
                return [
                    'title' => ui_phrase('Downstream Process Hidden'),
                    'message' => ui_phrase('This quotation already has downstream process history. Related operational links are hidden while the related module is unavailable.'),
                    'type' => $bookingStatus === 'issue' ? 'warning' : 'info',
                ];
            }

            return [
                'title' => ui_phrase('Booking In Progress'),
                'message' => ui_phrase('This quotation is already linked to booking status :status. Manage service changes from the booking or approved revision flow.', ['status' => QuotationWorkflow::label($bookingStatus)]),
                'type' => $bookingStatus === 'issue' ? 'warning' : 'info',
            ];
        }

        if ($bookingsModuleEnabled && (
            in_array($logicalQuotationStatus, ['in_operation', 'operation_adjustment', 'finalized'], true)
            || in_array($operationStatus, ['in_operation', 'service_completed', 'reconciliation'], true)
        )) {
            return [
                'title' => ui_phrase('Operation Active'),
                'message' => ui_phrase('This quotation has moved into operation. Quotation edits are locked; use operation adjustment or final invoice actions as needed.'),
                'type' => 'info',
            ];
        }

        if ((string) $validationStatus !== QuotationValidationService::STATUS_VALID && ! in_array($logicalQuotationStatus, ['draft', 'ready_to_send'], true)) {
            return [
                'title' => ui_phrase('Validation Needed'),
                'message' => ui_phrase('Quotation validation is not complete. Current next action: :action', ['action' => $nextAction]),
                'type' => 'warning',
            ];
        }

        return null;
    }

    /**
     * @param  array<string, mixed>  $validationProgress
     * @return array<int, array{label: string, tone: string}>
     */
    private function buildQuotationRiskIndicators(
        Quotation $quotation,
        array $validationProgress,
        string $invoiceStatus,
        string $paymentStatus,
        string $bookingStatus,
        bool $bookingsModuleEnabled,
        bool $invoicesModuleEnabled
    ): array {
        $risks = [];
        if ($quotation->validity_date && $quotation->validity_date->isPast() && ! $quotation->isStatus(Quotation::STATUS_COMPLETED, Quotation::STATUS_CANCELLED, Quotation::STATUS_LOST)) {
            $risks[] = ['label' => ui_phrase('Validity date has expired.'), 'tone' => 'danger'];
        }
        if ((bool) ($validationProgress['requires_validation'] ?? false) && ! (bool) ($validationProgress['is_complete'] ?? false)) {
            $risks[] = ['label' => ui_phrase('Quotation validation is not complete.'), 'tone' => 'warning'];
        }
        if ($bookingsModuleEnabled && $bookingStatus === 'cancelled') {
            $risks[] = ['label' => ui_phrase('Linked booking is cancelled.'), 'tone' => 'danger'];
        }
        if ($invoicesModuleEnabled && in_array($invoiceStatus, ['void', 'cancelled'], true)) {
            $risks[] = ['label' => ui_phrase('Linked invoice is not active.'), 'tone' => 'danger'];
        }
        if ($invoicesModuleEnabled && in_array($paymentStatus, ['unpaid', 'partially_paid'], true)) {
            $risks[] = ['label' => ui_phrase('Payment is not fully settled.'), 'tone' => 'warning'];
        }

        return $risks;
    }

    private function displayQuotationStatusForWorkflow(string $logicalQuotationStatus, bool $bookingsModuleEnabled): string
    {
        if ($bookingsModuleEnabled) {
            return $logicalQuotationStatus;
        }

        if (in_array($logicalQuotationStatus, ['converted_to_booking', 'booking_created', 'booking_in_progress', 'booking_issue'], true)) {
            return 'approved';
        }

        return $logicalQuotationStatus;
    }

    private function workflowToneForStatus(string $status): string
    {
        return match ($status) {
            'approved', 'customer_approved', 'booking_created', 'converted_to_booking', 'paid', 'overpaid', 'completed', 'valid', 'validated' => 'success',
            'pending', 'pending_validation', 'need_validation', 'pending_revalidation', 'need_revalidation', 'partial', 'ready_for_approval', 'waiting_customer', 'waiting_confirmation', 'unpaid', 'partially_paid', 'draft', 'issued' => 'warning',
            'cancelled', 'lost', 'void', 'booking_issue' => 'danger',
            'not_created', 'not_started', 'not_invoiced', 'not_ready' => 'muted',
            default => 'info',
        };
    }

    private function toneForQuotationStatus(string $status): string
    {
        return match (strtolower($status)) {
            'pending_validation' => 'bg-sky-50 text-sky-700 border-sky-100',
            'validated' => 'bg-cyan-50 text-cyan-700 border-cyan-100',
            'sent' => 'bg-indigo-50 text-indigo-700 border-indigo-100',
            'customer_approved' => 'bg-emerald-50 text-emerald-700 border-emerald-100',
            'booking_created' => 'bg-violet-50 text-violet-700 border-violet-100',
            'lost', 'cancelled' => 'bg-rose-50 text-rose-700 border-rose-100',
            'draft' => 'bg-slate-50 text-slate-700 border-slate-100',
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
        if ($this->quotationUsesApprovalBypass($quotation)) {
            $validationPassed = $this->quotationValidationService->canBeApproved($quotation);
            return [
                'required_non_creator_approvals' => 0,
                'non_creator_approval_count' => 0,
                'remaining_non_creator_approvals' => 0,
                'manager_approved' => false,
                'director_approved' => $validationPassed,
                'reservation_other_approved' => false,
                'reservation_count' => 0,
                'is_ready' => $validationPassed,
                'missing_labels' => $validationPassed ? [] : ['Validation is not completed'],
                'latest_non_creator_approver_id' => null,
                'director_approver_id' => null,
            ];
        }

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
        if ($this->quotationUsesApprovalBypass($quotation)) {
            $this->applyPrivilegedCreatorApprovalStatus($quotation);
            $this->syncLinkedLifecycleStatusesForQuotation($quotation);

            return $this->buildApprovalProgress($quotation);
        }

        $quotation->loadMissing('approvals');
        $quotation->unsetRelation('approvals');
        $quotation->load('approvals');
        $progress = $this->buildApprovalProgress($quotation);

        if ($progress['is_ready']) {
            $quotation->update([
                'status' => Quotation::STATUS_APPROVED,
                'approved_by' => $progress['latest_non_creator_approver_id'],
                'approved_at' => now(),
            ]);
        } else {
            $quotation->update([
                'status' => Quotation::STATUS_NEED_VALIDATION,
                'approved_by' => null,
                'approved_at' => null,
            ]);
        }
        $this->syncLinkedLifecycleStatusesForQuotation($quotation);

        return $progress;
    }

    private function shouldAutoApproveQuotationForCreator($user): bool
    {
        if (! $user) {
            return false;
        }

        return $user->can('dashboard.superadmin.view')
            || $user->can('dashboard.director.view');
    }

    private function quotationUsesApprovalBypass(Quotation $quotation): bool
    {
        $creator = $quotation->relationLoaded('creator')
            ? $quotation->creator
            : null;

        if (! $creator && Schema::hasColumn('quotations', 'created_by')) {
            $creatorId = (int) ($quotation->created_by ?? 0);
            if ($creatorId > 0) {
                $creator = User::query()->find($creatorId);
            }
        }

        return $this->shouldAutoApproveQuotationForCreator($creator);
    }

    private function applyPrivilegedCreatorApprovalStatus(Quotation $quotation): void
    {
        $canAutoApprove = $this->quotationValidationService->canBeApproved($quotation);
        $approvedBy = (int) ($quotation->created_by ?? auth()->id() ?? 0);

        Quotation::withoutActivityLogging(function () use ($quotation, $canAutoApprove, $approvedBy): void {
            if ($canAutoApprove) {
                $quotation->update([
                    'status' => Quotation::STATUS_APPROVED,
                    'approved_by' => $approvedBy > 0 ? $approvedBy : null,
                    'approved_at' => now(),
                    'approval_note' => null,
                    'approval_note_by' => null,
                    'approval_note_at' => null,
                ]);
                return;
            }

            $quotation->update([
                'status' => Quotation::STATUS_NEED_VALIDATION,
                'approved_by' => null,
                'approved_at' => null,
            ]);
        });
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
                    'sort_order' => (int) ($item->sort_order ?? 0),
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
            'order_number' => (string) ($quotation->order_number ?? ''),
            'service_date' => optional($quotation->service_date)->format('Y-m-d'),
            'pax_adult' => (int) ($quotation->pax_adult ?? 0),
            'pax_child' => (int) ($quotation->pax_child ?? 0),
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
