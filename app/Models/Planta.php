<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Planta extends Model
{
    protected $table = "plantas";
    protected $primaryKey = 'id';

    protected $fillable = [
        'cod_sirh',
        'nombre'
    ];
}
