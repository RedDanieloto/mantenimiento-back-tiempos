<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    // The primary key column is 'employee_number' (bigint by default via $table->id('employee_number'))
    protected $primaryKey = 'employee_number';
    public $incrementing = true; // it's an auto-incrementing big integer
    protected $keyType = 'int';

    protected $fillable = [
        'employee_number',
        'name',
        'role',
        'turno',
    ];


}
