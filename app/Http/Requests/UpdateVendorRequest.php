<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Form request for updating vendor profile.
 *
 * Validates partial vendor updates with optional fields.
 * All fields are nullable to support partial updates.
 */
final class UpdateVendorRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // Authorization handled by middleware/policies
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'business_name' => ['sometimes', 'string', 'max:255'],
            'contact_name' => ['sometimes', 'string', 'max:255'],
            'phone' => ['sometimes', 'string', 'max:20'],
            'address' => ['sometimes', 'string', 'max:500'],
            'city' => ['sometimes', 'string', 'max:100'],
            'state' => ['sometimes', 'string', 'max:100'],
            'postal_code' => ['sometimes', 'string', 'max:20'],
            'business_registration_number' => ['sometimes', 'nullable', 'string', 'max:100'],
            'tax_identification_number' => ['sometimes', 'nullable', 'string', 'max:100'],
            'description' => ['sometimes', 'nullable', 'string', 'max:2000'],
            'return_policy' => ['sometimes', 'nullable', 'string'],
            'shipping_policy' => ['sometimes', 'nullable', 'string'],
            'logo_url' => ['sometimes', 'nullable', 'url', 'max:500'],
            'banner_url' => ['sometimes', 'nullable', 'url', 'max:500'],
            'bank_details' => ['sometimes', 'nullable', 'array'],
            'bank_details.bank_name' => ['required_with:bank_details', 'string', 'max:255'],
            'bank_details.account_name' => ['required_with:bank_details', 'string', 'max:255'],
            'bank_details.account_number' => ['required_with:bank_details', 'string', 'max:50'],
            'bank_details.branch' => ['sometimes', 'nullable', 'string', 'max:255'],
            'metadata' => ['sometimes', 'nullable', 'array'],
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
            'business_name.string' => 'Business name must be a valid text string.',
            'business_name.max' => 'Business name cannot exceed 255 characters.',
            'phone.max' => 'Phone number cannot exceed 20 characters.',
            'logo_url.url' => 'Logo URL must be a valid URL.',
            'banner_url.url' => 'Banner URL must be a valid URL.',
            'bank_details.bank_name.required_with' => 'Bank name is required when providing bank details.',
            'bank_details.account_name.required_with' => 'Account name is required when providing bank details.',
            'bank_details.account_number.required_with' => 'Account number is required when providing bank details.',
        ];
    }
}
