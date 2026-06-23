<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FuzzyThreshold extends Model
{
    protected $fillable = [
        'name',
        'value',
    ];

    protected $casts = [
        'value' => 'decimal:2',
    ];
}
