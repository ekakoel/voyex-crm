<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SaveQuotationValidationProgressRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'items' => ['nullable', 'array'],
            'items.*.contract_rate' => ['nullable', 'numeric', 'min:0'],
            'items.*.markup_type' => ['nullable', Rule::in(['fixed', 'percent'])],
            'items.*.markup' => ['nullable', 'numeric', 'min:0'],
            'items.*.validation_notes' => ['nullable', 'string', 'max:2000'],
            'items.*.is_validated' => ['nullable', 'boolean'],
        ];
    }
}