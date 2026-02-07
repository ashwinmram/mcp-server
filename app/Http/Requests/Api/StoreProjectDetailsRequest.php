<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class StoreProjectDetailsRequest extends FormRequest
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
            'source_project' => ['required', 'string', 'max:255'],
            'lessons' => ['required', 'array', 'min:1'],
            'lessons.*.type' => ['required', 'string', 'in:cursor,ai_output,manual,markdown,project_detail'],
            'lessons.*.content' => ['required', 'string'],
            'lessons.*.category' => ['nullable', 'string', 'max:255'],
            'lessons.*.subcategory' => ['nullable', 'string', 'max:255'],
            'lessons.*.title' => ['nullable', 'string', 'max:255'],
            'lessons.*.summary' => ['nullable', 'string'],
            'lessons.*.tags' => ['nullable', 'array'],
            'lessons.*.tags.*' => ['string', 'max:255'],
            'lessons.*.metadata' => ['nullable', 'array'],
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
            'source_project.required' => 'The source project field is required.',
            'lessons.required' => 'At least one lesson is required.',
            'lessons.*.type.in' => 'Lesson type must be one of: cursor, ai_output, manual, markdown, project_detail.',
        ];
    }
}
