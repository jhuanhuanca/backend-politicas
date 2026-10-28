<?php

namespace App\Domain\Moderation\Exceptions;

use RuntimeException;

class StaffTotpException extends RuntimeException
{
    public function __construct(public readonly string $notice = 'codigo')
    {
        parent::__construct();
    }
}
