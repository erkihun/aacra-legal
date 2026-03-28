<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SequenceCounter extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'scope',
        'year',
        'next_value',
    ];

    protected function casts(): array
    {
        return [
            'year' => 'integer',
            'next_value' => 'integer',
        ];
    }
}
