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
use App\Services\Quotation\QuotationStatusService;
use App\Services\Quotation\QuotationWorkflowService;
use App\Support\Workflow\QuotationStatusNormalizer;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class QuotationValidationController extends Controller
{
    public function __construct(
        private readonly QuotationValidationService $quotationValidationService,
        private readonly QuotationWorkflowService $quotationWorkflowService,
        private readonly QuotationStatusService $quotationStatusService
    ) {
    }

    public function show(Request $request, Quotation $quotation)
    {
        $this->authorizeValidation($request, $quotation);
        if ($this->isValidationLocked($quotation)) {
            return redirect()
                ->route('quotations.show', $quotation)
                ->with('error', $this->validationLockedMessage($quotation));
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
        if ($this->isValidationLocked($quotation)) {
            return redirect()->route('quotations.show', $quotation)->with('error', $this->validationLockedMessage($quotation));
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
        if ($this->isValidationLocked($quotation)) {
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'message' => $this->validationLockedMessage($quotation),
                ], 422);
            }

            return redirect()->route('quotations.show', $quotation)->with('error', $this->validationLockedMessage($quotation));
        }

        $validated = $request->validated();
        $payload = (array) ($validated['items'][(string) $item->id] ?? $validated['items'][(int) $item->id] ?? $validated);

        $result = $this->quotationValidationService->saveItem($quotation, $item, $payload, (int) $request->user()->id);
        $progress = (array) ($result['progress'] ?? []);

        if ($request->expectsJson() || $request->ajax()) {
            $affectedItems = collect($result['items'] ?? [$item])
                ->map(function ($affectedItem) {
                    if ($affectedItem instanceof QuotationItem) {
                        return $affectedItem->fresh()->loadMissing('validator:id,name');
                    }

                    return null;
                })
                ->filter()
                ->values();

            $serializeItem = static function (QuotationItem $affectedItem): array {
                return [
                    'id' => (int) $affectedItem->id,
                    'qty' => (int) ($affectedItem->qty ?? 1),
                    'is_validated' => (bool) ($affectedItem->is_validated ?? false),
                    'validation_notes' => (string) ($affectedItem->validation_notes ?? ''),
                    'contract_rate' => (float) ($affectedItem->contract_rate ?? 0),
                    'markup_type' => (string) ($affectedItem->markup_type ?? 'fixed'),
                    'markup' => (float) ($affectedItem->markup ?? 0),
                    'updated_at' => optional($affectedItem->updated_at)->toIso8601String(),
                    'validator_name' => $affectedItem->validator?->name,
                ];
            };

            $primaryItem = $affectedItems->firstWhere('id', (int) $item->id) ?? $item->refresh()->loadMissing('validator:id,name');

            return response()->json([
                'message' => 'Item validation saved.',
                'progress' => $progress,
                'item' => $serializeItem($primaryItem),
                'items' => $affectedItems->map($serializeItem)->values()->all(),
            ]);
        }

        return redirect()
            ->route('quotations.validate.show', $quotation)
            ->with('success', 'Item validation saved.');
    }

    public function validateSelected(ValidateSelectedQuotationItemsRequest $request, Quotation $quotation): RedirectResponse
    {
        $this->authorizeValidation($request, $quotation);
        if ($this->isValidationLocked($quotation)) {
            return redirect()->route('quotations.show', $quotation)->with('error', $this->validationLockedMessage($quotation));
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
        if ($this->isValidationLocked($quotation)) {
            return redirect()->route('quotations.show', $quotation)->with('error', $this->validationLockedMessage($quotation));
        }

        $this->quotationValidationService->finalize($quotation, (int) $request->user()->id);
        $this->quotationStatusService->syncStatus($quotation);
        $quotation->refresh();
        $current = Quotation::normalizeStatus((string) ($quotation->status ?? ''));
        $this->quotationWorkflowService->syncDimensions($quotation, (int) $request->user()->id, [
            'action' => 'validation_finalize',
            'status' => $current,
        ]);

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
        if ($this->isValidationLocked($quotation)) {
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'message' => $this->validationLockedMessage($quotation),
                ], 422);
            }

            return redirect()->route('quotations.show', $quotation)->with('error', $this->validationLockedMessage($quotation));
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
                'history' => $payload['history'] ?? [],
                'service_history' => $payload['service_history'] ?? [],
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

    private function isValidationLocked(Quotation $quotation): bool
    {
        return in_array(QuotationStatusNormalizer::normalize((string) ($quotation->status ?? '')), [
            Quotation::STATUS_SENT,
            Quotation::STATUS_APPROVED,
            Quotation::STATUS_CUSTOMER_APPROVED,
            Quotation::STATUS_CONVERTED_TO_BOOKING,
            Quotation::STATUS_BOOKING_CREATED,
            Quotation::STATUS_IN_OPERATION,
            Quotation::STATUS_COMPLETED,
            Quotation::STATUS_CANCELLED,
            Quotation::STATUS_LOST,
            Quotation::STATUS_REJECTED,
        ], true);
    }

    private function validationLockedMessage(Quotation $quotation): string
    {
        $status = QuotationStatusNormalizer::normalize((string) ($quotation->status ?? ''));

        return match ($status) {
            Quotation::STATUS_SENT => ui_phrase('Validation is locked because the quotation has been sent. Record follow-up or customer response to continue.'),
            Quotation::STATUS_APPROVED,
            Quotation::STATUS_CUSTOMER_APPROVED => ui_phrase('Validation is locked because the customer has approved this quotation. Continue with booking preparation.'),
            Quotation::STATUS_CONVERTED_TO_BOOKING,
            Quotation::STATUS_BOOKING_CREATED => ui_phrase('Validation is locked because this quotation is already connected to booking. Manage service changes from the booking workflow.'),
            Quotation::STATUS_IN_OPERATION => ui_phrase('Validation is locked because this quotation has moved into operation. Use operation adjustment if changes are required.'),
            Quotation::STATUS_COMPLETED => ui_phrase('Validation is locked because this quotation workflow is completed.'),
            Quotation::STATUS_CANCELLED,
            Quotation::STATUS_LOST,
            Quotation::STATUS_REJECTED => ui_phrase('Validation is locked because this quotation is closed as :status.', ['status' => \App\Support\Workflow\QuotationWorkflow::label($status)]),
            default => ui_phrase('Validation is locked for the current quotation status: :status.', ['status' => \App\Support\Workflow\QuotationWorkflow::label($status)]),
        };
    }
}
