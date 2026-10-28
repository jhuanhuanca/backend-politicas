<?php

namespace App\Http\Requests\Debates;

use Illuminate\Foundation\Http\FormRequest;

class StoreArgumentVoteRequest extends FormRequest
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
            'stance' => ['required', 'in:favor,contra'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'stance.in' => 'El voto tiene que ser a favor o en contra.',
            'stance.required' => 'El voto tiene que ser a favor o en contra.',
        ];
    }
}
