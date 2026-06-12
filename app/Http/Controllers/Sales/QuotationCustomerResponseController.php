<?php

namespace App\Http\Controllers\Sales;

use App\Http\Controllers\Controller;
use App\Models\Quotation;
use App\Models\QuotationCustomerResponse;
use App\Services\Quotation\QuotationCustomerResponseService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class QuotationCustomerResponseController extends Controller
{
    public function __construct(
        private readonly QuotationCustomerResponseService $responseService
    ) {
    }

    public function store(Request $request, Quotation $quotation)
    {
        $user = $request->user();
        if (! $user || ! $user->can('update', $quotation)) {
            return redirect()->route('quotations.show', $quotation)->with('error', 'You do not have permission to add customer response.');
        }

        $validated = $request->validate([
            'response_channel' => ['required', 'string', Rule::in(['WhatsApp', 'Email', 'WeChat', 'Line', 'Phone', 'Telegram', 'Manual', 'Other'])],
            'response_status' => ['required', 'string', Rule::in([
                QuotationCustomerResponse::STATUS_REVISION_REQUESTED,
                QuotationCustomerResponse::STATUS_APPROVED,
                QuotationCustomerResponse::STATUS_CANCELLED,
                QuotationCustomerResponse::STATUS_REJECTED,
            ])],
            'response_note' => ['nullable', 'string', 'max:5000'],
        ]);

        $this->responseService->record($quotation, $validated, (int) $user->id);

        return redirect()->route('quotations.show', $quotation)->with('success', 'Customer response recorded.');
    }

    public function markSelectedUsedForRevision(Request $request, Quotation $quotation)
    {
        $user = $request->user();
        if (! $user || ! $user->can('update', $quotation)) {
            return redirect()->route('quotations.edit', $quotation)->with('error', 'You do not have permission to update customer response.');
        }

        $validated = $request->validate([
            'customer_response_ids' => ['required', 'array', 'min:1'],
            'customer_response_ids.*' => ['integer', 'min:1'],
        ]);

        $handledCount = $this->responseService->markManyUsedForRevision(
            $quotation,
            $validated['customer_response_ids'] ?? [],
            (int) $user->id
        );

        return redirect()->back()->with('success', $handledCount . ' customer response(s) marked as handled for this revision.');
    }
}
