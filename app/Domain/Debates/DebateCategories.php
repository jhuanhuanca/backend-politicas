<?php

namespace App\Domain\Debates;

final class DebateCategories
{
    /**
     * @return list<string>
     */
    public static function all(): array
    {
        return ['politica', 'territorio', 'economia'];
    }
}
