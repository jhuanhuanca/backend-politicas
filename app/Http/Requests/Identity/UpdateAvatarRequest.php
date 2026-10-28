<?php

namespace App\Http\Requests\Identity;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\UploadedFile;
use Illuminate\Validation\Validator;

class UpdateAvatarRequest extends FormRequest
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
            'avatar' => ['required', 'file'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'avatar.required' => 'Elige una foto.',
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $file = $this->file('avatar');
            if (! $file instanceof UploadedFile) {
                return;
            }

            $allowed = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];
            if (! in_array($file->getMimeType(), $allowed, true)) {
                $validator->errors()->add('avatar', 'La foto tiene que ser JPG, PNG, WEBP o GIF.');
            }

            if ($file->getSize() > 8 * 1024 * 1024) {
                $validator->errors()->add('avatar', 'La foto no puede pasar de 8 MB.');
            }
        });
    }
}
