<?php

namespace App\Domain\Moderation\Actions;

use App\Models\AdminAuditLog;
use App\Models\HeroBanner;
use App\Models\Staff;
use App\Support\PublicMedia;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;

class SaveHeroBannerAction
{
    public function __construct(private readonly PublicMedia $media) {}

    public function execute(
        Staff $staff,
        string $slot,
        ?string $announcement,
        ?UploadedFile $file,
        ?string $link,
        bool $clear,
    ): HeroBanner {
        return DB::transaction(function () use ($staff, $slot, $announcement, $file, $link, $clear): HeroBanner {
            $banner = HeroBanner::query()->firstOrNew(['slot' => $slot]);
            $previous = $banner->media;
            $banner->announcement = $announcement;

            if ($clear) {
                $banner->kind = null;
                $banner->media = null;
            } elseif ($file instanceof UploadedFile) {
                $mime = (string) $file->getMimeType();
                $banner->media = $this->media->store($file, 'banners');
                $banner->kind = str_starts_with($mime, 'video/') ? 'video' : 'image';
            } elseif (is_string($link) && $link !== '') {
                $banner->media = $link;
                $banner->kind = $this->kindForLink($link);
            }

            $banner->save();

            if ($banner->media !== $previous) {
                $this->media->delete($previous);
            }

            AdminAuditLog::query()->create([
                'staff_id' => $staff->id,
                'action' => 'hero.updated',
                'subject_type' => 'hero',
                'subject_id' => $banner->id,
                'created_at' => now(),
            ]);

            return $banner;
        });
    }

    private function kindForLink(string $link): string
    {
        $path = strtolower((string) parse_url($link, PHP_URL_PATH));
        if (preg_match('#\.(mp4|webm|ogg|mov)$#', $path)) {
            return 'video';
        }

        $host = strtolower((string) parse_url($link, PHP_URL_HOST));
        $host = (string) preg_replace('/^www\./', '', $host);
        $videoHosts = [
            'youtube.com',
            'm.youtube.com',
            'music.youtube.com',
            'youtu.be',
            'youtube-nocookie.com',
            'vimeo.com',
            'player.vimeo.com',
        ];

        return in_array($host, $videoHosts, true) ? 'video' : 'image';
    }
}
