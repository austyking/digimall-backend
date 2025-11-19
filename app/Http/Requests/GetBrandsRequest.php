<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Form request for getting/paginating brands
 */
class GetBrandsRequest extends FormRequest
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
        return [
            'page' => ['integer', 'min:1'],
            'per_page' => ['integer', 'min:1', 'max:100'],
            'search' => ['string', 'nullable'],
            'sort_by' => ['nullable', Rule::in(['created_at', 'updated_at'])],
            'sort_direction' => ['nullable', Rule::in(['asc', 'desc'])],
        ];
    }

    /**
     * Get the validated data from the request.
     */
    public function validated($key = null, $default = null)
    {
        $validated = parent::validated();

        // Set defaults
        return array_merge([
            'page' => 1,
            'per_page' => 15,
            'search' => null,
            'sort_by' => 'created_at',
            'sort_direction' => 'desc',
        ], $validated);
    }
}
