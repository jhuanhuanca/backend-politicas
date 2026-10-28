<?php

namespace App\Http\Requests\Identity;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\UploadedFile;
use Illuminate\Validation\Validator;

class UpdateCoverRequest extends FormRequest
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
            'cover' => ['required', 'file'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'cover.required' => 'Elige una foto de portada.',
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $file = $this->file('cover');
            if (! $file instanceof UploadedFile) {
                return;
            }

            $allowed = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];
            if (! in_array($file->getMimeType(), $allowed, true)) {
                $validator->errors()->add('cover', 'La foto tiene que ser JPG, PNG, WEBP o GIF.');
            }

            if ($file->getSize() > 8 * 1024 * 1024) {
                $validator->errors()->add('cover', 'La foto no puede pasar de 8 MB.');
            }
        });
    }
}
