<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Advice extends Model
{
    protected $table = 'advice';

    public $timestamps = false;

    protected $fillable = [
        'pest_id',
        'title',
        'category',
        'content',
        'status',
    ];

    public function pest()
    {
        return $this->belongsTo(Pest::class);
    }
}
