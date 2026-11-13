<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Detection extends Model
{
    protected $table = 'detections';

    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'pest_id',
        'label',
        'confidence',
        'severity',
        'count',
        'image_file',
        'source',
        'note',
        'created_at',
    ];

    protected $casts = [
        'confidence' => 'float',
        'count' => 'integer',
        'created_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function pest()
    {
        return $this->belongsTo(Pest::class);
    }
}
