<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ValidateSelectedQuotationItemsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'selected_item_ids' => ['required', 'array', 'min:1'],
            'selected_item_ids.*' => ['required', 'integer', 'min:1'],
        ];
    }
}