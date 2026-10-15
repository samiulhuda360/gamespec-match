<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AdvancedSearchRequest extends FormRequest
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
        return [
            'search' => ['nullable', 'string', 'max:255'],
            'genres' => ['nullable', 'array'],
            'genres.*' => ['string', 'max:100'],
            'categories' => ['nullable', 'array'],
            'categories.*' => ['string', 'max:255'],
            'developer' => ['nullable', 'string', 'max:255'],
            'publisher' => ['nullable', 'string', 'max:255'],
            'price_min' => ['nullable', 'numeric', 'min:0', 'max:999.99'],
            'price_max' => ['nullable', 'numeric', 'min:0', 'max:999.99'],
            'free_only' => ['nullable', 'boolean'],
            'min_year' => ['nullable', 'integer', 'min:1990', 'max:' . (date('Y') + 2)],
            'max_year' => ['nullable', 'integer', 'min:1990', 'max:' . (date('Y') + 2)],
            'min_review_score' => ['nullable', 'integer', 'min:0', 'max:100'],
            'min_recommendations' => ['nullable', 'integer', 'min:0'],
            'release_from' => ['nullable', 'date'],
            'release_to' => ['nullable', 'date'],
            'released_only' => ['nullable', 'boolean'],
            'platforms' => ['nullable', 'array'],
            'platforms.*' => ['string', 'in:pc,mac,linux'],
            'intensity_tier' => ['nullable', 'string', 'in:light,medium,high-end,ultra'],
            'min_requirement_score' => ['nullable', 'numeric', 'min:0'],
            'max_requirement_score' => ['nullable', 'numeric', 'min:0'],
            'sort' => ['nullable', 'string', 'in:recommendations_desc,popularity_desc,reviews_desc,rating_desc,date_desc,date_asc,price_asc,price_desc,low_end_first,high_end_first,name_asc,name_desc'],
            'per_page' => ['nullable', 'integer', 'min:12', 'max:100'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'search.max' => 'Search query must not exceed 255 characters.',
            'min_price.min' => 'Minimum price cannot be negative.',
            'max_price.min' => 'Maximum price cannot be negative.',
            'release_year.min' => 'Release year must be 1990 or later.',
            'release_year.max' => 'Invalid release year.',
            'platform.in' => 'Invalid platform selected.',
            'sort.in' => 'Invalid sort option selected.',
        ];
    }

    /**
     * Sanitize input data to prevent XSS and injection attacks.
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'search' => $this->sanitizeString($this->search),
            'genre' => $this->sanitizeString($this->genre),
            'category' => $this->sanitizeString($this->category),
            'developer' => $this->sanitizeString($this->developer),
            'publisher' => $this->sanitizeString($this->publisher),
        ]);
    }

    /**
     * Sanitize string input by removing dangerous characters.
     */
    private function sanitizeString(?string $input): ?string
    {
        if (empty($input)) {
            return null;
        }

        // Remove null bytes and control characters
        $input = str_replace("\0", '', $input);
        $input = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', '', $input);

        // Strip tags to prevent XSS
        $input = strip_tags($input);

        // Remove SQL wildcards from user input (% and _)
        $input = str_replace(['%', '_'], '', $input);

        return trim($input);
    }
}
