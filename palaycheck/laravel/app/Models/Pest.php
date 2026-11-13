<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Pest extends Model
{
    protected $table = 'pests';

    public $timestamps = false;

    protected $fillable = [
        'name',
        'scientific_name',
        'description',
        'symptoms',
        'effects',
        'status',
    ];

    public function advice()
    {
        return $this->hasMany(Advice::class);
    }

    public function detections()
    {
        return $this->hasMany(Detection::class);
    }
}
