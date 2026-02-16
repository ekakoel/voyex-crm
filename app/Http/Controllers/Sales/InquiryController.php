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

        return view('sales.inquiries.index', compact('inquiries', 'customers', 'assignees'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $customers = Customer::query()->orderBy('name')->get();
        $assignees = User::role(['Sales Manager', 'Sales Agent'])->orderBy('name')->get();

        return view('sales.inquiries.create', compact('customers', 'assignees'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'customer_id' => ['required', 'exists:customers,id'],
            'source' => ['nullable', Rule::in(['phone', 'email', 'website', 'walk-in', 'other'])],
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
            ->route('sales.inquiries.index')
            ->with('success', 'Inquiry created successfully.');
    }

    /**
     * Display the specified resource.
     */
    public function show(Inquiry $inquiry)
    {
        return redirect()->route('sales.inquiries.edit', $inquiry);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Inquiry $inquiry)
    {
        $customers = Customer::query()->orderBy('name')->get();
        $assignees = User::role(['Sales Manager', 'Sales Agent'])->orderBy('name')->get();
        $followUps = $inquiry->followUps()->orderByDesc('due_date')->get();
        $communications = $inquiry->communications()->orderByDesc('contact_at')->get();

        return view('sales.inquiries.edit', compact('inquiry', 'customers', 'assignees', 'followUps', 'communications'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Inquiry $inquiry)
    {
        $validated = $request->validate([
            'customer_id' => ['required', 'exists:customers,id'],
            'source' => ['nullable', Rule::in(['phone', 'email', 'website', 'walk-in', 'other'])],
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
            ->route('sales.inquiries.index')
            ->with('success', 'Inquiry updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Inquiry $inquiry)
    {
        $inquiry->delete();

        return redirect()
            ->route('sales.inquiries.index')
            ->with('success', 'Inquiry deleted successfully.');
    }

    public function storeFollowUp(Request $request, Inquiry $inquiry)
    {
        $validated = $request->validate([
            'due_date' => ['required', 'date'],
            'channel' => ['nullable', Rule::in(['phone', 'email', 'whatsapp', 'meeting', 'other'])],
            'note' => ['nullable', 'string'],
        ]);

        $inquiry->followUps()->create($validated);
        $this->syncFollowUpStatus($inquiry);

        return redirect()
            ->route('sales.inquiries.edit', $inquiry)
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
            ->route('sales.inquiries.edit', $followUp->inquiry_id)
            ->with('success', 'Follow-up marked as done.');
    }

    public function storeCommunication(Request $request, Inquiry $inquiry)
    {
        $validated = $request->validate([
            'channel' => ['required', Rule::in(['phone', 'email', 'whatsapp', 'meeting', 'other'])],
            'summary' => ['required', 'string'],
            'contact_at' => ['nullable', 'date'],
        ]);

        $validated['created_by'] = auth()->id();

        $inquiry->communications()->create($validated);

        return redirect()
            ->route('sales.inquiries.edit', $inquiry)
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
