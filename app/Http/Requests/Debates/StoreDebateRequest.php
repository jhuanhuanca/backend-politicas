<?php

namespace App\Http\Requests\Debates;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\UploadedFile;
use Illuminate\Validation\Validator;

class StoreDebateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $image = trim((string) $this->input('image', ''));
        $kind = (string) $this->input('kind', 'image');

        $this->merge([
            'body' => trim((string) $this->input('body', '')),
            'image' => $image === '' ? null : $image,
            'kind' => $this->hasFile('media') || $image !== '' ? ($kind === '' ? 'image' : $kind) : 'image',
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'body' => ['required', 'string', 'min:8', 'max:5000'],
            'image' => ['nullable', 'string', 'max:255', 'url', 'starts_with:https://'],
            'kind' => ['required', 'in:image,video'],
            'media' => ['nullable', 'file'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'body.min' => 'El argumento necesita al menos 8 caracteres.',
            'image.url' => 'El enlace tiene que ser una URL https.',
            'image.starts_with' => 'El enlace tiene que empezar por https://.',
            'kind.in' => 'Elige foto o video.',
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $file = $this->file('media');
            $hasLink = $this->filled('image');

            if ($file instanceof UploadedFile && $hasLink) {
                $validator->errors()->add('media', 'Sube un archivo o pega un enlace, no los dos.');

                return;
            }

            if (! $file instanceof UploadedFile) {
                return;
            }

            $kind = (string) $this->input('kind');
            $allowed = $kind === 'video'
                ? ['video/mp4' => 'mp4', 'video/webm' => 'webm']
                : ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp', 'image/gif' => 'gif'];
            $max = $kind === 'video' ? 50 * 1024 * 1024 : 8 * 1024 * 1024;

            if (! array_key_exists((string) $file->getMimeType(), $allowed)) {
                $validator->errors()->add('media', $kind === 'video'
                    ? 'El video tiene que ser MP4 o WebM.'
                    : 'La imagen tiene que ser JPG, PNG, WEBP o GIF.');
            }

            if ($file->getSize() > $max) {
                $validator->errors()->add('media', $kind === 'video'
                    ? 'El video no puede pasar de 50 MB.'
                    : 'La imagen no puede pasar de 8 MB.');
            }
        });
    }
}
