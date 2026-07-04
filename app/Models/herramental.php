<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Herramental extends Model
{
    protected $table = 'herramentals';

    protected $fillable = [
        'name',
        'linea_id',
    ];

    // Relacion con la linea a la que pertenece
    public function linea()
    {
        return $this->belongsTo(Linea::class);
    }
}
