<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AssessmentImage extends Model
{
    protected $fillable = [
        'assessment_id',
        'image_path'
    ];

    public function assessment()
    {
        return $this->belongsTo(Assessment::class, 'assessment_id');
    }
}
