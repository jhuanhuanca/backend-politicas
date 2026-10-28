<?php

namespace Database\Seeders;

use App\Models\Poll;
use Illuminate\Database\Seeder;

class PollSeeder extends Seeder
{
    public function run(): void
    {
        $this->poll(
            '¿Qué obra va primero en el presupuesto participativo?',
            'photo-1517048676732-d65bc937f952',
            'Abierta',
            'Centro',
            'economia',
            1,
            [
                ['Agua en los barrios altos', 42],
                ['Buses después de las 21:00', 36],
                ['Parque en el lote norte', 18],
            ],
        );
        $this->poll(
            '¿Qué horario de carga prefieres para el mercado?',
            'photo-1414235077428-338989a2e8c0',
            'Cierra hoy',
            'Mercado central',
            'economia',
            2,
            [
                ['6:00 a 8:00', 27],
                ['14:00 a 16:00', 11],
                ['Por turnos de cuadra', 19],
            ],
        );
        $this->poll(
            '¿La audiencia del aire se hace presencial o también en línea?',
            'photo-1475721027785-f74eccf877e2',
            'En línea',
            'En línea',
            'territorio',
            3,
            [
                ['Solo presencial', 8],
                ['Presencial y en línea', 51],
                ['Solo en línea', 14],
            ],
        );
        $this->poll(
            '¿Dónde conviene el punto de cuidado nocturno?',
            'photo-1529156069898-49953e39b3ac',
            'En vivo',
            'Norte',
            'cuidados',
            4,
            [
                ['Junto al mercado', 22],
                ['En la escuela del norte', 17],
                ['En la terminal de buses', 29],
            ],
        );
    }

    /**
     * @param  list<array{0: string, 1: int}>  $options
     */
    private function poll(string $title, string $photo, string $badge, string $place, string $category, int $position, array $options): void
    {
        $poll = Poll::query()->firstOrCreate(
            ['title' => $title],
            [
                'image' => 'https://images.unsplash.com/'.$photo.'?auto=format&fit=crop&w=900&q=70',
                'badge' => $badge,
                'place' => $place,
                'category' => $category,
                'position' => $position,
            ],
        );

        foreach ($options as $index => $option) {
            $poll->options()->firstOrCreate(
                ['label' => $option[0]],
                [
                    'votes_count' => $option[1],
                    'position' => $index + 1,
                ],
            );
        }
    }
}
