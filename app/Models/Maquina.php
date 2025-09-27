<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Maquina extends Model
{
    protected $fillable = ['name', 'linea_id'];

    //Una maquina pertenece a una linea
    public function linea()
    {
        return $this->belongsTo(Linea::class);
    }
}
