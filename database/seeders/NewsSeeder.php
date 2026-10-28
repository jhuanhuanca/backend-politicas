<?php

namespace Database\Seeders;

use App\Models\NewsItem;
use App\Models\Topic;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

class NewsSeeder extends Seeder
{
    public function run(): void
    {
        $budget = Topic::query()->where('body', 'like', 'Si el orden de las obras%')->value('id');
        $night = Topic::query()->where('body', 'like', 'El borrador deja fuera%')->value('id');

        $this->item(
            'Diez preguntas para la audiencia del presupuesto.',
            'Antes de la audiencia conviene anotar qué obra queda fuera si se aplica un tope por zona.',
            'Mesa de presupuesto',
            $budget,
            2,
        );
        $this->item(
            'Cinco datos del informe de empleo local.',
            'El informe separa empleo formal, cuidados y trabajo por turnos. Sirve para leer una propuesta sin mezclar cifras.',
            'Observatorio local',
            null,
            3,
        );
        $this->item(
            'Cómo leer una propuesta antes de votarla.',
            'Una propuesta se lee por lo que cambia, a quién deja fuera y qué dato la sostiene.',
            'Guía ciudadana',
            null,
            4,
        );
        $this->item(
            'Qué cambia si el mercado unifica su horario.',
            'Un horario único mueve el último bus de la noche y deja sin opción a quien sale después de las 21:00.',
            'Mesa de movilidad',
            $night,
            6,
        );
    }

    private function item(string $title, string $summary, string $source, mixed $topicId, int $hoursAgo): void
    {
        $item = NewsItem::query()->firstOrCreate(
            ['title' => $title],
            [
                'summary' => $summary,
                'source' => $source,
                'topic_id' => $topicId,
                'published_at' => Carbon::now()->subHours($hoursAgo),
            ],
        );

        if ($item->wasRecentlyCreated) {
            return;
        }

        $item->update([
            'summary' => $summary,
            'source' => $source,
            'topic_id' => $topicId,
        ]);
    }
}
