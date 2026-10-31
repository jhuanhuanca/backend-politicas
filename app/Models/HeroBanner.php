<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HeroBanner extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'slot',
        'announcement',
        'kind',
        'media',
    ];
}
