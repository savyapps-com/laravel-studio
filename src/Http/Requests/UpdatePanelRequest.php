<?php

namespace SavyApps\LaravelStudio\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use SavyApps\LaravelStudio\Models\Panel;

class UpdatePanelRequest extends FormRequest
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
        $panel = Panel::findByKey($this->route('key'));
        $panelId = $panel?->id;

        return [
            'key' => 'sometimes|string|max:255|alpha_dash|unique:panels,key,' . $panelId,
            'label' => 'sometimes|string|max:255',
            'path' => ['sometimes', 'string', 'max:255', 'regex:/^\/[a-z0-9\-\/]*$/i'],
            'icon' => 'nullable|string|max:255',
            'role' => 'nullable|string|max:255',
            'roles' => 'nullable|array',
            'roles.*' => 'string|max:255',
            'middleware' => 'nullable|array',
            'middleware.*' => 'string|max:255',
            'resources' => 'nullable|array',
            'resources.*' => 'string|max:255',
            'features' => 'nullable|array',
            'features.*' => 'string|max:255',
            'menu' => 'nullable|array',
            'settings' => 'nullable|array',
            'is_active' => 'nullable|boolean',
            'is_default' => 'nullable|boolean',
            'priority' => 'nullable|integer|min:0',
        ];
    }

    /**
     * Get custom error messages for validation rules.
     */
    public function messages(): array
    {
        return [
            'key.unique' => 'A panel with this key already exists.',
            'key.alpha_dash' => 'The panel key may only contain letters, numbers, dashes, and underscores.',
            'path.regex' => 'The panel path must start with / and contain only letters, numbers, hyphens, and forward slashes.',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Ensure path starts with /
        if ($this->has('path') && !str_starts_with($this->path, '/')) {
            $this->merge(['path' => '/' . $this->path]);
        }
    }
}
