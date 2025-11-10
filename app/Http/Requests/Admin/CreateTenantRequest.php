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
            'domain' => [
                'nullable',
                'string',
                'max:255',
                'unique:domains,domain',
            ],
            'active' => [
                'boolean',
            ],
            // Accept flat format from frontend
            'theme_primary_color' => [
                'nullable',
                'string',
                'regex:/^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$/',
            ],
            'theme_secondary_color' => [
                'nullable',
                'string',
                'regex:/^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$/',
            ],
            'logo' => [
                'nullable',
                'file',
                'image',
                'max:5120', // 5MB in kilobytes
                'mimes:jpeg,jpg,png,gif,webp',
            ],
            'hire_purchase_enabled' => [
                'boolean',
            ],
            'vendor_registration_enabled' => [
                'boolean',
            ],
            'multi_currency_enabled' => [
                'boolean',
            ],
            'contact_email' => [
                'nullable',
                'email',
                'max:255',
            ],
            'contact_phone' => [
                'nullable',
                'string',
                'max:50',
            ],
            'contact_address' => [
                'nullable',
                'string',
                'max:500',
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
            'settings.theme.primary_color.regex' => 'Primary color must be a valid hex color code',
            'settings.theme.secondary_color.regex' => 'Secondary color must be a valid hex color code',
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
            'settings.theme.primary_color' => 'primary color',
            'settings.theme.secondary_color' => 'secondary color',
        ];
    }
}
