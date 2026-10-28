<?php

namespace App\Domain\Identity;

final class Departments
{
    /**
     * @return list<string>
     */
    public static function all(): array
    {
        return [
            'Chuquisaca',
            'La Paz',
            'Cochabamba',
            'Oruro',
            'Potosí',
            'Tarija',
            'Santa Cruz',
            'Beni',
            'Pando',
        ];
    }
}
