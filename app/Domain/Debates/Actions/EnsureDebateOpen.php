<?php

namespace App\Domain\Debates\Actions;

use App\Models\Argument;
use App\Models\Topic;
use Illuminate\Validation\ValidationException;

class EnsureDebateOpen
{
    public function execute(Topic|Argument $subject): void
    {
        $topic = $subject instanceof Topic ? $subject : $subject->topic;

        if ($topic !== null && $topic->closed_at !== null) {
            throw ValidationException::withMessages([
                'debate' => 'Este debate está cerrado.',
            ]);
        }
    }
}
