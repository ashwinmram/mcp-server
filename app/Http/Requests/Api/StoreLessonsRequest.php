<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class StoreLessonsRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // Authorization handled by Sanctum middleware
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
            'lessons.*.type' => ['required', 'string', 'in:cursor,ai_output,manual,markdown'],
            'lessons.*.content' => ['required', 'string'],
            'lessons.*.category' => ['nullable', 'string', 'max:255'],
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
            'lessons.array' => 'Lessons must be an array.',
            'lessons.min' => 'At least one lesson must be provided.',
            'lessons.*.type.required' => 'Each lesson must have a type.',
            'lessons.*.type.in' => 'Lesson type must be one of: cursor, ai_output, manual, markdown.',
            'lessons.*.content.required' => 'Each lesson must have content.',
        ];
    }
}
