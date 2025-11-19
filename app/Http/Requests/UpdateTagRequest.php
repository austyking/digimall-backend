<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Form request for updating a tag
 */
class UpdateTagRequest extends FormRequest
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
        $tagId = $this->route('tag')?->id;

        return [
            'value' => ['required', 'string', 'max:255', 'unique:tags,value,'.$tagId],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'value.required' => 'Tag value is required.',
            'value.max' => 'Tag value cannot exceed 255 characters.',
            'value.unique' => 'A tag with this value already exists.',
        ];
    }
}
