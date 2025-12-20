<?php

namespace SavyApps\LaravelStudio\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use SavyApps\LaravelStudio\Models\EmailTemplate;
use SavyApps\LaravelStudio\Rules\ValidBladeTemplate;

class EmailTemplateRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->isUserAdmin();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $templateId = $this->route('template')?->id;

        // For store/upsert: check if template exists by key
        if (! $templateId && $this->isMethod('POST')) {
            $existingTemplate = EmailTemplate::query()
                ->where('key', $this->input('key'))
                ->first();
            $templateId = $existingTemplate?->id;
        }

        return [
            'key' => [
                'required',
                'string',
                'max:255',
                'unique:email_templates,key,'.$templateId,
            ],
            'name' => 'required|string|max:255',
            'subject_template' => ['required', 'string', new ValidBladeTemplate],
            'body_content' => ['required', 'string', new ValidBladeTemplate],
            'is_active' => 'sometimes|boolean',
        ];
    }

    /**
     * Check if user is admin
     */
    protected function isUserAdmin(): bool
    {
        $user = $this->user();

        if (! $user) {
            return false;
        }

        if (method_exists($user, 'isAdmin')) {
            return $user->isAdmin();
        }

        if (method_exists($user, 'hasRole')) {
            return $user->hasRole('admin') || $user->hasRole('super_admin');
        }

        return false;
    }
}
