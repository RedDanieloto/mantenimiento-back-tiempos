<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Area extends Model
{
    protected $fillable = ['name'];

    //Una area tiene muchas lineas
    public function lineas()
    {
        return $this->hasMany(Linea::class);
    }
    
}
