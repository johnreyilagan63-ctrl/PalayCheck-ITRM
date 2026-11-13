<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LearningMaterial extends Model
{
    protected $table = 'learning_materials';

    public $timestamps = false;

    protected $fillable = [
        'title',
        'category',
        'summary',
        'content',
        'status',
    ];
}
