<?php

namespace App\Http\Resources;

use App\Models\HeroBanner;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin HeroBanner
 */
class HeroBannerResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'slot' => $this->slot,
            'announcement' => $this->announcement,
            'kind' => $this->kind,
            'media' => $this->media,
        ];
    }
}
