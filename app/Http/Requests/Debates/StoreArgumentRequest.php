<?php

namespace App\Http\Requests\Debates;

use Illuminate\Foundation\Http\FormRequest;

class StoreArgumentRequest extends FormRequest
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
            'body' => ['required', 'string', 'min:2', 'max:2000'],
            'parent_id' => ['nullable', 'integer'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'body.min' => 'Escribe un comentario antes de publicarlo.',
        ];
    }
}
