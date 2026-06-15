<?php

namespace App\Http\Controllers\Sales;

use App\Http\Controllers\Concerns\HandlesActivityTimelineAjax;
use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Inquiry;
use App\Models\Quotation;
use App\Models\User;
use App\Services\ActivityAuditLogger;
use App\Support\InquiryDeadlineReminder;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Validation\Rule;

class InquiryController extends Controller
{
    use HandlesActivityTimelineAjax;

    public function __construct(
        private readonly ActivityAuditLogger $activityAuditLogger
    ) {
    }

    private const SOURCE_OPTIONS = [
        'phone',
        'email',
        'website',
        'walk-in',
        'whatsapp',
        'line',
        'wechat',
        'telegram',
        'instagram',
        'facebook',
        'tiktok',
        'linkedin',
        'traveloka',
        'klook',
        'referral',
        'other',
    ];

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $searchKeyword = trim((string) request('q'));
        $selectedTab = (string) request('tab', 'new');
        if (! in_array($selectedTab, ['new', 'in_progress', 'archived'], true)) {
            $selectedTab = 'new';
        }

        $query = Inquiry::query()
            ->withTrashed()
            ->with([
                'customer',
                'creator',
                'handledBy:id,name',
                'assignedTo:id,name',
                'quotation' => function ($quotationQuery) {
                    $quotationQuery
                        ->select([
                            'quotations.id',
                            'quotations.inquiry_id',
                            'quotations.quotation_number',
                            'quotations.order_number',
                            'quotations.status',
                            'quotations.updated_at',
                            'quotations.deleted_at',
                        ])
                        ->withCount('items');
                },
                'itineraries' => function ($itineraryQuery) {
                    $itineraryQuery
                        ->select([
                            'itineraries.id',
                            'itineraries.title',
                            'itineraries.status',
                            'itineraries.is_active',
                            'itineraries.updated_at',
                        ])
                        ->orderByDesc('itineraries.is_active')
                        ->orderByDesc('itineraries.updated_at');
                },
            ])
            ->withCount('itineraries');

        if ($searchKeyword !== '') {
            if (mb_strlen($searchKeyword) < 3) {
                $query->whereRaw('1 = 0');
            } else {
                $query->where(function ($sub) use ($searchKeyword) {
                    $sub
                        ->where('inquiry_number', 'like', "%{$searchKeyword}%")
                        ->orWhere('status', 'like', "%{$searchKeyword}%")
                        ->orWhere('priority', 'like', "%{$searchKeyword}%")
                        ->orWhereRaw("DATE_FORMAT(deadline, '%Y-%m-%d') LIKE ?", ["%{$searchKeyword}%"])
                        ->orWhereHas('customer', function ($c) use ($searchKeyword) {
                            $c->where('name', 'like', "%{$searchKeyword}%");
                        })
                        ->orWhereHas('creator', function ($creatorQuery) use ($searchKeyword) {
                            $creatorQuery->where('name', 'like', "%{$searchKeyword}%");
                        })
                        ->orWhereHas('itineraries', function ($itineraryQuery) use ($searchKeyword) {
                            $itineraryQuery
                                ->where('title', 'like', "%{$searchKeyword}%")
                                ->orWhere('status', 'like', "%{$searchKeyword}%");
                        });
                });
            }
        }

        $query->when(request('priority'), fn ($q) => $q->where('priority', request('priority')));

        $baseFilteredQuery = clone $query;

        $this->applyInquiryTabFilter($query, $selectedTab);

        $perPage = (int) request('per_page', 10);
        $perPage = $perPage > 0 && $perPage <= 100 ? $perPage : 10;

        $inquiries = $query
            ->orderByRaw('deadline IS NULL, deadline ASC')
            ->orderByDesc('created_at')
            ->paginate($perPage)
            ->withQueryString();

        $tabCounts = [
            'new' => $this->countInquiryTab(clone $baseFilteredQuery, 'new'),
            'in_progress' => $this->countInquiryTab(clone $baseFilteredQuery, 'in_progress'),
            'archived' => $this->countInquiryTab(clone $baseFilteredQuery, 'archived'),
        ];

        $inquiryTabs = $this->buildInquiryTabs(
            $tabCounts,
            $selectedTab,
            request()->except('tab', 'page')
        );
        $inquiryRows = $this->buildInquiryIndexRows($inquiries);
        $perPageOptions = [10, 25, 50, 100];

        return view('modules.inquiries.index', compact(
            'inquiries',
            'selectedTab',
            'tabCounts',
            'inquiryTabs',
            'inquiryRows',
            'perPageOptions'
        ));
    }

    private function buildInquiryTabs(array $tabCounts, string $selectedTab, array $queryBase): array
    {
        return [
            [
                'key' => 'new',
                'label' => ui_phrase('New'),
                'count' => (int) ($tabCounts['new'] ?? 0),
                'is_active' => $selectedTab === 'new',
                'url' => route('inquiries.index', array_merge($queryBase, ['tab' => 'new'])),
            ],
            [
                'key' => 'in_progress',
                'label' => ui_phrase('In Progress'),
                'count' => (int) ($tabCounts['in_progress'] ?? 0),
                'is_active' => $selectedTab === 'in_progress',
                'url' => route('inquiries.index', array_merge($queryBase, ['tab' => 'in_progress'])),
            ],
            [
                'key' => 'archived',
                'label' => ui_phrase('Archived'),
                'count' => (int) ($tabCounts['archived'] ?? 0),
                'is_active' => $selectedTab === 'archived',
                'url' => route('inquiries.index', array_merge($queryBase, ['tab' => 'archived'])),
            ],
        ];
    }

    private function buildInquiryIndexRows($inquiries): array
    {
        $currentUserId = (int) (auth()->id() ?? 0);
        $user = auth()->user();
        $canAccessQuotationModule = $user?->can('module.quotations.access') ?? false;
        $canCreateQuotation = $user?->can('module.quotations.create') ?? false;
        $firstItem = (int) ($inquiries->firstItem() ?? 1);

        return $inquiries->getCollection()->values()->map(function (Inquiry $inquiry, int $index) use (
            $currentUserId,
            $user,
            $canAccessQuotationModule,
            $canCreateQuotation,
            $firstItem
        ): array {
            $handlerIdForActions = (int) ($inquiry->handled_by ?? ($inquiry->assigned_to ?? 0));
            $linkedQuotation = $inquiry->quotation;
            $hasLinkedQuotation = $linkedQuotation !== null;
            $canProcessInquiry = ! $inquiry->isFinal()
                && ($handlerIdForActions <= 0 || $handlerIdForActions === $currentUserId);

            $quotationNumber = trim((string) ($linkedQuotation?->quotation_number ?? ''));
            $orderNumber = trim((string) ($linkedQuotation?->order_number ?? ''));
            $quotationSummary = $hasLinkedQuotation
                ? ($quotationNumber !== '' ? $quotationNumber : '-') . ' | ' . ($orderNumber !== '' ? $orderNumber : '-')
                : '-';

            $canOpenLinkedQuotation = $hasLinkedQuotation
                && ! $linkedQuotation->trashed()
                && Route::has('quotations.show')
                && $canAccessQuotationModule;

            return [
                'inquiry' => $inquiry,
                'row_number' => $firstItem + $index,
                'customer_name' => trim((string) ($inquiry->customer?->name ?? '')) !== ''
                    ? (string) $inquiry->customer->name
                    : '-',
                'status' => (string) ($inquiry->status ?? 'new_request'),
                'priority' => (string) ($inquiry->priority ?? ''),
                'creator_name' => trim((string) ($inquiry->creator?->name ?? '')) !== ''
                    ? (string) $inquiry->creator->name
                    : '-',
                'assigned_to_name' => trim((string) ($inquiry->handledBy?->name ?? $inquiry->assignedTo?->name ?? '')) !== ''
                    ? (string) ($inquiry->handledBy?->name ?? $inquiry->assignedTo?->name)
                    : '-',
                'deadline_display' => $inquiry->deadline ? $inquiry->deadline->format('Y-m-d') : '-',
                'has_linked_quotation' => $hasLinkedQuotation,
                'quotation_summary' => $quotationSummary,
                'quotation_status' => (string) ($linkedQuotation?->status ?? 'draft'),
                'can_open_linked_quotation' => $canOpenLinkedQuotation,
                'quotation_show_url' => $canOpenLinkedQuotation ? route('quotations.show', $linkedQuotation) : null,
                'show_url' => route('inquiries.show', $inquiry),
                'edit_url' => route('inquiries.edit', $inquiry),
                'can_edit' => (bool) ($user?->can('update', $inquiry) ?? false)
                    && ! $hasLinkedQuotation
                    && ! $inquiry->isFinal(),
                'can_process' => $canProcessInquiry,
                'can_generate_quotation' => ! $hasLinkedQuotation && $canProcessInquiry && $canCreateQuotation,
                'create_itinerary_url' => route('itineraries.create', ['inquiry_id' => $inquiry->id]),
                'create_quotation_url' => route('quotations.create', ['inquiry_id' => $inquiry->id]),
            ];
        })->all();
    }

    private function countInquiryTab(Builder $query, string $tab): int
    {
        $this->applyInquiryTabFilter($query, $tab);
        return (int) $query->count();
    }

    private function applyInquiryTabFilter(Builder $query, string $tab): void
    {
        $archivedStatuses = ['expired', 'cancelled', 'lost', 'unqualified', Inquiry::FINAL_STATUS];
        $inProgressStatuses = ['qualified', 'itinerary_in_progress', 'quotation_in_progress', 'quotation_sent', 'under_negotiation', 'accepted'];
        $newStatuses = ['new_request', 'need_customer_data', 'registered', 'assigned', 'contacted', 'waiting_customer'];

        if ($tab === 'archived') {
            $query->whereIn('status', $archivedStatuses);
            return;
        }

        if ($tab === 'in_progress') {
            $query->where(function (Builder $subQuery) use ($inProgressStatuses): void {
                $subQuery
                    ->whereIn('status', $inProgressStatuses)
                    ->orWhereHas('itineraries')
                    ->orWhereHas('quotation')
                    ->orWhereHas('itineraries.quotations');
            });
            $query->whereNotIn('status', ['expired', 'cancelled', 'lost', 'unqualified']);
            return;
        }

        $query->whereIn('status', $newStatuses)
            ->whereDoesntHave('itineraries')
            ->whereDoesntHave('quotation')
            ->whereDoesntHave('itineraries.quotations');
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('modules.inquiries.create', $this->buildInquiryFormViewData());
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $this->validateInquiryPayload($request);
        $validated = $this->normalizeInquiryAssignmentPayload($validated);
        $validated['status'] = 'new_request';

        $inquiry = Inquiry::withoutActivityLogging(function () use ($validated) {
            return Inquiry::query()->create($validated);
        });
        $this->activityAuditLogger->logCreated($inquiry, $this->buildInquiryAuditSnapshot($inquiry), 'Inquiry');

        return redirect()
            ->route('inquiries.show', $inquiry)
            ->with('success', ui_phrase('Inquiry created successfully.'));
    }

    /**
     * Display the specified resource.
     */
    public function show(Request $request, Inquiry $inquiry)
    {
        $inquiry->load(['customer', 'creator', 'handledBy:id,name', 'assignedTo:id,name', 'quotation:id,inquiry_id,status,deleted_at']);
        $itineraries = $inquiry->itineraries()
            ->with('destination:id,name')
            ->with(['dayPoints:id,itinerary_id,day_number,break_start_time,break_end_time'])
            ->orderByDesc('itineraries.updated_at')
            ->get([
                'itineraries.id',
                'itineraries.title',
                'itineraries.destination_id',
                'itineraries.duration_days',
            ]);
        $quotations = Quotation::query()
            ->withTrashed()
            ->where('inquiry_id', $inquiry->id)
            ->with('itinerary:id,title')
            ->latest('quotations.updated_at')
            ->limit(1)
            ->get([
                'quotations.id',
                'quotations.quotation_number',
                'quotations.order_number',
                'quotations.status',
                'quotations.inquiry_id',
                'quotations.itinerary_id',
                'quotations.updated_at',
                'quotations.deleted_at',
            ]);
        $activities = $inquiry->activities()
            ->with('user:id,name')
            ->latest()
            ->paginate(5, ['*'], 'activity_page')
            ->withQueryString();
        if ($this->wantsActivityTimelineFragment($request)) {
            return $this->activityTimelineFragmentResponse($activities);
        }

        return view('modules.inquiries.show', compact('inquiry', 'itineraries', 'quotations', 'activities'));
    }

    public function deadlineReminderNotifications(Request $request)
    {
        $user = $request->user();
        if (! $user) {
            return response()->json([
                'enabled' => false,
                'count' => 0,
                'latest' => null,
            ], 401);
        }

        if (! $user->can('module.inquiries.access')) {
            return response()->json([
                'enabled' => false,
                'count' => 0,
                'latest' => null,
            ]);
        }

        $query = $this->pendingInquiryDeadlineReminderQuery($user);
        $count = (clone $query)->count();
        $latest = (clone $query)
            ->orderBy('deadline')
            ->latest('id')
            ->first(['id', 'inquiry_number', 'deadline', 'priority']);

        return response()->json([
            'enabled' => true,
            'count' => (int) $count,
            'latest' => $latest ? [
                'id' => (int) $latest->id,
                'inquiry_number' => (string) ($latest->inquiry_number ?? ''),
                'deadline' => optional($latest->deadline)->toDateString(),
                'priority' => (string) ($latest->priority ?? ''),
                'deadline_label' => InquiryDeadlineReminder::reminderLabel($latest->deadline),
                'days_until_deadline' => InquiryDeadlineReminder::daysUntilDeadline($latest->deadline),
            ] : null,
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Request $request, Inquiry $inquiry)
    {
        $inquiry->loadMissing(['quotation:id,inquiry_id,status,deleted_at']);
        if (! $this->canManageInquiry($inquiry, 'update')) {
            return $this->denyInquiryMutation($inquiry);
        }
        if ($inquiry->isFinal()) {
            return redirect()
                ->route('inquiries.show', $inquiry)
                ->with('error', ui_phrase('Inquiry is final and cannot be edited.'));
        }
        if ($this->isInquiryLockedByQuotation($inquiry)) {
            return redirect()
                ->route('inquiries.show', $inquiry)
                ->with('error', ui_phrase('Inquiry is locked by quotation and cannot be edited.'));
        }
        $activities = $inquiry->activities()
            ->with('user:id,name')
            ->latest()
            ->paginate(5, ['*'], 'activity_page')
            ->withQueryString();

        if ($this->wantsActivityTimelineFragment($request)) {
            return $this->activityTimelineFragmentResponse($activities);
        }

        return view('modules.inquiries.edit', array_merge(
            $this->buildInquiryFormViewData(),
            compact('inquiry', 'activities')
        ));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Inquiry $inquiry)
    {
        $inquiry->loadMissing(['quotation:id,inquiry_id,status,deleted_at']);
        if (! $this->canManageInquiry($inquiry, 'update')) {
            return $this->denyInquiryMutation($inquiry);
        }
        if ($inquiry->isFinal()) {
            return redirect()
                ->route('inquiries.show', $inquiry)
                ->with('error', ui_phrase('Inquiry is final and cannot be edited.'));
        }
        if ($this->isInquiryLockedByQuotation($inquiry)) {
            return redirect()
                ->route('inquiries.show', $inquiry)
                ->with('error', ui_phrase('Inquiry is locked by quotation and cannot be updated.'));
        }
        $validated = $this->validateInquiryPayload($request);
        $existingHandlerId = (int) ($inquiry->handled_by ?? $inquiry->assigned_to ?? 0);
        $validated = $this->normalizeInquiryAssignmentPayload($validated, $existingHandlerId);

        $beforeAudit = $this->buildInquiryAuditSnapshot($inquiry);
        Inquiry::withoutActivityLogging(function () use ($inquiry, $validated): void {
            $inquiry->update($validated);
        });
        $inquiry->refresh();
        $this->activityAuditLogger->logUpdated($inquiry, $beforeAudit, $this->buildInquiryAuditSnapshot($inquiry), 'Inquiry');

        return redirect()
            ->route('inquiries.show', $inquiry)
            ->with('success', ui_phrase('Inquiry updated successfully.'));
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Inquiry $inquiry)
    {
        if ($inquiry->isFinal()) {
            return redirect()
                ->route('inquiries.show', $inquiry)
                ->with('error', ui_phrase('Inquiry is final and cannot be edited.'));
        }
        $inquiry->delete();

        return redirect()
            ->route('inquiries.index')
            ->with('success', ui_phrase('Inquiry deactivated successfully.'));
    }

    public function toggleStatus($inquiry)
    {
        abort_unless(auth()->user()?->canManageActivationActions(), 403);
        $inquiry = Inquiry::withTrashed()->findOrFail($inquiry);
        if ($inquiry->isFinal()) {
            return redirect()
                ->route('inquiries.show', $inquiry)
                ->with('error', ui_phrase('Inquiry is final and status cannot be changed.'));
        }

        if ($inquiry->trashed()) {
            $inquiry->restore();

            return redirect()
                ->route('inquiries.index')
                ->with('success', ui_phrase('Inquiry activated successfully.'));
        }

        $inquiry->delete();

        return redirect()
            ->route('inquiries.index')
            ->with('success', ui_phrase('Inquiry deactivated successfully.'));
    }


    private function canManageInquiry(Inquiry $inquiry, string $ability = 'update'): bool
    {
        $user = auth()->user();
        if (! $user) {
            return false;
        }
        if (! in_array($ability, ['update', 'delete'], true)) {
            $ability = 'update';
        }

        return $user->can($ability, $inquiry);
    }

    private function isInquiryLockedByQuotation(Inquiry $inquiry): bool
    {
        $inquiry->loadMissing('quotation:id,inquiry_id,status,deleted_at');
        return $inquiry->hasLinkedQuotation();
    }

    private function denyInquiryMutation(Inquiry $inquiry)
    {
        return redirect()
            ->route('inquiries.show', $inquiry)
            ->with('error', ui_phrase('You do not have permission to modify this inquiry.'));
    }

    private function buildInquiryAuditSnapshot(Inquiry $inquiry): array
    {
        return [
            'inquiry_number' => (string) ($inquiry->inquiry_number ?? ''),
            'customer_id' => (int) ($inquiry->customer_id ?? 0),
            'source' => (string) ($inquiry->source ?? ''),
            'status' => (string) ($inquiry->status ?? ''),
            'priority' => (string) ($inquiry->priority ?? ''),
            'assigned_to' => (int) ($inquiry->assigned_to ?? 0),
            'handled_by' => (int) ($inquiry->handled_by ?? 0),
            'deadline' => optional($inquiry->deadline)->format('Y-m-d'),
            'reminder_enabled' => (bool) ($inquiry->reminder_enabled ?? false),
            'notes' => trim((string) ($inquiry->notes ?? '')),
        ];
    }

    private function buildInquiryFormViewData(): array
    {
        return [
            'customers' => Customer::query()
                ->select(['id', 'name', 'code'])
                ->orderBy('name')
                ->get(),
            'handlerUsers' => $this->handlerUsersQuery()->get(['id', 'name']),
            'sourceOptions' => self::SOURCE_OPTIONS,
        ];
    }

    private function validateInquiryPayload(Request $request): array
    {
        return $request->validate([
            'customer_id' => ['required', 'exists:customers,id'],
            'source' => ['nullable', Rule::in(self::SOURCE_OPTIONS)],
            'priority' => ['required', Rule::in(['low', 'normal', 'high'])],
            'deadline' => ['nullable', 'date'],
            'assigned_to' => ['nullable', 'integer', Rule::exists('users', 'id')],
            'notes' => ['nullable', 'string'],
        ]);
    }

    private function normalizeInquiryAssignmentPayload(array $validated, ?int $lockedHandlerId = null): array
    {
        if ($lockedHandlerId && $lockedHandlerId > 0) {
            $validated['assigned_to'] = $lockedHandlerId;
        }

        $this->assertAssignedToRole($validated['assigned_to'] ?? null);
        $validated['reminder_enabled'] = true;
        $validated['handled_by'] = $validated['assigned_to'] ?? null;

        return $validated;
    }

    private function pendingInquiryDeadlineReminderQuery($user): Builder
    {
        return InquiryDeadlineReminder::queryForUser($user);
    }

    private function handlerUsersQuery()
    {
        return User::query()
            ->whereHas('roles', function (Builder $query): void {
                $query->whereIn('name', ['Reservation', 'Manager', 'Director']);
            })
            ->orderBy('name');
    }

    private function assertAssignedToRole($assignedToId): void
    {
        $id = (int) ($assignedToId ?? 0);
        if ($id <= 0) {
            return;
        }

        $isValidHandler = $this->handlerUsersQuery()
            ->where('id', $id)
            ->exists();

        if (! $isValidHandler) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'assigned_to' => ui_phrase('Assigned To must be Reservation, Manager, or Director.'),
            ]);
        }
    }

}
