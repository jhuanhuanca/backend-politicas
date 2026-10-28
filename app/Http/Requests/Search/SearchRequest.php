<?php

namespace App\Http\Requests\Search;

use Illuminate\Foundation\Http\FormRequest;

class SearchRequest extends FormRequest
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
            'q' => ['required', 'string', 'min:2', 'max:80'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'q.required' => 'Escribe al menos 2 caracteres.',
            'q.min' => 'Escribe al menos 2 caracteres.',
            'q.max' => 'La búsqueda admite hasta 80 caracteres.',
        ];
    }

    /**
     * @return array<int, callable>
     */
    public function after(): array
    {
        return [
            function ($validator): void {
                $literal = str_replace(['%', '_'], '', trim((string) $this->input('q', '')));

                if (strlen($literal) < 2 && ! $validator->errors()->has('q')) {
                    $validator->errors()->add('q', 'Escribe al menos 2 caracteres.');
                }
            },
        ];
    }
}
