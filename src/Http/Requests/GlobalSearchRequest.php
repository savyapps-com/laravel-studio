<?php

namespace SavyApps\LaravelStudio\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class GlobalSearchRequest extends FormRequest
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
        $minChars = config('studio.global_search.min_characters', 2);

        return [
            'q' => "required|string|min:{$minChars}|max:255",
            'panel' => 'nullable|string|max:100',
            'resources' => 'nullable|array|max:50',
            'resources.*' => 'string|max:100',
        ];
    }

    /**
     * Get custom error messages for validation rules.
     */
    public function messages(): array
    {
        $minChars = config('studio.global_search.min_characters', 2);

        return [
            'q.required' => 'A search query is required.',
            'q.min' => "Search query must be at least {$minChars} characters.",
            'q.max' => 'Search query cannot exceed 255 characters.',
            'panel.max' => 'Panel name cannot exceed 100 characters.',
            'resources.max' => 'Cannot search more than 50 resources at once.',
        ];
    }
}
