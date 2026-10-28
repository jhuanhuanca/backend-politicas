<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class DecideLeaderRequest extends FormRequest
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
            'status' => ['required', 'in:accepted,set_aside,pending'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'status.in' => 'La decisión tiene que ser reconocer, apartar o volver a pendiente.',
            'status.required' => 'La decisión tiene que ser reconocer, apartar o volver a pendiente.',
        ];
    }
}
