<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TipoAusentismo extends Model
{
    protected $table = "tipo_ausentismos";
    protected $primaryKey = 'id';

    protected $fillable = [
        'codigo_sirh',
        'nombre',
        'estado'
    ];
}
