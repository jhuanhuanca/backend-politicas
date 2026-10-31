<?php

namespace App\Http\Resources;

use App\Models\LeaderCandidate;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin LeaderCandidate
 */
class AdminLeaderResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'nick' => $this->user?->nick,
            'name' => $this->user?->name,
            'department' => $this->user?->department,
            'municipality' => $this->user?->municipality,
            'participation' => $this->participation,
            'respect' => $this->respect,
            'status' => $this->status,
            'calculated_at' => $this->calculated_at?->toIso8601String(),
        ];
    }
}
