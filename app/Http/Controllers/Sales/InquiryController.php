<?php

namespace App\Http\Controllers\Sales;

use App\Http\Controllers\Concerns\HandlesActivityTimelineAjax;
use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Inquiry;
use App\Models\InquiryCommunication;
use App\Models\InquiryFollowUp;
use App\Models\Quotation;
use App\Models\User;
use App\Services\ActivityAuditLogger;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Spatie\Permission\Models\Role;

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

    private const CHANNEL_OPTIONS = [
        'phone',
        'email',
        'whatsapp',
        'line',
        'wechat',
        'telegram',
        'meeting',
        'zoom',
        'google-meet',
        'instagram',
        'facebook',
        'other',
    ];

    private const SOURCE_LABELS = [
        'phone' => 'Phone',
        'email' => 'Email',
        'website' => 'Website',
        'walk-in' => 'Walk-in',
        'whatsapp' => 'WhatsApp',
        'line' => 'LINE',
        'wechat' => 'WeChat',
        'telegram' => 'Telegram',
        'instagram' => 'Instagram',
        'facebook' => 'Facebook',
        'tiktok' => 'TikTok',
        'linkedin' => 'LinkedIn',
        'traveloka' => 'Traveloka',
        'klook' => 'Klook',
        'referral' => 'Referral',
        'other' => 'Other',
    ];

    private const CHANNEL_LABELS = [
        'phone' => 'Phone',
        'email' => 'Email',
        'whatsapp' => 'WhatsApp',
        'line' => 'LINE',
        'wechat' => 'WeChat',
        'telegram' => 'Telegram',
        'meeting' => 'Meeting',
        'zoom' => 'Zoom',
        'google-meet' => 'Google Meet',
        'instagram' => 'Instagram',
        'facebook' => 'Facebook',
        'other' => 'Other',
    ];
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $query = Inquiry::query()
            ->withTrashed()
            ->with([
                'customer',
                'assignedUser',
                'quotation:id,inquiry_id,status',
                'itineraries:id,inquiry_id,title,is_active,updated_at',
            ])
            ->withCount('itineraries');

        $query->when(request('q'), function ($q) {
            $term = request('q');
            $q->where(function ($sub) use ($term) {
                $sub->where('inquiry_number', 'like', "%{$term}%")
                    ->orWhereHas('customer', function ($c) use ($term) {
                        $c->where('name', 'like', "%{$term}%");
                    });
            });
        });

        $query->when(request('status'), fn ($q) => $q->where('status', request('status')));
        $query->when(request('priority'), fn ($q) => $q->where('priority', request('priority')));
        $query->when(request('customer_id'), fn ($q) => $q->where('customer_id', request('customer_id')));
        $query->when(request('assigned_to'), fn ($q) => $q->where('assigned_to', request('assigned_to')));
        $query->when(request('source'), fn ($q) => $q->where('source', request('source')));
        $query->when(request('deadline_from'), fn ($q) => $q->whereDate('deadline', '>=', request('deadline_from')));
        $query->when(request('deadline_to'), fn ($q) => $q->whereDate('deadline', '<=', request('deadline_to')));
        $query->when(request('itinerary') === 'available', fn ($q) => $q->has('itineraries'));
        $query->when(request('itinerary') === 'missing', fn ($q) => $q->doesntHave('itineraries'));

        $perPage = (int) request('per_page', 10);
        $perPage = $perPage > 0 && $perPage <= 100 ? $perPage : 10;

        $inquiries = $query
            ->orderByRaw('deadline IS NULL, deadline ASC')
            ->orderByDesc('created_at')
            ->paginate($perPage)
            ->withQueryString();
        $customers = Customer::query()->orderBy('name')->get();
        $assignees = User::role(['Reservation', 'Manager', 'Director', 'Marketing'])->orderBy('name')->get();

        $sourceLabels = self::SOURCE_LABELS;

        return view('modules.inquiries.index', compact('inquiries', 'customers', 'assignees', 'sourceLabels'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $customers = Customer::query()->orderBy('name')->get();
        $assignees = User::role(['Reservation'])->orderBy('name')->get();
        $canAssignToReservation = auth()->user()?->hasRole(['Manager', 'Director']) ?? false;

        $sourceLabels = self::SOURCE_LABELS;
        $channelLabels = self::CHANNEL_LABELS;

        return view('modules.inquiries.create', compact('customers', 'assignees', 'sourceLabels', 'channelLabels', 'canAssignToReservation'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $canAssignToReservation = auth()->user()?->hasRole(['Manager', 'Director']) ?? false;
        $reservationRoleId = $canAssignToReservation
            ? Role::query()->where('name', 'Reservation')->value('id')
            : null;
        $validated = $request->validate([
            'customer_id' => ['required', 'exists:customers,id'],
            'source' => ['nullable', Rule::in(self::SOURCE_OPTIONS)],
            'priority' => ['required', Rule::in(['low', 'normal', 'high'])],
            'deadline' => ['nullable', 'date'],
            'notes' => ['nullable', 'string'],
            'reminder_enabled' => ['nullable', 'boolean'],
            'assigned_to' => $canAssignToReservation
                ? [
                    'required',
                    'integer',
                    Rule::exists('model_has_roles', 'model_id')
                        ->where('role_id', $reservationRoleId)
                        ->where('model_type', User::class),
                ]
                : ['nullable'],
        ]);
        $validated['reminder_enabled'] = $request->boolean('reminder_enabled');
        $validated['status'] = 'draft';
        $validated['assigned_to'] = $canAssignToReservation
            ? (int) ($validated['assigned_to'] ?? auth()->id())
            : auth()->id();

        $inquiry = Inquiry::withoutActivityLogging(function () use ($validated) {
            return Inquiry::query()->create($validated);
        });
        $this->activityAuditLogger->logCreated($inquiry, $this->buildInquiryAuditSnapshot($inquiry), 'Inquiry');

        return redirect()
            ->route('inquiries.show', $inquiry)
            ->with('success', 'Inquiry created successfully.');
    }

    /**
     * Display the specified resource.
     */
    public function show(Request $request, Inquiry $inquiry)
    {
        $inquiry->load(['customer', 'assignedUser', 'quotation:id,inquiry_id,status']);
        $followUps = $inquiry->followUps()
            ->with('creator:id,name')
            ->orderByDesc('due_date')
            ->get();
        $activities = $inquiry->activities()
            ->with('user:id,name')
            ->latest()
            ->paginate(5, ['*'], 'activity_page')
            ->withQueryString();
        $communications = $inquiry->communications()->with('creator')->orderByDesc('contact_at')->get();
        $channelLabels = self::CHANNEL_LABELS;
        $sourceLabels = self::SOURCE_LABELS;
        $canManageInquiry = auth()->user()?->can('update', $inquiry) ?? false;
        $canManageFollowUp = $this->canManageFollowUp($inquiry);
        $canMarkFollowUpDone = $this->canMarkFollowUpDone($inquiry);

        if ($this->wantsActivityTimelineFragment($request)) {
            return $this->activityTimelineFragmentResponse($activities);
        }

        return view('modules.inquiries.show', compact('inquiry', 'followUps', 'activities', 'communications', 'channelLabels', 'sourceLabels', 'canManageInquiry', 'canManageFollowUp', 'canMarkFollowUpDone'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Request $request, Inquiry $inquiry)
    {
        $inquiry->loadMissing(['quotation:id,inquiry_id,status']);
        if (! $this->canManageInquiry($inquiry, 'update')) {
            return $this->denyInquiryMutation($inquiry);
        }
        if ($inquiry->isFinal()) {
            return redirect()
                ->route('inquiries.show', $inquiry)
                ->with('error', 'Inquiry sudah final dan tidak dapat diubah.');
        }
        if ($this->isInquiryLockedByQuotation($inquiry)) {
            return redirect()
                ->route('inquiries.show', $inquiry)
                ->with('error', 'Inquiry cannot be edited because the related quotation is approved/final.');
        }
        $customers = Customer::query()->orderBy('name')->get();
        $assignees = User::role(['Reservation'])->orderBy('name')->get();
        $canAssignToReservation = auth()->user()?->hasRole(['Manager', 'Director']) ?? false;
        $activities = $inquiry->activities()
            ->with('user:id,name')
            ->latest()
            ->paginate(5, ['*'], 'activity_page')
            ->withQueryString();

        $sourceLabels = self::SOURCE_LABELS;

        if ($this->wantsActivityTimelineFragment($request)) {
            return $this->activityTimelineFragmentResponse($activities);
        }

        return view('modules.inquiries.edit', compact('inquiry', 'customers', 'assignees', 'sourceLabels', 'canAssignToReservation', 'activities'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Inquiry $inquiry)
    {
        $inquiry->loadMissing(['quotation:id,inquiry_id,status']);
        if (! $this->canManageInquiry($inquiry, 'update')) {
            return $this->denyInquiryMutation($inquiry);
        }
        if ($inquiry->isFinal()) {
            return redirect()
                ->route('inquiries.show', $inquiry)
                ->with('error', 'Inquiry sudah final dan tidak dapat diubah.');
        }
        if ($this->isInquiryLockedByQuotation($inquiry)) {
            return redirect()
                ->route('inquiries.show', $inquiry)
                ->with('error', 'Inquiry cannot be updated because the related quotation is approved/final.');
        }
        $canAssignToReservation = auth()->user()?->hasRole(['Manager', 'Director']) ?? false;
        $reservationRoleId = $canAssignToReservation
            ? Role::query()->where('name', 'Reservation')->value('id')
            : null;
        $validated = $request->validate([
            'customer_id' => ['required', 'exists:customers,id'],
            'source' => ['nullable', Rule::in(self::SOURCE_OPTIONS)],
            'priority' => ['required', Rule::in(['low', 'normal', 'high'])],
            'deadline' => ['nullable', 'date'],
            'notes' => ['nullable', 'string'],
            'reminder_enabled' => ['nullable', 'boolean'],
            'assigned_to' => $canAssignToReservation
                ? [
                    'nullable',
                    'integer',
                    Rule::exists('model_has_roles', 'model_id')
                        ->where('role_id', $reservationRoleId)
                        ->where('model_type', User::class),
                ]
                : ['nullable'],
        ]);
        $validated['reminder_enabled'] = $request->boolean('reminder_enabled');
        if ($canAssignToReservation && ! empty($validated['assigned_to'])) {
            $validated['assigned_to'] = (int) $validated['assigned_to'];
        } else {
            unset($validated['assigned_to']);
        }

        $beforeAudit = $this->buildInquiryAuditSnapshot($inquiry);
        Inquiry::withoutActivityLogging(function () use ($inquiry, $validated): void {
            $inquiry->update($validated);
        });
        $inquiry->refresh();
        $this->activityAuditLogger->logUpdated($inquiry, $beforeAudit, $this->buildInquiryAuditSnapshot($inquiry), 'Inquiry');
        $this->syncFollowUpStatus($inquiry);

        return redirect()
            ->route('inquiries.show', $inquiry)
            ->with('success', 'Inquiry updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Inquiry $inquiry)
    {
        if ($inquiry->isFinal()) {
            return redirect()
                ->route('inquiries.show', $inquiry)
                ->with('error', 'Inquiry sudah final dan tidak dapat diubah.');
        }
        $inquiry->delete();

        return redirect()
            ->route('inquiries.index')
            ->with('success', 'Inquiry deactivated successfully.');
    }

    public function toggleStatus($inquiry)
    {
        $inquiry = Inquiry::withTrashed()->findOrFail($inquiry);
        if ($inquiry->isFinal()) {
            return redirect()
                ->route('inquiries.show', $inquiry)
                ->with('error', 'Inquiry sudah final dan tidak dapat diubah statusnya.');
        }

        if ($inquiry->trashed()) {
            $inquiry->restore();

            return redirect()
                ->route('inquiries.index')
                ->with('success', 'Inquiry activated successfully.');
        }

        $inquiry->delete();

        return redirect()
            ->route('inquiries.index')
            ->with('success', 'Inquiry deactivated successfully.');
    }

    public function storeFollowUp(Request $request, Inquiry $inquiry)
    {
        $inquiry->loadMissing(['quotation:id,inquiry_id,status']);
        if (! $this->canManageFollowUp($inquiry)) {
            return $this->denyInquiryMutation($inquiry);
        }
        if ($inquiry->isFinal()) {
            return redirect()
                ->route('inquiries.show', $inquiry)
                ->with('error', 'Inquiry sudah final dan tidak dapat diubah.');
        }
        if ($this->isInquiryLockedByQuotation($inquiry)) {
            return redirect()
                ->route('inquiries.show', $inquiry)
                ->with('error', 'Inquiry is locked because the related quotation is approved/final.');
        }
        $validated = $request->validate([
            'due_date' => ['required', 'date'],
            'channel' => ['nullable', Rule::in(self::CHANNEL_OPTIONS)],
            'note' => ['nullable', 'string'],
        ]);

        $validated['created_by'] = auth()->id();
        $followUp = $inquiry->followUps()->create($validated);
        $this->syncFollowUpStatus($inquiry);
        $inquiry->logActivity('reminder_added', $inquiry, [
            'follow_up_id' => $followUp->id,
            'due_date' => $followUp->due_date?->format('Y-m-d'),
        ]);

        return redirect()
            ->route('inquiries.show', $inquiry)
            ->with('success', 'Follow-up reminder added successfully.');
    }

    public function markFollowUpDone(InquiryFollowUp $followUp)
    {
        $followUp->loadMissing(['inquiry.quotation:id,inquiry_id,status']);
        if ($followUp->inquiry && ! $this->canMarkFollowUpDone($followUp->inquiry)) {
            return $this->denyInquiryMutation($followUp->inquiry);
        }
        if ($followUp->inquiry?->isFinal()) {
            return redirect()
                ->route('inquiries.show', $followUp->inquiry_id)
                ->with('error', 'Inquiry sudah final dan tidak dapat diubah.');
        }
        if ($followUp->inquiry && $this->isInquiryLockedByQuotation($followUp->inquiry)) {
            return redirect()
                ->route('inquiries.show', $followUp->inquiry_id)
                ->with('error', 'Inquiry is locked because the related quotation is approved/final.');
        }
        $validated = request()->validate([
            'done_reason' => ['required', 'string', 'max:1000'],
        ]);
        $followUp->update([
            'is_done' => true,
            'done_at' => now(),
            'done_reason' => $validated['done_reason'],
        ]);

        $this->syncFollowUpStatus($followUp->inquiry);
        if ($followUp->inquiry) {
            $followUp->inquiry->logActivity('reminder_done', $followUp->inquiry, [
                'follow_up_id' => $followUp->id,
                'done_reason' => $validated['done_reason'],
            ]);
        }

        return redirect()
            ->route('inquiries.show', $followUp->inquiry_id)
            ->with('success', 'Follow-up marked as done.');
    }

    public function storeCommunication(Request $request, Inquiry $inquiry)
    {
        $inquiry->loadMissing(['quotation:id,inquiry_id,status']);
        if (! $this->canManageInquiry($inquiry, 'update')) {
            return $this->denyInquiryMutation($inquiry);
        }
        if ($inquiry->isFinal()) {
            return redirect()
                ->route('inquiries.show', $inquiry)
                ->with('error', 'Inquiry sudah final dan tidak dapat diubah.');
        }
        if ($this->isInquiryLockedByQuotation($inquiry)) {
            return redirect()
                ->route('inquiries.show', $inquiry)
                ->with('error', 'Inquiry is locked because the related quotation is approved/final.');
        }
        $validated = $request->validate([
            'channel' => ['required', Rule::in(self::CHANNEL_OPTIONS)],
            'summary' => ['required', 'string'],
            'contact_at' => ['nullable', 'date'],
        ]);

        $validated['created_by'] = auth()->id();

        $communication = $inquiry->communications()->create($validated);
        $inquiry->logActivity('communication_added', $inquiry, [
            'communication_id' => $communication->id,
            'channel' => $communication->channel,
        ]);

        return redirect()
            ->route('inquiries.show', $inquiry)
            ->with('success', 'Communication history added successfully.');
    }

    private function syncFollowUpStatus(Inquiry $inquiry): void
    {
        if ($inquiry->isFinal()) {
            return;
        }
        $hasActive = $inquiry->followUps()->where('is_done', false)->exists();
        if ($hasActive && $inquiry->status === 'draft') {
            $inquiry->update(['status' => 'processed']);
        }
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
        $status = (string) ($inquiry->quotation->status ?? '');
        return in_array($status, ['approved', Quotation::FINAL_STATUS], true);
    }

    private function canManageFollowUp(Inquiry $inquiry): bool
    {
        $user = auth()->user();
        if (! $user) {
            return false;
        }

        if ($user->hasRole(['Reservation', 'Director', 'Manager'])) {
            return true;
        }

        return $user->can('update', $inquiry);
    }

    private function canMarkFollowUpDone(Inquiry $inquiry): bool
    {
        $user = auth()->user();
        if (! $user) {
            return false;
        }

        if ($inquiry->isCreator($user)) {
            return true;
        }

        return (int) ($inquiry->assigned_to ?? 0) === (int) $user->id;
    }

    private function denyInquiryMutation(Inquiry $inquiry)
    {
        return redirect()
            ->route('inquiries.show', $inquiry)
            ->with('error', 'Hanya creator yang dapat mengubah atau menghapus inquiry ini.');
    }

    private function buildInquiryAuditSnapshot(Inquiry $inquiry): array
    {
        return [
            'inquiry_number' => (string) ($inquiry->inquiry_number ?? ''),
            'customer_id' => (int) ($inquiry->customer_id ?? 0),
            'source' => (string) ($inquiry->source ?? ''),
            'status' => (string) ($inquiry->status ?? ''),
            'priority' => (string) ($inquiry->priority ?? ''),
            'deadline' => optional($inquiry->deadline)->format('Y-m-d'),
            'assigned_to' => (int) ($inquiry->assigned_to ?? 0),
            'reminder_enabled' => (bool) ($inquiry->reminder_enabled ?? false),
            'notes' => trim((string) ($inquiry->notes ?? '')),
        ];
    }

}



