<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Assessment extends Model
{
    protected $fillable = [
        'laptop_name',
        'lcd_input',
        'battery_input',
        'ram_input',
        'keyboard_input',
        'final_score',
        'status',
        'description',
        'ai_conclusion',
    ];

    protected $casts = [
        'lcd_input' => 'float',
        'battery_input' => 'float',
        'ram_input' => 'float',
        'keyboard_input' => 'float',
        'final_score' => 'float',
    ];
}
