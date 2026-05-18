<?php

namespace App\Http\Controllers\Sales;

use App\Http\Controllers\Concerns\HandlesActivityTimelineAjax;
use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Inquiry;
use App\Models\Quotation;
use App\Services\ActivityAuditLogger;
use Illuminate\Http\Request;
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

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $searchKeyword = trim((string) request('q'));

        $query = Inquiry::query()
            ->withTrashed()
            ->with([
                'customer',
                'creator',
                'quotations:id,inquiry_id,status',
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

        $query->when(request('status'), fn ($q) => $q->where('status', request('status')));
        $query->when(request('priority'), fn ($q) => $q->where('priority', request('priority')));
        $query->when(request('customer_id'), fn ($q) => $q->where('customer_id', request('customer_id')));
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
        $sourceLabels = self::SOURCE_LABELS;

        return view('modules.inquiries.index', compact('inquiries', 'customers', 'sourceLabels'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $customers = Customer::query()->orderBy('name')->get();

        $sourceLabels = self::SOURCE_LABELS;

        return view('modules.inquiries.create', compact('customers', 'sourceLabels'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'customer_id' => ['required', 'exists:customers,id'],
            'source' => ['nullable', Rule::in(self::SOURCE_OPTIONS)],
            'priority' => ['required', Rule::in(['low', 'normal', 'high'])],
            'deadline' => ['nullable', 'date'],
            'notes' => ['nullable', 'string'],
        ]);
        $validated['reminder_enabled'] = true;
        $validated['status'] = 'draft';

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
        $inquiry->load(['customer', 'creator', 'quotations:id,inquiry_id,status']);
        $quotations = Quotation::query()
            ->where(function ($query) use ($inquiry): void {
                $query->where('inquiry_id', $inquiry->id)
                    ->orWhereHas('itinerary', function ($itineraryQuery) use ($inquiry): void {
                        $itineraryQuery->where('inquiry_id', $inquiry->id);
                    });
            })
            ->with('itinerary:id,title')
            ->orderByDesc('quotations.updated_at')
            ->get([
                'quotations.id',
                'quotations.quotation_number',
                'quotations.order_number',
                'quotations.status',
                'quotations.inquiry_id',
                'quotations.itinerary_id',
                'quotations.updated_at',
            ]);
        $activities = $inquiry->activities()
            ->with('user:id,name')
            ->latest()
            ->paginate(5, ['*'], 'activity_page')
            ->withQueryString();
        $sourceLabels = self::SOURCE_LABELS;
        $canManageInquiry = auth()->user()?->can('update', $inquiry) ?? false;

        if ($this->wantsActivityTimelineFragment($request)) {
            return $this->activityTimelineFragmentResponse($activities);
        }

        return view('modules.inquiries.show', compact('inquiry', 'quotations', 'activities', 'sourceLabels', 'canManageInquiry'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Request $request, Inquiry $inquiry)
    {
        $inquiry->loadMissing(['quotations:id,inquiry_id,status']);
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
        $customers = Customer::query()->orderBy('name')->get();
        $activities = $inquiry->activities()
            ->with('user:id,name')
            ->latest()
            ->paginate(5, ['*'], 'activity_page')
            ->withQueryString();

        $sourceLabels = self::SOURCE_LABELS;

        if ($this->wantsActivityTimelineFragment($request)) {
            return $this->activityTimelineFragmentResponse($activities);
        }

        return view('modules.inquiries.edit', compact('inquiry', 'customers', 'sourceLabels', 'activities'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Inquiry $inquiry)
    {
        $inquiry->loadMissing(['quotations:id,inquiry_id,status']);
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
        $validated = $request->validate([
            'customer_id' => ['required', 'exists:customers,id'],
            'source' => ['nullable', Rule::in(self::SOURCE_OPTIONS)],
            'priority' => ['required', Rule::in(['low', 'normal', 'high'])],
            'deadline' => ['nullable', 'date'],
            'notes' => ['nullable', 'string'],
        ]);
        $validated['reminder_enabled'] = true;

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
        $inquiry->loadMissing('quotations:id,inquiry_id,status');
        return $inquiry->quotations->contains(
            fn ($quotation) => in_array((string) ($quotation->status ?? ''), ['approved', Quotation::FINAL_STATUS], true)
        );
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
            'deadline' => optional($inquiry->deadline)->format('Y-m-d'),
            'reminder_enabled' => (bool) ($inquiry->reminder_enabled ?? false),
            'notes' => trim((string) ($inquiry->notes ?? '')),
        ];
    }

}
