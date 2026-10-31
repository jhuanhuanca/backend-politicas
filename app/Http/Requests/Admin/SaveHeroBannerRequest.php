<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\UploadedFile;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class SaveHeroBannerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $link = trim((string) $this->input('link', ''));
        $announcement = trim((string) $this->input('announcement', ''));

        $this->merge([
            'announcement' => $announcement === '' ? null : $announcement,
            'link' => $link === '' ? null : $link,
            'clear' => $this->boolean('clear'),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'slot' => ['required', 'string', Rule::in(['home', 'polls'])],
            'announcement' => ['nullable', 'string', 'max:180'],
            'link' => ['nullable', 'string', 'max:2000', 'url', 'starts_with:https://'],
            'media' => ['nullable', 'file'],
            'clear' => ['boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'slot.in' => 'Elige el inicio o las votaciones.',
            'announcement.max' => 'El anuncio no puede pasar de 180 caracteres.',
            'link.url' => 'El enlace tiene que ser una dirección https.',
            'link.starts_with' => 'El enlace tiene que empezar por https://.',
            'link.max' => 'El enlace es demasiado largo.',
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $file = $this->file('media');
            $link = $this->input('link');
            $hasFile = $file instanceof UploadedFile;
            $hasLink = is_string($link) && $link !== '';

            if ($hasFile && $hasLink) {
                $validator->errors()->add('media', 'Elige un archivo o un enlace, no los dos.');

                return;
            }

            if (! $hasFile) {
                return;
            }

            $allowed = ['image/jpeg', 'image/png', 'image/webp', 'image/gif', 'video/mp4', 'video/webm'];
            if (! in_array($file->getMimeType(), $allowed, true)) {
                $validator->errors()->add('media', 'El archivo tiene que ser JPG, PNG, WEBP, GIF, MP4 o WebM.');

                return;
            }

            $video = in_array($file->getMimeType(), ['video/mp4', 'video/webm'], true);
            $max = $video ? 50 * 1024 * 1024 : 8 * 1024 * 1024;
            if ($file->getSize() > $max) {
                $validator->errors()->add('media', $video
                    ? 'El video no puede pasar de 50 MB.'
                    : 'La imagen no puede pasar de 8 MB.');
            }
        });
    }
}
