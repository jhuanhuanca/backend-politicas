<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'nick' => fake()->unique()->bothify('user_????'),
            'device_fingerprint' => hash('sha256', Str::uuid()->toString()),
        ];
    }
}
