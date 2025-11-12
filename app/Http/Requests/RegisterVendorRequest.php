<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Form Request for vendor registration validation.
 *
 * Handles server-side validation following Laravel conventions.
 * Validates all required fields, formats, and business rules.
 */
final class RegisterVendorRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // Authorization handled by middleware/controllers
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\Rule|array|string>
     */
    public function rules(): array
    {
        return [
            // tenant_id is resolved from current tenant via middleware
            'business_name' => ['required', 'string', 'max:255'],
            'contact_name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'string',
                'email',
                'max:255',
                Rule::unique('vendors', 'email'),
                // Also ensure no user account exists with the same email
                Rule::unique('users', 'email'),
            ],

            // Optional contact information
            'phone' => ['nullable', 'string', 'max:20'],
            'description' => ['nullable', 'string', 'max:1000'],
            'logo_url' => ['nullable', 'string', 'url', 'max:500'],

            // Optional address information
            'address_line_1' => ['nullable', 'string', 'max:255'],
            'address_line_2' => ['nullable', 'string', 'max:255'],
            'city' => ['nullable', 'string', 'max:100'],
            'state' => ['nullable', 'string', 'max:100'],
            'postal_code' => ['nullable', 'string', 'max:20'],
            'country' => ['nullable', 'string', 'max:100'],

            // Optional business details
            'business_registration_number' => ['nullable', 'string', 'max:100'],
            'tax_id' => ['nullable', 'string', 'max:100'],
            'bank_name' => ['nullable', 'string', 'max:255'],
            'bank_account_number' => ['nullable', 'string', 'max:50'],
            'bank_account_name' => ['nullable', 'string', 'max:255'],

            // Optional commission settings
            'commission_rate' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'commission_type' => ['nullable', 'string', Rule::in(['percentage', 'fixed'])],

            // Optional metadata
            'settings' => ['nullable', 'array'],
            'metadata' => ['nullable', 'array'],
        ];
    }

    /**
     * Get custom validation messages.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            // no user_id validation messages (public registration creates a user)
            'business_name.required' => 'Business name is required.',
            'contact_name.required' => 'Contact person name is required.',
            'email.required' => 'Email address is required.',
            'email.email' => 'Please provide a valid email address.',
            'email.unique' => 'This email is already registered as a vendor.',
            'commission_rate.numeric' => 'Commission rate must be a number.',
            'commission_rate.min' => 'Commission rate cannot be negative.',
            'commission_rate.max' => 'Commission rate cannot exceed 100%.',
            'commission_type.in' => 'Commission type must be either percentage or fixed.',
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
            'tenant_id' => 'association',
            'business_name' => 'business name',
            'contact_name' => 'contact person',
            'address_line_1' => 'address line 1',
            'address_line_2' => 'address line 2',
            'postal_code' => 'postal code',
            'business_registration_number' => 'business registration number',
            'tax_id' => 'tax identification number',
            'bank_name' => 'bank name',
            'bank_account_number' => 'bank account number',
            'bank_account_name' => 'bank account name',
            'commission_rate' => 'commission rate',
            'commission_type' => 'commission type',
        ];
    }
}
