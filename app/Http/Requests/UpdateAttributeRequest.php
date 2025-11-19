<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Form request for updating an attribute
 */
class UpdateAttributeRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // Authorization handled by middleware
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        $attributeId = $this->route('attribute')?->id;

        return [
            'name' => ['required', 'string', 'max:255'],
            'type' => ['required', 'string', Rule::in(['text', 'number', 'boolean', 'select', 'multiselect', 'richtext', 'file', 'image', 'datetime', 'date', 'color'])],
            'handle' => ['nullable', 'string', 'max:255', 'regex:/^[a-z_]+$/'],
            'section' => ['nullable', 'string', 'max:255'],
            'position' => ['nullable', 'integer', 'min:0'],
            'required' => ['boolean'],
            'system' => ['boolean'],
            'attribute_group_id' => ['nullable', 'uuid', 'exists:attribute_groups,id'],
            'configuration' => ['nullable', 'array'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Attribute name is required.',
            'name.max' => 'Attribute name cannot exceed 255 characters.',
            'type.required' => 'Attribute type is required.',
            'type.in' => 'Invalid attribute type selected.',
            'handle.regex' => 'Handle must contain only lowercase letters and underscores.',
            'attribute_group_id.exists' => 'Selected attribute group does not exist.',
        ];
    }
}
