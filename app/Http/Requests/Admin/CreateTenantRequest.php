<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

final class CreateTenantRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // Only system administrators can create tenants
        $user = $this->user();

        return $user !== null && $user->hasRole('system-administrator');
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => [
                'required',
                'string',
                'max:255',
                'unique:tenants,name',
                'regex:/^[A-Z0-9_]+$/', // Uppercase alphanumeric with underscores
            ],
            'display_name' => [
                'required',
                'string',
                'max:255',
            ],
            'description' => [
                'nullable',
                'string',
                'max:1000',
            ],
            'active' => [
                'boolean',
            ],
            'settings' => [
                'nullable',
                'array',
            ],
            'settings.branding' => [
                'nullable',
                'array',
            ],
            'settings.branding.primary_color' => [
                'nullable',
                'string',
                'regex:/^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$/',
            ],
            'settings.branding.secondary_color' => [
                'nullable',
                'string',
                'regex:/^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$/',
            ],
            'settings.branding.logo_url' => [
                'nullable',
                'string',
                'url',
                'max:2048',
            ],
            'settings.features' => [
                'nullable',
                'array',
            ],
            'settings.features.hire_purchase_enabled' => [
                'boolean',
            ],
            'settings.features.cross_association_sync_enabled' => [
                'boolean',
            ],
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Association name is required',
            'name.unique' => 'An association with this name already exists',
            'name.regex' => 'Association name must be uppercase alphanumeric with underscores (e.g., GRNMA, GMA)',
            'display_name.required' => 'Display name is required',
            'settings.branding.primary_color.regex' => 'Primary color must be a valid hex color code',
            'settings.branding.secondary_color.regex' => 'Secondary color must be a valid hex color code',
            'settings.branding.logo_url.url' => 'Logo URL must be a valid URL',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'name' => 'association name',
            'display_name' => 'display name',
            'settings.branding.primary_color' => 'primary color',
            'settings.branding.secondary_color' => 'secondary color',
            'settings.branding.logo_url' => 'logo URL',
        ];
    }
}
