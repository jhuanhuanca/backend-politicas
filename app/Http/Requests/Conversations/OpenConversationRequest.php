<?php

namespace App\Http\Requests\Conversations;

use Illuminate\Foundation\Http\FormRequest;

class OpenConversationRequest extends FormRequest
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
            'nick' => ['required', 'string', 'min:3', 'max:20', 'regex:/^[a-zA-Z0-9_]+$/'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'nick.required' => 'Escribe el nick de la otra persona.',
            'nick.regex' => 'El nick solo puede tener letras, números y guion bajo.',
            'nick.min' => 'El nick solo puede tener letras, números y guion bajo.',
        ];
    }
}
