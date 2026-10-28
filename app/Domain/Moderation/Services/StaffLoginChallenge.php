<?php

namespace App\Domain\Moderation\Services;

use App\Models\Staff;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class StaffLoginChallenge
{
    /**
     * @return array{staff: Staff, purpose: string}|null
     */
    public function find(string $challenge): ?array
    {
        $payload = Cache::get($this->key($challenge));

        if (! is_array($payload)) {
            return null;
        }

        $staff = Staff::query()->find($payload['staff_id'] ?? null);

        if (! $staff instanceof Staff || ! in_array($payload['purpose'] ?? '', ['setup', 'code'], true)) {
            return null;
        }

        return [
            'staff' => $staff,
            'purpose' => $payload['purpose'],
        ];
    }

    public function issue(Staff $staff, string $purpose): string
    {
        $challenge = Str::random(48);
        Cache::put($this->key($challenge), [
            'staff_id' => $staff->id,
            'purpose' => $purpose,
            'attempts' => 0,
        ], now()->addMinutes(5));

        return $challenge;
    }

    public function recordFailure(string $challenge): int
    {
        $key = $this->key($challenge);
        $payload = Cache::get($key);

        if (! is_array($payload)) {
            return 5;
        }

        $payload['attempts'] = (int) ($payload['attempts'] ?? 0) + 1;
        Cache::put($key, $payload, now()->addMinutes(5));

        if ($payload['attempts'] >= 5) {
            Cache::forget($key);
        }

        return $payload['attempts'];
    }

    public function forget(string $challenge): void
    {
        Cache::forget($this->key($challenge));
    }

    private function key(string $challenge): string
    {
        return 'staff-login:'.hash('sha256', $challenge);
    }
}
