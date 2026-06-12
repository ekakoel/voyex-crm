<?php

namespace App\Support;

use App\Models\Booking;
use App\Models\Quotation;
use App\Models\User;
use App\Services\ModuleService;
use App\Support\Concerns\ResolvesInquiryHandler;
use App\Support\Workflow\QuotationStatusNormalizer;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;

class QuotationActionResolver
{
    use ResolvesInquiryHandler;

    /**
     * @var array<string, array{label: string, icon: string, style: string}>
     */
    private const ACTION_CATALOG = [
        'view_summary' => ['label' => 'View Summary', 'icon' => 'fa-list-check', 'style' => 'secondary'],
        'duplicate_or_create_new_quotation' => ['label' => 'Create New Quotation', 'icon' => 'fa-copy', 'style' => 'secondary'],
        'edit_quotation' => ['label' => 'Revise Quotation', 'icon' => 'fa-pen-to-square', 'style' => 'primary'],
        'submit_validation' => ['label' => 'Submit Validation', 'icon' => 'fa-clipboard-check', 'style' => 'secondary'],
        'validate_quotation' => ['label' => 'Validate Quotation', 'icon' => 'fa-clipboard-check', 'style' => 'primary'],
        'continue_validation' => ['label' => 'Continue Validation', 'icon' => 'fa-clipboard-check', 'style' => 'primary'],
        'view_validation_progress' => ['label' => 'View Validation Progress', 'icon' => 'fa-chart-simple', 'style' => 'secondary'],
        'revise_quotation' => ['label' => 'Edit Quotation', 'icon' => 'fa-pen-nib', 'style' => 'primary'],
        'start_revision' => ['label' => 'Start Revision', 'icon' => 'fa-code-branch', 'style' => 'primary'],
        'finish_revision' => ['label' => 'Finish Revision', 'icon' => 'fa-flag-checkered', 'style' => 'primary'],
        'submit_revalidation' => ['label' => 'Revalidate', 'icon' => 'fa-clipboard-check', 'style' => 'secondary'],
        'revalidate_quotation' => ['label' => 'Revalidate', 'icon' => 'fa-clipboard-check', 'style' => 'primary'],
        'view_expired_items' => ['label' => 'View Expired Items', 'icon' => 'fa-triangle-exclamation', 'style' => 'secondary'],
        'preview_download_pdf' => ['label' => 'Preview / Download PDF', 'icon' => 'fa-file-pdf', 'style' => 'secondary'],
        'mark_as_sent' => ['label' => 'Mark as Sent', 'icon' => 'fa-paper-plane', 'style' => 'primary'],
        'add_follow_up' => ['label' => 'Add Follow-up', 'icon' => 'fa-headset', 'style' => 'primary'],
        'add_customer_response' => ['label' => 'Add Customer Response', 'icon' => 'fa-reply', 'style' => 'primary'],
        'set_pending' => ['label' => 'Set Pending', 'icon' => 'fa-clock', 'style' => 'secondary'],
        'mark_lost' => ['label' => 'Mark as Lost / No Response', 'icon' => 'fa-circle-xmark', 'style' => 'danger'],
        'mark_cancelled' => ['label' => 'Mark as Cancelled', 'icon' => 'fa-ban', 'style' => 'danger'],
        'create_booking' => ['label' => 'Create Booking', 'icon' => 'fa-calendar-check', 'style' => 'primary'],
        'create_revision' => ['label' => 'Create Revision', 'icon' => 'fa-code-branch', 'style' => 'secondary'],
        'create_revision_from_booking_issue' => ['label' => 'Create Revision from Booking Issue', 'icon' => 'fa-code-branch', 'style' => 'primary'],
        'revalidate_replacement_items' => ['label' => 'Revalidate Replacement Items', 'icon' => 'fa-clipboard-check', 'style' => 'secondary'],
        'view_booking' => ['label' => 'View Booking', 'icon' => 'fa-suitcase-rolling', 'style' => 'primary'],
        'vendor_confirmation' => ['label' => 'Vendor Confirmation', 'icon' => 'fa-handshake', 'style' => 'secondary'],
        'view_invoice' => ['label' => 'View Invoice', 'icon' => 'fa-file-invoice', 'style' => 'primary'],
        'record_payment' => ['label' => 'Record Payment', 'icon' => 'fa-money-bill-wave', 'style' => 'secondary'],
        'add_operation_adjustment' => ['label' => 'Add Operation Adjustment', 'icon' => 'fa-screwdriver-wrench', 'style' => 'secondary'],
        'review_operation_adjustments' => ['label' => 'Review Operation Adjustments', 'icon' => 'fa-list-check', 'style' => 'primary'],
        'generate_final_invoice' => ['label' => 'Generate Final Invoice', 'icon' => 'fa-file-invoice-dollar', 'style' => 'secondary'],
        'view_final_invoice' => ['label' => 'View Final Invoice', 'icon' => 'fa-file-invoice-dollar', 'style' => 'primary'],
        'record_final_payment' => ['label' => 'Record Final Payment', 'icon' => 'fa-money-bill-wave', 'style' => 'secondary'],
    ];

    /**
     * @return array<int, array<string, mixed>>
     */
    public function availableActions(Quotation $quotation, ?User $user = null): array
    {
        $status = Quotation::normalizeStatus((string) ($quotation->status ?? 'draft'));
        $logicalStatus = QuotationStatusNormalizer::normalize($status);
        $bookingsModuleEnabled = ModuleService::isEnabledStatic('bookings');
        $validationStatus = (string) ($quotation->validation_status ?? '');
        $approvalStatus = (string) ($quotation->approval_status ?? '');
        $followUpStatus = (string) ($quotation->follow_up_status ?? '');
        $bookingStatus = (string) ($quotation->booking_status ?? '');
        $invoiceStatus = (string) ($quotation->invoice_status ?? '');
        $paymentStatus = (string) ($quotation->payment_status ?? '');
        $operationStatus = (string) ($quotation->operation_status ?? '');

        $actions = [];
        $booking = $this->latestBooking($quotation);
        $invoice = $booking ? $this->latestInvoice((int) $booking->id) : null;

        if ($user && ! $this->canUpdate($quotation, $user)) {
            $this->addStandard($actions, $quotation, 'view_summary', 'quotations.show', 'GET', 'secondary');
            return $this->sorted($actions);
        }

        if (in_array($logicalStatus, ['completed'], true)) {
            $this->addStandard($actions, $quotation, 'view_summary', 'quotations.show', 'GET', 'secondary');
            return $this->sorted($actions);
        }

        if (in_array($logicalStatus, ['cancelled', 'lost', 'rejected'], true)) {
            $this->addStandard($actions, $quotation, 'view_summary', 'quotations.show', 'GET', 'secondary');
            $this->addStandard($actions, $quotation, 'duplicate_or_create_new_quotation', 'quotations.create', 'GET', 'secondary');
            return $this->sorted($actions);
        }

        if ($logicalStatus === 'draft') {
            $this->addStandard($actions, $quotation, 'edit_quotation', 'quotations.edit', 'GET', 'primary', null, null, $this->canUpdate($quotation, $user));
            $this->addStandard($actions, $quotation, 'submit_validation', 'quotations.validate.show', 'GET', 'secondary', null, null, $this->canValidate($user));

            return $this->sorted($actions);
        }

        if ($logicalStatus === 'revision_requested'
            || ($logicalStatus !== 'under_revision' && ($approvalStatus === 'revision_requested' || $followUpStatus === 'revision_requested'))
            || $this->hasPendingRevisionResponse($quotation)) {
            $this->addStandard($actions, $quotation, 'start_revision', 'quotations.request-revision', 'POST', 'primary', null, null, $this->canUpdate($quotation, $user));
            $this->addStandardModal($actions, 'add_customer_response', 'quotation-customer-response-modal', 'secondary', $this->canUpdate($quotation, $user));
            $this->addStandardModal($actions, 'add_follow_up', 'quotation-follow-up-modal', 'secondary', $this->canUpdate($quotation, $user));

            return $this->sorted($actions);
        }

        if ($this->isReadyToSend($logicalStatus)) {
            $this->addStandard($actions, $quotation, 'preview_download_pdf', 'quotations.pdf', 'GET', 'secondary');
            $this->addStandard($actions, $quotation, 'mark_as_sent', 'quotations.mark-sent', 'POST', 'primary', 'Mark this quotation as sent?', null, $this->canUpdate($quotation, $user));
            $this->addStandard($actions, $quotation, 'revalidate_quotation', 'quotations.validate.show', 'GET', 'secondary', null, null, $this->canValidate($user));
            $this->addStandard($actions, $quotation, 'start_revision', 'quotations.request-revision', 'POST', 'secondary', null, null, $this->canUpdate($quotation, $user));
            $this->addStandardModal($actions, 'add_customer_response', 'quotation-customer-response-modal', 'secondary', $this->canUpdate($quotation, $user));

            return $this->sorted($actions);
        }

        if (($logicalStatus === 'need_validation' || $validationStatus === 'pending') && $logicalStatus !== 'under_revision') {
            $this->addStandard($actions, $quotation, 'validate_quotation', 'quotations.validate.show', 'GET', 'primary', null, null, $this->canValidate($user));
            $this->addStandard($actions, $quotation, 'edit_quotation', 'quotations.edit', 'GET', 'secondary', null, null, $this->canUpdate($quotation, $user));

            return $this->sorted($actions);
        }

        if ($validationStatus === 'partial') {
            $this->addStandard($actions, $quotation, 'continue_validation', 'quotations.validate.show', 'GET', 'primary', null, null, $this->canValidate($user));
            $this->addStandard($actions, $quotation, 'view_validation_progress', 'quotations.validate.show', 'GET', 'secondary', null, null, $this->canValidate($user));
        }

        if ($logicalStatus === 'under_revision') {
            $progress = $this->validationProgress($quotation);
            $this->addStandard($actions, $quotation, 'revise_quotation', 'quotations.edit', 'GET', 'primary', null, null, $this->canUpdate($quotation, $user));
            $this->addStandardModal($actions, 'add_customer_response', 'quotation-customer-response-modal', 'secondary', $this->canUpdate($quotation, $user));
            if (! (bool) ($progress['is_complete'] ?? false)) {
                $this->addStandard($actions, $quotation, 'submit_revalidation', 'quotations.validate.show', 'GET', 'secondary', null, null, $this->canValidate($user));
            } else {
                $this->addStandard($actions, $quotation, 'finish_revision', 'quotations.validate.finalize', 'POST', 'primary', 'Finish this quotation revision?', null, $this->canValidate($user));
            }

            return $this->sorted($actions);
        }

        if ($logicalStatus === 'need_revalidation' || in_array($validationStatus, ['needs_revalidation', 'expired'], true)) {
            $progress = $this->validationProgress($quotation);
            $this->addStandard($actions, $quotation, 'revalidate_quotation', 'quotations.validate.show', 'GET', 'primary', null, null, $this->canValidate($user));
            $this->addStandard($actions, $quotation, 'view_expired_items', 'quotations.validate.show', 'GET', 'secondary', null, null, $this->canValidate($user));
            $this->addStandard($actions, $quotation, 'edit_quotation', 'quotations.edit', 'GET', 'secondary', null, null, $this->canUpdate($quotation, $user));
            $this->addStandardModal($actions, 'add_customer_response', 'quotation-customer-response-modal', 'secondary', $this->canUpdate($quotation, $user));
            if ((bool) ($progress['is_complete'] ?? false)) {
                $this->addStandard($actions, $quotation, 'finish_revision', 'quotations.validate.finalize', 'POST', 'primary', 'Finish this quotation revision?', null, $this->canValidate($user));
            }

            return $this->sorted($actions);
        }

        if ($logicalStatus === 'sent') {
            if (! $this->followedUpToday($quotation)) {
                $this->addStandardModal($actions, 'add_follow_up', 'quotation-follow-up-modal', 'primary', $this->canUpdate($quotation, $user));
            }
            $this->addStandardModal($actions, 'add_customer_response', 'quotation-customer-response-modal', 'primary', $this->canUpdate($quotation, $user));
            $this->addStandard($actions, $quotation, 'preview_download_pdf', 'quotations.pdf', 'GET', 'secondary');
            $this->addStandard($actions, $quotation, 'set_pending', 'quotations.set-pending', 'POST', 'secondary', 'Set this quotation to pending?', null, $this->canSetPending($user));

            return $this->sorted($actions);
        }

        if ($this->isCustomerApproved($logicalStatus, $approvalStatus)) {
            $this->addStandard($actions, $quotation, 'create_booking', 'bookings.create', 'GET', 'primary', null, ['quotation_id' => (int) $quotation->id], $bookingsModuleEnabled && $this->canCreateBooking($quotation, $user));
            $this->addStandard($actions, $quotation, 'preview_download_pdf', 'quotations.pdf', 'GET', 'secondary');

            return $this->sorted($actions);
        }

        if ($logicalStatus === 'booking_issue' || $bookingStatus === 'issue') {
            $revisionActionKey = $bookingsModuleEnabled ? 'create_revision_from_booking_issue' : 'create_revision';
            $revisionConfirmMessage = $bookingsModuleEnabled ? 'Start itinerary revision from booking issue?' : 'Start itinerary revision?';
            $this->addStandard($actions, $quotation, $revisionActionKey, 'quotations.start-itinerary-revision', 'POST', 'primary', $revisionConfirmMessage, null, $this->canUpdate($quotation, $user));
            $this->addStandard($actions, $quotation, 'revalidate_replacement_items', 'quotations.validate.show', 'GET', 'secondary', null, null, $this->canValidate($user));
            $this->addStandardModal($actions, 'add_customer_response', 'quotation-customer-response-modal', 'secondary', $this->canUpdate($quotation, $user));

            return $this->sorted($actions);
        }

        if ($bookingsModuleEnabled && (in_array($logicalStatus, ['converted_to_booking', 'booking_in_progress'], true) || $bookingStatus === 'in_progress')) {
            if ($booking) {
                $this->addStandard($actions, $quotation, 'view_booking', 'bookings.show', 'GET', 'primary', null, ['booking' => $booking->id]);
                $this->addStandard($actions, $quotation, 'vendor_confirmation', 'bookings.show', 'GET', 'secondary', null, ['booking' => $booking->id]);
            }
        }

        if (in_array($invoiceStatus, ['issued', 'sent'], true) || in_array($paymentStatus, ['unpaid', 'partially_paid', 'waiting_payment'], true)) {
            if ($invoice) {
                $this->addStandard($actions, $quotation, 'view_invoice', 'invoices.show', 'GET', 'primary', null, ['invoice' => $invoice->id]);
                $this->addStandard($actions, $quotation, 'record_payment', 'payments.create', 'GET', 'secondary', null, ['invoice_id' => $invoice->id]);
            }
        }

        if ($bookingsModuleEnabled && ($logicalStatus === 'in_operation' || $operationStatus === 'in_operation')) {
            if ($booking) {
                $this->addStandard($actions, $quotation, 'add_operation_adjustment', 'bookings.show', 'GET', 'secondary', null, ['booking' => $booking->id]);
                $this->addStandard($actions, $quotation, 'view_booking', 'bookings.show', 'GET', 'primary', null, ['booking' => $booking->id]);
            }
        }

        if ($bookingsModuleEnabled && ($logicalStatus === 'operation_adjustment' || $operationStatus === 'adjustment_required')) {
            if ($booking) {
                $this->addStandard($actions, $quotation, 'review_operation_adjustments', 'bookings.show', 'GET', 'primary', null, ['booking' => $booking->id]);
                $this->addStandard($actions, $quotation, 'generate_final_invoice', 'bookings.invoices.final', 'POST', 'secondary', 'Generate final invoice?', ['booking' => $booking->id]);
            }
        }

        if ($logicalStatus === 'finalized' && $invoice) {
            $this->addStandard($actions, $quotation, 'view_final_invoice', 'invoices.show', 'GET', 'primary', null, ['invoice' => $invoice->id]);
            if (! in_array($paymentStatus, ['paid', 'overpaid'], true)) {
                $this->addStandard($actions, $quotation, 'record_final_payment', 'payments.create', 'GET', 'secondary', null, ['invoice_id' => $invoice->id]);
            }
        }

        if ($actions === []) {
            $this->addStandard($actions, $quotation, 'view_summary', 'quotations.show', 'GET', 'secondary');
        }

        return $this->sorted($actions);
    }

    private function addStandard(
        array &$actions,
        Quotation $quotation,
        string $key,
        string $routeName,
        string $method,
        string $priority,
        ?string $confirmMessage = null,
        ?array $parameters = null,
        bool $visible = true
    ): void {
        $meta = $this->actionMeta($key);
        $this->add(
            $actions,
            $quotation,
            $key,
            $meta['label'],
            $routeName,
            $method,
            $meta['icon'],
            $meta['style'],
            $priority,
            $confirmMessage,
            $parameters,
            $visible
        );
    }

    private function addStandardModal(
        array &$actions,
        string $key,
        string $modal,
        string $priority,
        bool $visible = true
    ): void {
        $meta = $this->actionMeta($key);
        $this->addModal(
            $actions,
            $key,
            $meta['label'],
            $modal,
            $meta['icon'],
            $meta['style'],
            $priority,
            $visible
        );
    }

    /**
     * @return array{label: string, icon: string, style: string}
     */
    private function actionMeta(string $key): array
    {
        return self::ACTION_CATALOG[$key] ?? ['label' => $key, 'icon' => 'fa-bolt', 'style' => 'secondary'];
    }

    private function add(array &$actions, Quotation $quotation, string $key, string $label, string $routeName, string $method, string $icon, string $style, string $priority, ?string $confirmMessage = null, ?array $parameters = null, bool $visible = true): void
    {
        if (! $visible || ! Route::has($routeName) || isset($actions[$key])) {
            return;
        }

        $actions[$key] = [
            'key' => $key,
            'label' => $label,
            'route' => $this->route($routeName, $quotation, $parameters),
            'method' => strtoupper($method),
            'icon' => $icon,
            'style' => $style,
            'priority' => $priority,
            'confirm_message' => $confirmMessage,
            'visible' => true,
            'payload' => $parameters && ! $this->isRouteParameterPayload($routeName, $parameters) ? $parameters : [],
        ];
    }

    private function addModal(array &$actions, string $key, string $label, string $modal, string $icon, string $style, string $priority, bool $visible = true): void
    {
        if (! $visible || isset($actions[$key])) {
            return;
        }

        $actions[$key] = [
            'key' => $key,
            'label' => $label,
            'route' => null,
            'method' => 'MODAL',
            'modal' => $modal,
            'icon' => $icon,
            'style' => $style,
            'priority' => $priority,
            'confirm_message' => null,
            'visible' => true,
            'payload' => [],
        ];
    }

    private function route(string $routeName, Quotation $quotation, ?array $parameters = null): string
    {
        $parameters ??= [];
        if ($this->isRouteParameterPayload($routeName, $parameters)) {
            return route($routeName, $parameters);
        }

        if (in_array($routeName, ['bookings.create', 'payments.create', 'quotations.create'], true)) {
            return route($routeName, $parameters);
        }

        return route($routeName, $quotation);
    }

    private function isRouteParameterPayload(string $routeName, array $parameters): bool
    {
        return in_array($routeName, ['bookings.show', 'invoices.show', 'bookings.invoices.final'], true);
    }

    private function sorted(array $actions): array
    {
        $weight = ['primary' => 10, 'secondary' => 20, 'danger' => 30, 'dropdown' => 40];

        return collect($actions)
            ->sortBy(fn (array $action): int => $weight[$action['priority'] ?? 'secondary'] ?? 50)
            ->values()
            ->all();
    }

    private function isReadyToSend(string $status): bool
    {
        return $status === 'ready_to_send';
    }

    private function isCustomerApproved(string $status, string $approvalStatus): bool
    {
        return in_array($status, ['customer_approved', 'approved'], true) || $approvalStatus === 'approved';
    }

    private function hasPendingRevisionResponse(Quotation $quotation): bool
    {
        if (! Schema::hasTable('quotation_customer_responses')) {
            return false;
        }

        return DB::table('quotation_customer_responses')
            ->where('quotation_id', (int) $quotation->id)
            ->where('requires_revision', true)
            ->where(function ($query): void {
                $query->where('is_used_for_revision', false)
                    ->orWhereNull('is_used_for_revision');
            })
            ->exists();
    }

    /**
     * @return array{required: int, validated: int, is_complete: bool}
     */
    private function validationProgress(Quotation $quotation): array
    {
        if (! Schema::hasTable('quotation_items')) {
            return ['required' => 0, 'validated' => 0, 'is_complete' => true];
        }

        $required = DB::table('quotation_items')
            ->where('quotation_id', (int) $quotation->id)
            ->where(function ($query): void {
                if (Schema::hasColumn('quotation_items', 'validation_required')) {
                    $query->orWhere('validation_required', true);
                }
                if (Schema::hasColumn('quotation_items', 'is_validation_required')) {
                    $query->orWhere('is_validation_required', true);
                }
            })
            ->count();

        $validated = DB::table('quotation_items')
            ->where('quotation_id', (int) $quotation->id)
            ->where(function ($query): void {
                if (Schema::hasColumn('quotation_items', 'validation_required')) {
                    $query->orWhere('validation_required', true);
                }
                if (Schema::hasColumn('quotation_items', 'is_validation_required')) {
                    $query->orWhere('is_validation_required', true);
                }
            })
            ->where(function ($query): void {
                $query->where('is_validated', true);
                if (Schema::hasColumn('quotation_items', 'validation_status')) {
                    $query->orWhere('validation_status', 'validated');
                }
            })
            ->count();

        return [
            'required' => (int) $required,
            'validated' => (int) $validated,
            'is_complete' => $required <= 0 || $validated >= $required,
        ];
    }

    private function latestBooking(Quotation $quotation): ?object
    {
        if (! Schema::hasTable('bookings')) {
            return null;
        }

        return DB::table('bookings')
            ->where('quotation_id', (int) $quotation->id)
            ->orderByDesc('id')
            ->first();
    }

    private function latestInvoice(int $bookingId): ?object
    {
        if (! Schema::hasTable('invoices')) {
            return null;
        }

        return DB::table('invoices')
            ->where('booking_id', $bookingId)
            ->orderByDesc('id')
            ->first();
    }

    private function canUpdate(Quotation $quotation, ?User $user): bool
    {
        return ! $user || $user->can('update', $quotation);
    }

    private function canValidate(?User $user): bool
    {
        return ! $user || $user->can('quotations.validate');
    }

    private function canSetPending(?User $user): bool
    {
        return ! $user || $user->can('quotations.set_pending');
    }

    private function canCreateBooking(Quotation $quotation, ?User $user): bool
    {
        if (! ModuleService::isEnabledStatic('bookings')) {
            return false;
        }

        if (! $user) {
            return $this->isCreateBookingEligible($quotation);
        }

        if (! $user->can('module.bookings.access')) {
            return false;
        }

        return $this->inquiryHandlerMatchesUser($quotation->inquiry, (int) $user->id)
            && $this->isCreateBookingEligible($quotation);
    }

    private function isCreateBookingEligible(Quotation $quotation): bool
    {
        if (! in_array((string) ($quotation->validation_status ?? ''), ['valid', 'validated'], true)) {
            return false;
        }

        if ($quotation->relationLoaded('booking')) {
            if ($quotation->booking) {
                return false;
            }
        } elseif ($quotation->booking()->exists()) {
            return false;
        }

        if ($quotation->relationLoaded('items')) {
            if ($quotation->items->isEmpty()) {
                return false;
            }
        } elseif (! $quotation->items()->exists()) {
            return false;
        }

        $revisionRootId = (int) ($quotation->revision_of_id ?: $quotation->id);

        return ! DB::table('bookings as b')
            ->join('quotations as q2', 'q2.id', '=', 'b.quotation_id')
            ->whereRaw('COALESCE(q2.revision_of_id, q2.id) = ?', [$revisionRootId])
            ->whereNotIn('b.status', ['cancelled', Booking::FINAL_STATUS])
            ->exists();
    }

    private function canReject(?User $user): bool
    {
        return ! $user || $user->can('quotations.reject');
    }

    private function followedUpToday(Quotation $quotation): bool
    {
        if (! $quotation->last_followed_up_at) {
            return false;
        }

        return $quotation->last_followed_up_at->isSameDay(now());
    }
}
