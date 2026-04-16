<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SaveQuotationValidationItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'contract_rate' => ['nullable', 'numeric', 'min:0'],
            'markup_type' => ['nullable', Rule::in(['fixed', 'percent'])],
            'markup' => ['nullable', 'numeric', 'min:0'],
            'validation_notes' => ['nullable', 'string', 'max:2000'],
            'is_validated' => ['nullable', 'boolean'],
            'items' => ['nullable', 'array'],
            'items.*.contract_rate' => ['nullable', 'numeric', 'min:0'],
            'items.*.markup_type' => ['nullable', Rule::in(['fixed', 'percent'])],
            'items.*.markup' => ['nullable', 'numeric', 'min:0'],
            'items.*.validation_notes' => ['nullable', 'string', 'max:2000'],
            'items.*.is_validated' => ['nullable', 'boolean'],
        ];
    }
}
