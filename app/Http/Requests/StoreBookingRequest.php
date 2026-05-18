<?php

namespace App\Http\Requests;

use App\Models\Quotation;
use Illuminate\Foundation\Http\FormRequest;

class StoreBookingRequest extends FormRequest
{
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
            'quotation_id' => ['required', 'exists:quotations,id', 'unique:bookings,quotation_id'],
            'travel_date' => ['required', 'date'],
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

            $quotation = Quotation::query()
                ->withCount('items')
                ->withCount('booking')
                ->find($quotationId);

            if (! $quotation) {
                return;
            }

            if (! in_array((string) $quotation->status, ['approved', Quotation::FINAL_STATUS], true)) {
                $validator->errors()->add('quotation_id', ui_phrase('Only approved quotation can be converted to booking.'));
            }

            if ((string) ($quotation->validation_status ?? 'pending') !== 'valid') {
                $validator->errors()->add('quotation_id', ui_phrase('Selected quotation validation must be 100% before booking.'));
            }

            if ((int) ($quotation->items_count ?? 0) <= 0) {
                $validator->errors()->add('quotation_id', ui_phrase('Selected quotation has no items to be booked.'));
            }

            if ((int) ($quotation->booking_count ?? 0) > 0) {
                $validator->errors()->add('quotation_id', ui_phrase('Selected quotation is already linked to another booking.'));
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
