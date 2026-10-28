<?php

namespace App\Http\Requests\Debates;

use Illuminate\Foundation\Http\FormRequest;

class NewerCursorRequest extends FormRequest
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
            'after' => ['nullable', 'integer', 'min:0'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'after.integer' => 'El cursor tiene que ser un número.',
            'after.min' => 'El cursor tiene que ser un número.',
        ];
    }
}
