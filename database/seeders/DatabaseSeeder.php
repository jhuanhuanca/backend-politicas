<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();

        User::query()->firstOrCreate(
            ['nick' => 'demo'],
            ['device_fingerprint' => hash('sha256', 'seed-demo')],
        );

        $this->call(DebateSeeder::class);
        $this->call(NewsSeeder::class);
        $this->call(NewsSourceSeeder::class);
        $this->call(PollSeeder::class);
    }
}
