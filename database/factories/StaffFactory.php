<?php

namespace Database\Factories;

use App\Models\Staff;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Staff>
 */
class StaffFactory extends Factory
{
    protected $model = Staff::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => 'Mesa de moderación',
            'email' => fake()->unique()->safeEmail(),
            'password' => 'clave-segura',
            'role' => 'moderator',
        ];
    }
}
