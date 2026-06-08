<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FuzzyRule extends Model
{
    protected $fillable = [
        'lcd',
        'keyboard',
        'ram',
        'baterai',
        'processor',
        'output',
    ];
}
