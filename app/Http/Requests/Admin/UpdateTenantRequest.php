<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

final class UpdateTenantRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->hasRole('system-administrator');
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'display_name' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
            'logo' => [
                'nullable',
                'file',
                'image',
                'max:5120', // 5MB in kilobytes
                'mimes:jpeg,jpg,png,gif,webp',
            ],
            'logo_url' => ['nullable', 'url', 'max:500'],
            'settings' => ['nullable', 'array'],
            'settings.theme' => ['nullable', 'array'],
            'settings.theme.primary_color' => ['nullable', 'string', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'settings.theme.secondary_color' => ['nullable', 'string', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'settings.features' => ['nullable', 'array'],
            'settings.features.hire_purchase_enabled' => ['nullable', 'boolean'],
            'settings.features.vendor_registration_enabled' => ['nullable', 'boolean'],
            'settings.features.multi_currency_enabled' => ['nullable', 'boolean'],
            'settings.contact' => ['nullable', 'array'],
            'settings.contact.email' => ['nullable', 'email', 'max:255'],
            'settings.contact.phone' => ['nullable', 'string', 'max:50'],
            'settings.contact.address' => ['nullable', 'string', 'max:500'],
            'settings.payment_gateways' => ['nullable', 'array'],
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
            'settings.theme.primary_color.regex' => 'The primary color must be a valid hex color code (e.g., #1976d2).',
            'settings.theme.secondary_color.regex' => 'The secondary color must be a valid hex color code (e.g., #dc004e).',
        ];
    }
}
