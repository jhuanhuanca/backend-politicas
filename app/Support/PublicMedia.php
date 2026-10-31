<?php

namespace App\Support;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class PublicMedia
{
    public function store(UploadedFile $file, string $folder): string
    {
        $extension = match ($file->getMimeType()) {
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/webp' => 'webp',
            'image/gif' => 'gif',
            'video/mp4' => 'mp4',
            'video/webm' => 'webm',
            default => null,
        };

        if ($extension === null || ! in_array($folder, ['debates', 'avatars', 'covers', 'banners'], true)) {
            throw ValidationException::withMessages([
                'media' => 'Ese archivo no se puede publicar.',
            ]);
        }

        if (in_array($folder, ['avatars', 'covers'], true) && ! in_array($extension, ['jpg', 'png', 'webp', 'gif'], true)) {
            throw ValidationException::withMessages([
                $folder === 'covers' ? 'cover' : 'avatar' => 'La foto tiene que ser JPG, PNG, WEBP o GIF.',
            ]);
        }

        $directory = public_path('media/'.$folder);
        if (! is_dir($directory) && ! mkdir($directory, 0755, true) && ! is_dir($directory)) {
            throw ValidationException::withMessages([
                'media' => 'No se pudo guardar el archivo.',
            ]);
        }

        $name = Str::uuid()->toString().'.'.$extension;
        $file->move($directory, $name);

        return '/media/'.$folder.'/'.$name;
    }

    public function delete(?string $publicPath): void
    {
        if (! is_string($publicPath) || ! preg_match('#^/media/(debates|avatars|covers|banners)/[0-9a-f-]{36}\.(jpg|png|webp|gif|mp4|webm)$#', $publicPath)) {
            return;
        }

        $file = public_path(ltrim($publicPath, '/'));
        if (is_file($file)) {
            unlink($file);
        }
    }
}
