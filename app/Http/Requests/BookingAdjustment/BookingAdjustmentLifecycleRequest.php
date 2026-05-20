<?php

namespace App\Http\Requests\BookingAdjustment;

use Illuminate\Foundation\Http\FormRequest;

class BookingAdjustmentLifecycleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'reason' => ['nullable', 'string', 'max:5000'],
            'rejection_reason' => ['nullable', 'string', 'max:5000'],
        ];
    }
}
