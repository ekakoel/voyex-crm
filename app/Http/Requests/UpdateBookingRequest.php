<?php

namespace App\Http\Requests;

use App\Models\Quotation;
use App\Support\Concerns\ResolvesInquiryHandler;
use Illuminate\Foundation\Http\FormRequest;

class UpdateBookingRequest extends FormRequest
{
    use ResolvesInquiryHandler;

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'quotation_id' => [
                'required',
                'exists:quotations,id',
                \Illuminate\Validation\Rule::unique('bookings', 'quotation_id')->ignore($this->route('booking')?->id),
            ],
            'travel_date' => ['required', 'date'],
            'pax_adult' => ['required', 'integer', 'min:0'],
            'pax_child' => ['required', 'integer', 'min:0'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.quotation_item_id' => ['nullable', 'integer', 'exists:quotation_items,id'],
            'items.*.description' => ['required', 'string', 'max:255'],
            'items.*.qty' => ['required', 'integer', 'min:1'],
            'items.*.unit_price' => ['required', 'numeric', 'min:0'],
            'items.*.serviceable_type' => ['nullable', 'string', 'max:255'],
            'items.*.serviceable_id' => ['nullable', 'integer', 'min:1'],
            'items.*.day_number' => ['nullable', 'integer', 'min:1'],
            'items.*.serviceable_meta' => ['nullable'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $quotationId = (int) $this->input('quotation_id');
            if ($quotationId <= 0) {
                return;
            }

            $currentBookingId = (int) ($this->route('booking')?->id ?? 0);
            $quotation = Quotation::query()
                ->withCount('items')
                ->with('booking:id,quotation_id', 'inquiry:id,handled_by,assigned_to,created_by')
                ->find($quotationId);

            if (! $quotation) {
                return;
            }

            if (! $this->inquiryHandlerMatchesUser($quotation->inquiry, (int) ($this->user()?->id ?? 0))) {
                $validator->errors()->add('quotation_id', ui_phrase('Inquiry is handled by another user.'));
            }

            if (! in_array((string) ($quotation->validation_status ?? 'pending'), ['valid', 'validated'], true)) {
                $validator->errors()->add('quotation_id', ui_phrase('Selected quotation validation must be 100% before booking.'));
            }

            if ((int) ($quotation->items_count ?? 0) <= 0) {
                $validator->errors()->add('quotation_id', ui_phrase('Selected quotation has no items to be booked.'));
            }

            $paxAdult = (int) $this->input('pax_adult', 0);
            $paxChild = (int) $this->input('pax_child', 0);
            if (($paxAdult + $paxChild) <= 0) {
                $validator->errors()->add('pax_adult', ui_phrase('Pax adult and child cannot both be zero.'));
            }

            $linkedBookingId = (int) ($quotation->booking?->id ?? 0);
            if ($linkedBookingId > 0 && $linkedBookingId !== $currentBookingId) {
                $validator->errors()->add('quotation_id', ui_phrase('Selected quotation is already linked to another booking.'));
            }

            $revisionRootId = (int) ($quotation->revision_of_id ?: $quotation->id);
            $hasActiveChainBooking = \App\Models\Booking::query()
                ->whereNotIn('status', ['cancelled', \App\Models\Booking::FINAL_STATUS])
                ->where('id', '!=', $currentBookingId)
                ->whereHas('quotation', function ($query) use ($revisionRootId): void {
                    $query->whereRaw('COALESCE(revision_of_id, id) = ?', [$revisionRootId]);
                })
                ->exists();
            if ($hasActiveChainBooking) {
                $validator->errors()->add('quotation_id', ui_phrase('Another active booking already exists for this quotation revision chain.'));
            }

            $normalizedStatus = Quotation::normalizeStatus((string) $quotation->status);
            $allowedStatuses = $linkedBookingId === $currentBookingId
                ? [Quotation::STATUS_APPROVED, Quotation::STATUS_CONVERTED_TO_BOOKING]
                : [Quotation::STATUS_APPROVED];
            if (! in_array($normalizedStatus, $allowedStatuses, true)) {
                $validator->errors()->add('quotation_id', ui_phrase('Booking can only use an approved quotation that is not linked to another booking.'));
            }

            $itemIds = collect($this->input('items', []))
                ->pluck('quotation_item_id')
                ->filter()
                ->map(fn ($id) => (int) $id)
                ->values();

            if ($itemIds->isNotEmpty()) {
                $validItemCount = $quotation->items()->whereIn('id', $itemIds->all())->count();
                if ($validItemCount !== $itemIds->count()) {
                    $validator->errors()->add('items', ui_phrase('Some booking items do not belong to selected quotation.'));
                }
            }
        });
    }
}
