<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FuzzyRule extends Model
{
    protected $fillable = [
        'variable',
        'category',
        'curve_type',
        'parameters',
    ];

    protected $casts = [
        'parameters' => 'array',
    ];
}
