<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Linea extends Model
{
    protected $fillable = ['name', 'area_id'];

    //Una linea pertenece a un area
    public function area()
    {
        return $this->belongsTo(Area::class);
    }

    //Una linea tiene muchas maquinas
    public function maquinas()
    {
        return $this->hasMany(Maquina::class);
    }
}
