<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SchoolClassRequest extends FormRequest
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
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'grade_name' => ['required', 'string', 'max:255'],
            'grade_level' => ['required', 'integer', 'min:1', 'max:12'],
            'country_id' => ['required', 'exists:countries,id'],
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
            'grade_name' => __('grade name'),
            'grade_level' => __('grade level'),
            'country_id' => __('country'),
        ];
    }

    /**
     * Get the error messages for the defined validation rules.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'grade_name.required' => __('The grade name is required.'),
            'grade_name.max' => __('The grade name cannot exceed :max characters.'),
            'grade_level.required' => __('The grade level is required.'),
            'grade_level.integer' => __('The grade level must be a number.'),
            'grade_level.min' => __('The grade level must be at least :min.'),
            'grade_level.max' => __('The grade level cannot exceed :max.'),
            'country_id.required' => __('The country is required.'),
            'country_id.exists' => __('The selected country is invalid.'),
        ];
    }
}
