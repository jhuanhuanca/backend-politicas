<?php

namespace App\Http\Resources;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin User
 */
class AdminUserResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $consent = $this->consents->first();

        return [
            'nick' => $this->nick,
            'name' => $this->name,
            'department' => $this->department,
            'municipality' => $this->municipality,
            'created_at' => $this->created_at?->toIso8601String(),
            'metrics_consent' => $consent?->granted_at !== null,
            'device_registered' => $this->device_fingerprint !== null,
            'last_seen_at' => $this->devices->first()?->last_seen_at?->toIso8601String(),
        ];
    }
}
