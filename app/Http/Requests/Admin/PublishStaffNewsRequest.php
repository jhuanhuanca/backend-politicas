<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class PublishStaffNewsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $debate = trim((string) $this->input('debate_id', ''));

        $this->merge([
            'title' => trim((string) $this->input('title', '')),
            'summary' => trim((string) $this->input('summary', '')),
            'source' => trim((string) $this->input('source', '')),
            'debate_id' => $debate === '' ? null : $debate,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'min:8', 'max:180'],
            'summary' => ['required', 'string', 'min:8', 'max:2000'],
            'source' => ['required', 'string', 'min:2', 'max:80'],
            'debate_id' => ['nullable', 'integer', 'exists:topics,id'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'title.required' => 'Escribe el título de la noticia.',
            'title.min' => 'El título necesita al menos 8 caracteres.',
            'summary.required' => 'Escribe el resumen de la noticia.',
            'summary.min' => 'El resumen necesita al menos 8 caracteres.',
            'source.required' => 'Escribe la fuente.',
            'source.min' => 'La fuente necesita al menos 2 caracteres.',
            'debate_id.exists' => 'Ese debate no existe.',
        ];
    }
}
