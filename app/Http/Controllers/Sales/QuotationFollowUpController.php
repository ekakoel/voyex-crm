<?php

namespace App\Http\Controllers\Sales;

use App\Http\Controllers\Controller;
use App\Models\Quotation;
use App\Services\Quotation\QuotationFollowUpService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class QuotationFollowUpController extends Controller
{
    public function __construct(
        private readonly QuotationFollowUpService $followUpService
    ) {
    }

    public function store(Request $request, Quotation $quotation)
    {
        $user = $request->user();
        if (! $user || ! $user->can('update', $quotation)) {
            return redirect()->route('quotations.show', $quotation)->with('error', 'You do not have permission to add quotation follow-up.');
        }

        $validated = $request->validate([
            'channel' => ['required', 'string', Rule::in(['WhatsApp', 'Email', 'WeChat', 'Line', 'Phone', 'Telegram', 'Manual', 'Other'])],
            'follow_up_note' => ['nullable', 'string', 'max:5000'],
            'follow_up_at' => ['nullable', 'date'],
        ]);

        $this->followUpService->record($quotation, $validated, (int) $user->id);

        return redirect()->route('quotations.show', $quotation)->with('success', 'Quotation follow-up recorded.');
    }
}
