<?php

namespace App\Http\Requests;

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
            'status' => ['required', 'in:confirmed,completed,cancelled'],
            'notes' => ['nullable', 'string'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $status = $this->input('status');
            $travelDate = $this->input('travel_date');
            $notes = trim((string) $this->input('notes'));

            if ($status === 'completed' && $travelDate && now()->lt(\Carbon\Carbon::parse($travelDate))) {
                $validator->errors()->add('status', 'Completed status is only allowed when travel_date is in the past.');
            }

            if ($status === 'cancelled' && $notes === '') {
                $validator->errors()->add('notes', 'A reason is required when status is cancelled.');
            }
        });
    }
}
