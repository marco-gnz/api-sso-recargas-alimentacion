<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Calidad extends Model
{
    protected $table = "calidads";
    protected $primaryKey = 'id';

    protected $fillable = [
        'cod_sirh',
        'nombre'
    ];
}
