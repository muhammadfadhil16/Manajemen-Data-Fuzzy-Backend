<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Assessment extends Model
{
    protected $fillable = [
        'laptop_name',
        'lcd_input',
        'battery_input',
        'processor_input',
        'keyboard_input',
        'final_score',
        'status',
        'market_price',
        'estimated_price',
        'description',
        'ai_conclusion',
    ];

    protected $casts = [
        'lcd_input' => 'float',
        'battery_input' => 'float',
        'processor_input' => 'float',
        'keyboard_input' => 'float',
        'final_score' => 'float',
        'market_price' => 'integer',
        'estimated_price' => 'integer',
    ];
}
