<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class ReorderFieldSelectionItemsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'ordered_item_ids' => ['required', 'array', 'max:150'],
            'ordered_item_ids.*' => ['required', 'integer', 'distinct'],
        ];
    }
}
