<?php

namespace App\Http\Controllers\Sales;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Inquiry;
use App\Models\InquiryCommunication;
use App\Models\InquiryFollowUp;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class InquiryController extends Controller
{
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
        $query = Inquiry::query()->with(['customer', 'assignedUser']);

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

        $perPage = (int) request('per_page', 10);
        $perPage = $perPage > 0 && $perPage <= 100 ? $perPage : 10;

        $inquiries = $query->latest()->paginate($perPage)->withQueryString();
        $customers = Customer::query()->orderBy('name')->get();
        $assignees = User::role(['Sales Manager', 'Sales Agent'])->orderBy('name')->get();

        $sourceLabels = self::SOURCE_LABELS;

        return view('modules.inquiries.index', compact('inquiries', 'customers', 'assignees', 'sourceLabels'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $customers = Customer::query()->orderBy('name')->get();
        $assignees = User::role(['Sales Manager', 'Sales Agent'])->orderBy('name')->get();

        $sourceLabels = self::SOURCE_LABELS;
        $channelLabels = self::CHANNEL_LABELS;

        return view('modules.inquiries.create', compact('customers', 'assignees', 'sourceLabels', 'channelLabels'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'customer_id' => ['required', 'exists:customers,id'],
            'source' => ['nullable', Rule::in(self::SOURCE_OPTIONS)],
            'status' => ['required', Rule::in(['new', 'follow_up', 'quoted', 'converted', 'closed'])],
            'priority' => ['required', Rule::in(['low', 'normal', 'high'])],
            'assigned_to' => ['nullable', 'exists:users,id'],
            'deadline' => ['nullable', 'date'],
            'notes' => ['nullable', 'string'],
            'reminder_enabled' => ['nullable', 'boolean'],
        ]);
        $validated['reminder_enabled'] = $request->boolean('reminder_enabled');

        Inquiry::query()->create($validated);

        return redirect()
            ->route('inquiries.index')
            ->with('success', 'Inquiry created successfully.');
    }

    /**
     * Display the specified resource.
     */
    public function show(Inquiry $inquiry)
    {
        $inquiry->load(['customer', 'assignedUser']);
        $followUps = $inquiry->followUps()->orderByDesc('due_date')->get();
        $communications = $inquiry->communications()->with('creator')->orderByDesc('contact_at')->get();
        $channelLabels = self::CHANNEL_LABELS;
        $sourceLabels = self::SOURCE_LABELS;

        return view('modules.inquiries.show', compact('inquiry', 'followUps', 'communications', 'channelLabels', 'sourceLabels'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Inquiry $inquiry)
    {
        $customers = Customer::query()->orderBy('name')->get();
        $assignees = User::role(['Sales Manager', 'Sales Agent'])->orderBy('name')->get();

        $sourceLabels = self::SOURCE_LABELS;

        return view('modules.inquiries.edit', compact('inquiry', 'customers', 'assignees', 'sourceLabels'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Inquiry $inquiry)
    {
        $validated = $request->validate([
            'customer_id' => ['required', 'exists:customers,id'],
            'source' => ['nullable', Rule::in(self::SOURCE_OPTIONS)],
            'status' => ['required', Rule::in(['new', 'follow_up', 'quoted', 'converted', 'closed'])],
            'priority' => ['required', Rule::in(['low', 'normal', 'high'])],
            'assigned_to' => ['nullable', 'exists:users,id'],
            'deadline' => ['nullable', 'date'],
            'notes' => ['nullable', 'string'],
            'reminder_enabled' => ['nullable', 'boolean'],
        ]);
        $validated['reminder_enabled'] = $request->boolean('reminder_enabled');

        $inquiry->update($validated);
        $this->syncFollowUpStatus($inquiry);

        return redirect()
            ->route('inquiries.index')
            ->with('success', 'Inquiry updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Inquiry $inquiry)
    {
        $inquiry->delete();

        return redirect()
            ->route('inquiries.index')
            ->with('success', 'Inquiry deleted successfully.');
    }

    public function storeFollowUp(Request $request, Inquiry $inquiry)
    {
        $validated = $request->validate([
            'due_date' => ['required', 'date'],
            'channel' => ['nullable', Rule::in(self::CHANNEL_OPTIONS)],
            'note' => ['nullable', 'string'],
        ]);

        $inquiry->followUps()->create($validated);
        $this->syncFollowUpStatus($inquiry);

        return redirect()
            ->route('inquiries.show', $inquiry)
            ->with('success', 'Follow-up reminder added successfully.');
    }

    public function markFollowUpDone(InquiryFollowUp $followUp)
    {
        $followUp->update([
            'is_done' => true,
            'done_at' => now(),
        ]);

        $this->syncFollowUpStatus($followUp->inquiry);

        return redirect()
            ->route('inquiries.show', $followUp->inquiry_id)
            ->with('success', 'Follow-up marked as done.');
    }

    public function storeCommunication(Request $request, Inquiry $inquiry)
    {
        $validated = $request->validate([
            'channel' => ['required', Rule::in(self::CHANNEL_OPTIONS)],
            'summary' => ['required', 'string'],
            'contact_at' => ['nullable', 'date'],
        ]);

        $validated['created_by'] = auth()->id();

        $inquiry->communications()->create($validated);

        return redirect()
            ->route('inquiries.show', $inquiry)
            ->with('success', 'Communication history added successfully.');
    }

    private function syncFollowUpStatus(Inquiry $inquiry): void
    {
        $hasActive = $inquiry->followUps()->where('is_done', false)->exists();
        if ($hasActive && $inquiry->status === 'new') {
            $inquiry->update(['status' => 'follow_up']);
        }
    }
}



