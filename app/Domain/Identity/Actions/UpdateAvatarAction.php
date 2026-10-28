<?php

namespace App\Domain\Identity\Actions;

use App\Models\User;
use App\Support\PublicMedia;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;

class UpdateAvatarAction
{
    public function __construct(private readonly PublicMedia $media) {}

    public function execute(User $user, UploadedFile $file): User
    {
        return DB::transaction(function () use ($user, $file): User {
            $user = User::query()->whereKey($user->id)->lockForUpdate()->firstOrFail();
            $previous = $user->avatar;
            $path = $this->media->store($file, 'avatars');
            $user->forceFill(['avatar' => $path])->save();
            $this->media->delete(is_string($previous) ? $previous : null);

            return $user;
        });
    }
}
