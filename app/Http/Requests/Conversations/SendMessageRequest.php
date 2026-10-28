<?php

namespace App\Http\Requests\Conversations;

use Illuminate\Foundation\Http\FormRequest;

class SendMessageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'body' => trim(strip_tags((string) $this->input('body', ''))),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'body' => ['required', 'string', 'min:1', 'max:1000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'body.required' => 'Escribe un mensaje antes de enviarlo.',
            'body.min' => 'Escribe un mensaje antes de enviarlo.',
            'body.max' => 'El mensaje puede tener hasta 1000 caracteres.',
        ];
    }
}
