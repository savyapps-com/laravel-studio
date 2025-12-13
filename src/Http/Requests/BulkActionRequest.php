<?php

namespace SavyApps\LaravelStudio\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class BulkActionRequest extends FormRequest
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
     */
    public function rules(): array
    {
        $maxIds = config('studio.bulk_operations.max_ids', 1000);

        return [
            'ids' => "required|array|min:1|max:{$maxIds}",
            'ids.*' => 'required|integer|min:1',
        ];
    }

    /**
     * Get custom error messages for validation rules.
     */
    public function messages(): array
    {
        $maxIds = config('studio.bulk_operations.max_ids', 1000);

        return [
            'ids.required' => 'At least one item must be selected.',
            'ids.min' => 'At least one item must be selected.',
            'ids.max' => "Cannot perform bulk action on more than {$maxIds} items at once.",
            'ids.*.required' => 'Invalid item ID provided.',
            'ids.*.integer' => 'Item IDs must be integers.',
            'ids.*.min' => 'Item IDs must be positive integers.',
        ];
    }
}
