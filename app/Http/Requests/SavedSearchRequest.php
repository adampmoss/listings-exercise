<?php

namespace App\Http\Requests;

use App\Enums\PropertyType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;
use Illuminate\Validation\Validator;

class SavedSearchRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'max_price' => ['nullable', 'integer', 'min:1'],
            'min_bedrooms' => ['nullable', 'integer', 'min:0', 'max:20'],
            'property_type' => ['nullable', new Enum(PropertyType::class)],
            'region' => ['nullable', 'string', 'max:100'],
        ];
    }

    /**
     * A saved search with no criteria would match everything, which is not
     * useful and would generate noise. Require at least one.
     *
     * @param  Validator  $validator
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $criteria = ['max_price', 'min_bedrooms', 'property_type', 'region'];

            if (! collect($criteria)->contains(fn (string $field) => $this->filled($field))) {
                $validator->errors()->add('criteria', 'At least one search criterion is required.');
            }
        });
    }
}
