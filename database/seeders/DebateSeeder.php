<?php

namespace Database\Seeders;

use App\Models\Argument;
use App\Models\Topic;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

class DebateSeeder extends Seeder
{
    public function run(): void
    {
        $clara = $this->voice('clara_rivas');
        $elena = $this->voice('elena_cruz');
        $luis = $this->voice('luis_navarro');
        $andres = $this->voice('andres_palma');
        $ivan = $this->voice('ivan_paredes');
        $rosa = $this->voice('rosa_aguilar');
        $marta = $this->voice('marta_quispe');

        $night = $this->topic(
            $clara,
            'El borrador deja fuera a los barrios del norte después de las 21:00. Si el transporte se recorta, la gente que sale del turno noche se queda sin opción segura.',
            'Mesa de movilidad',
            $this->photo('photo-1551836022-d5d88e9218df'),
            'image',
            56,
            2,
        );

        $market = $this->comment($night, $elena, 'En el mercado el último bus pasa a las 20:10. Habría que publicar el horario real, no el del mapa.', 3, 5);
        $this->comment($night, $luis, 'Puedo llevar el conteo de la parada de la avenida durante una semana.', 1, 4, $market);
        $this->comment($night, $andres, 'La ciclovía no reemplaza el bus de noche. Son dos necesidades distintas y las dos tienen que quedar escritas.', 5, 2);

        $budget = $this->topic(
            $ivan,
            'Si el orden de las obras se decide solo por likes, los barrios con menos gente conectada quedan últimos. Propongo un tope por zona.',
            'Presupuesto participativo',
            $this->photo('photo-1469474968028-56623f02e42e'),
            'video',
            58,
            6,
        );

        $this->comment($budget, $rosa, 'De acuerdo con el tope. En cuidados siempre quedamos debajo de las obras grandes.', 3, 5);
        $this->comment($budget, $marta, 'El agua no puede competir en la misma lista que un evento. Hay que separar lo urgente de lo deseable.', 5, 2);
    }

    private function voice(string $nick): User
    {
        return User::query()->firstOrCreate(
            ['nick' => $nick],
            ['device_fingerprint' => hash('sha256', 'seed-'.$nick)],
        );
    }

    private function topic(User $author, string $body, string $context, string $image, string $kind, int $likes, int $hoursAgo): Topic
    {
        $topic = Topic::query()->firstOrCreate(
            ['body' => $body],
            [
                'user_id' => $author->id,
                'context' => $context,
                'image' => $image,
                'kind' => $kind,
                'likes_count' => $likes,
                'shares_count' => 3,
            ],
        );

        if ($topic->wasRecentlyCreated) {
            $moment = Carbon::now()->subHours($hoursAgo);
            Topic::query()->whereKey($topic->id)->update([
                'created_at' => $moment,
                'updated_at' => $moment,
            ]);
        }

        return $topic;
    }

    private function comment(Topic $topic, User $author, string $body, int $likes, int $hoursAgo, ?Argument $parent = null): Argument
    {
        $argument = Argument::query()->firstOrCreate(
            ['topic_id' => $topic->id, 'body' => $body],
            [
                'user_id' => $author->id,
                'parent_id' => $parent?->id,
                'likes_count' => $likes,
            ],
        );

        if ($argument->wasRecentlyCreated) {
            $moment = Carbon::now()->subHours($hoursAgo);
            Argument::query()->whereKey($argument->id)->update([
                'created_at' => $moment,
                'updated_at' => $moment,
            ]);
        }

        return $argument;
    }

    private function photo(string $id): string
    {
        return 'https://images.unsplash.com/'.$id.'?auto=format&fit=crop&w=900&q=70';
    }
}
