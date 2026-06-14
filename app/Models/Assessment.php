<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Assessment extends Model
{
    protected $fillable = [
        'customer_name',
        'laptop_name',
        'lcd_input',
        'battery_input',
        'processor_input',
        'keyboard_input',
        'ram_input',
        'processor_id',
        'final_score',
        'status',
        'market_price',
        'estimated_price',
        'description',
        'ai_conclusion',
    ];

    protected $casts = [
        'lcd_input' => 'integer',
        'battery_input' => 'integer',
        'processor_input' => 'float',
        'keyboard_input' => 'integer',
        'ram_input' => 'float',
        'final_score' => 'float',
        'market_price' => 'integer',
        'estimated_price' => 'integer',
    ];

    public function processor()
    {
        return $this->belongsTo(Processor::class);
    }
    public function images()
    {
        return $this->hasMany(AssessmentImage::class, 'assessment_id');
    }
}
