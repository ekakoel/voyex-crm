<?php

namespace App\Http\Controllers\Sales;

use App\Http\Controllers\Controller;
use App\Http\Requests\SaveQuotationValidationItemRequest;
use App\Http\Requests\SaveQuotationValidationProgressRequest;
use App\Http\Requests\UpdateQuotationValidationItemContactRequest;
use App\Http\Requests\ValidateSelectedQuotationItemsRequest;
use App\Models\Quotation;
use App\Models\QuotationItem;
use App\Services\QuotationValidationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class QuotationValidationController extends Controller
{
    public function __construct(
        private readonly QuotationValidationService $quotationValidationService
    ) {
    }

    public function show(Request $request, Quotation $quotation)
    {
        $this->authorizeValidation($request, $quotation);
        if (in_array((string) ($quotation->status ?? ''), ['final'], true)) {
            return redirect()
                ->route('quotations.show', $quotation)
                ->with('error', 'Validation changes are locked for final quotation.');
        }

        $data = $this->quotationValidationService->prepareValidationPageData($quotation);

        if (! (bool) ($data['progress']['requires_validation'] ?? false)) {
            return redirect()
                ->route('quotations.show', $quotation)
                ->with('error', 'This quotation has no validation-required items.');
        }

        return view('modules.quotations.validate', $data);
    }

    public function saveProgress(SaveQuotationValidationProgressRequest $request, Quotation $quotation): RedirectResponse
    {
        $this->authorizeValidation($request, $quotation);
        if (in_array((string) ($quotation->status ?? ''), ['final'], true)) {
            return redirect()->route('quotations.show', $quotation)->with('error', 'Validation changes are locked for final quotation.');
        }

        $payload = (array) ($request->validated('items') ?? []);
        $this->quotationValidationService->saveProgress($quotation, $payload, (int) $request->user()->id);

        return redirect()
            ->route('quotations.validate.show', $quotation)
            ->with('success', 'Validation progress saved.');
    }

    public function saveItem(SaveQuotationValidationItemRequest $request, Quotation $quotation, QuotationItem $item): RedirectResponse|JsonResponse
    {
        $this->authorizeValidation($request, $quotation);
        if (in_array((string) ($quotation->status ?? ''), ['final'], true)) {
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'message' => 'Validation changes are locked for final quotation.',
                ], 422);
            }

            return redirect()->route('quotations.show', $quotation)->with('error', 'Validation changes are locked for final quotation.');
        }

        $validated = $request->validated();
        $payload = (array) ($validated['items'][(string) $item->id] ?? $validated['items'][(int) $item->id] ?? $validated);

        $progress = $this->quotationValidationService->saveItem($quotation, $item, $payload, (int) $request->user()->id);

        if ($request->expectsJson() || $request->ajax()) {
            $item->refresh()->loadMissing('validator:id,name');

            return response()->json([
                'message' => 'Item validation saved.',
                'progress' => $progress,
                'item' => [
                    'id' => (int) $item->id,
                    'is_validated' => (bool) ($item->is_validated ?? false),
                    'validation_notes' => (string) ($item->validation_notes ?? ''),
                    'contract_rate' => (float) ($item->contract_rate ?? 0),
                    'markup_type' => (string) ($item->markup_type ?? 'fixed'),
                    'markup' => (float) ($item->markup ?? 0),
                    'updated_at' => optional($item->updated_at)->toIso8601String(),
                    'validator_name' => $item->validator?->name,
                ],
            ]);
        }

        return redirect()
            ->route('quotations.validate.show', $quotation)
            ->with('success', 'Item validation saved.');
    }

    public function validateSelected(ValidateSelectedQuotationItemsRequest $request, Quotation $quotation): RedirectResponse
    {
        $this->authorizeValidation($request, $quotation);
        if (in_array((string) ($quotation->status ?? ''), ['final'], true)) {
            return redirect()->route('quotations.show', $quotation)->with('error', 'Validation changes are locked for final quotation.');
        }

        $itemIds = array_map('intval', (array) ($request->validated('selected_item_ids') ?? []));
        $this->quotationValidationService->validateSelected($quotation, $itemIds, (int) $request->user()->id);

        return redirect()
            ->route('quotations.validate.show', $quotation)
            ->with('success', 'Selected items are validated.');
    }

    public function finalize(Request $request, Quotation $quotation): RedirectResponse
    {
        $this->authorizeValidation($request, $quotation);
        if (in_array((string) ($quotation->status ?? ''), ['final'], true)) {
            return redirect()->route('quotations.show', $quotation)->with('error', 'Validation changes are locked for final quotation.');
        }

        $this->quotationValidationService->finalize($quotation, (int) $request->user()->id);

        return redirect()
            ->route('quotations.show', $quotation)
            ->with('success', 'Quotation validation completed.');
    }

    public function itemDetailJson(Request $request, Quotation $quotation, QuotationItem $item): JsonResponse
    {
        $this->authorizeValidation($request, $quotation);

        $payload = $this->quotationValidationService->buildValidationItemDetail($quotation, $item);

        return response()->json($payload);
    }

    public function updateItemContact(UpdateQuotationValidationItemContactRequest $request, Quotation $quotation, QuotationItem $item): JsonResponse|RedirectResponse
    {
        $this->authorizeValidation($request, $quotation);
        if (in_array((string) ($quotation->status ?? ''), ['final'], true)) {
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'message' => 'Validation changes are locked for final quotation.',
                ], 422);
            }

            return redirect()->route('quotations.show', $quotation)->with('error', 'Validation changes are locked for final quotation.');
        }

        $payload = $this->quotationValidationService->updateItemContact(
            $quotation,
            $item,
            $request->validated(),
            (int) $request->user()->id
        );

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'message' => 'Contact details updated.',
                'contact' => $payload['contact'] ?? [],
                'item' => $payload['item'] ?? [],
            ]);
        }

        return redirect()
            ->route('quotations.validate.show', $quotation)
            ->with('success', 'Contact details updated.');
    }

    private function authorizeValidation(Request $request, Quotation $quotation): void
    {
        $user = $request->user();
        abort_if(! $user, 403);
        abort_unless($this->quotationValidationService->isValidationActor($user), 403);

        $this->authorize('validateQuotation', $quotation);
    }
}
