<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class herramental extends Model
{
    protected $fillable = [
        'name',
        'linea_id',
    ];

    public function linea()
    {
        return $this->belongsTo(Linea::class);
    }
}
