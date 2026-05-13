<?php

namespace App\Http\Requests;

use App\Models\Quotation;
use Illuminate\Foundation\Http\FormRequest;

class UpdateBookingRequest extends FormRequest
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
            'quotation_id' => [
                'required',
                'exists:quotations,id',
                \Illuminate\Validation\Rule::unique('bookings', 'quotation_id')->ignore($this->route('booking')?->id),
            ],
            'travel_date' => ['required', 'date'],
            'notes' => ['nullable', 'string'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.quotation_item_id' => ['nullable', 'integer', 'exists:quotation_items,id'],
            'items.*.description' => ['required', 'string', 'max:255'],
            'items.*.qty' => ['required', 'integer', 'min:1'],
            'items.*.unit_price' => ['required', 'numeric', 'min:0'],
            'items.*.serviceable_type' => ['nullable', 'string', 'max:255'],
            'items.*.serviceable_id' => ['nullable', 'integer', 'min:1'],
            'items.*.day_number' => ['nullable', 'integer', 'min:1'],
            'items.*.serviceable_meta' => ['nullable'],
            'items.*.notes' => ['nullable', 'string'],
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
                ->with('booking:id,quotation_id')
                ->find($quotationId);

            if (! $quotation) {
                return;
            }

            if (! in_array((string) $quotation->status, ['approved', Quotation::FINAL_STATUS], true)) {
                $validator->errors()->add('quotation_id', ui_phrase('Only approved/final quotation can be converted to booking.'));
            }

            if ((int) ($quotation->items_count ?? 0) <= 0) {
                $validator->errors()->add('quotation_id', ui_phrase('Selected quotation has no items to be booked.'));
            }

            $notValidatedItemsCount = $quotation->items()
                ->where(function ($q) {
                    $q->whereNull('is_validated')
                        ->orWhere('is_validated', false);
                })
                ->count();
            if ($notValidatedItemsCount > 0) {
                $validator->errors()->add('quotation_id', ui_phrase('Selected quotation still has unvalidated items.'));
            }

            $linkedBookingId = (int) ($quotation->booking?->id ?? 0);
            if ($linkedBookingId > 0 && $linkedBookingId !== $currentBookingId) {
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
