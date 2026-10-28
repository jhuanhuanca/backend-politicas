<?php

namespace App\Http\Resources;

use App\Models\Sanction;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Sanction
 */
class AdminSanctionResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'nick' => $this->user?->nick,
            'reason' => $this->reason,
            'ends_at' => $this->ends_at?->toIso8601String(),
            'lifted' => $this->lifted_at !== null,
        ];
    }
}
