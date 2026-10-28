<?php

namespace App\Http\Requests\Debates;

use Illuminate\Foundation\Http\FormRequest;

class StoreDebateRequest extends FormRequest
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
            'body' => ['required', 'string', 'min:8', 'max:5000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'body.min' => 'El argumento necesita al menos 8 caracteres.',
        ];
    }
}
