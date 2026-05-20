<?php

namespace App\Http\Requests\BookingAdjustment;

use App\Models\BookingAdjustment;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateBookingAdjustmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('booking_adjustments.update') ?? false;
    }

    public function rules(): array
    {
        return [
            'booking_item_id' => ['nullable', 'integer', 'exists:booking_items,id'],
            'invoice_id' => ['nullable', 'integer', 'exists:invoices,id'],
            'payment_id' => ['nullable', 'integer', 'exists:payments,id'],
            'adjustment_type' => ['required', Rule::in(BookingAdjustment::TYPE_OPTIONS)],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'reason' => ['nullable', 'string'],
            'amount' => ['required', 'numeric', 'min:0'],
            'currency_code' => ['nullable', 'string', 'max:8'],
            'impact_type' => ['required', Rule::in(BookingAdjustment::IMPACT_OPTIONS)],
        ];
    }
}
