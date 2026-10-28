<?php

namespace App\Http\Requests\Polls;

use Illuminate\Foundation\Http\FormRequest;

class StorePollVoteRequest extends FormRequest
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
            'option_id' => ['required', 'integer'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'option_id.required' => 'Elige una opción.',
            'option_id.integer' => 'Elige una opción.',
        ];
    }
}
