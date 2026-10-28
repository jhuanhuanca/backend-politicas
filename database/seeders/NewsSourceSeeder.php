<?php

namespace Database\Seeders;

use App\Models\NewsSource;
use App\Models\Topic;
use Illuminate\Database\Seeder;

class NewsSourceSeeder extends Seeder
{
    public function run(): void
    {
        $night = Topic::query()->where('body', 'like', 'El borrador deja fuera%')->value('id');

        NewsSource::query()->updateOrCreate(
            ['feed_url' => 'http://127.0.0.1:8000/feeds/mesa-ciudad.xml'],
            [
                'name' => 'Mesa de movilidad',
                'topic_id' => $night,
                'active' => true,
            ],
        );
    }
}
