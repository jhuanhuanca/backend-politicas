<?php

namespace App\Models;

use Database\Factories\StaffFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Sanctum\HasApiTokens;

class Staff extends Authenticatable
{
    /** @use HasFactory<StaffFactory> */
    use HasApiTokens, HasFactory;

    protected $table = 'staff';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'totp_secret',
        'totp_confirmed_at',
        'totp_last_step',
    ];

    /**
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'totp_secret',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'password' => 'hashed',
            'totp_secret' => 'encrypted',
            'totp_confirmed_at' => 'datetime',
        ];
    }
}
