<?php

namespace App\Http\Requests\Admin;

use App\Domain\Debates\DebateCategories;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PublishStaffDebateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $image = trim((string) $this->input('image', ''));

        $this->merge([
            'body' => trim((string) $this->input('body', '')),
            'context' => trim((string) $this->input('context', '')),
            'category' => trim((string) $this->input('category', '')),
            'image' => $image === '' ? null : $image,
            'kind' => $image === '' ? 'text' : (string) $this->input('kind', 'image'),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'body' => ['required', 'string', 'min:8', 'max:5000'],
            'context' => ['required', 'string', 'min:2', 'max:80'],
            'category' => ['required', 'string', Rule::in(DebateCategories::all())],
            'image' => ['nullable', 'string', 'max:255', 'url', 'starts_with:https://'],
            'kind' => ['required', 'in:text,image,video'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'body.required' => 'Escribe el debate.',
            'body.min' => 'El debate necesita al menos 8 caracteres.',
            'context.required' => 'Escribe la mesa o el contexto.',
            'context.min' => 'La mesa necesita al menos 2 caracteres.',
            'category.required' => 'Elige la categoría del debate.',
            'category.in' => 'La categoría tiene que ser Política, Territorio o Economía.',
            'image.url' => 'El enlace de la imagen tiene que ser una URL https.',
            'image.starts_with' => 'El enlace de la imagen tiene que empezar por https://.',
            'kind.in' => 'El tipo de debate no es válido.',
        ];
    }
}
