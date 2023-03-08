<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Feriado extends Model
{
    protected $table = "feriados";
    protected $primaryKey = 'id';

    protected $fillable = [
        'nombre',
        'observacion',
        'anio',
        'mes',
        'fecha',
        'irrenunciable',
        'tipo'
    ];

    public function recargas()
    {
        return $this->belongsToMany(Recarga::class)->withPivot('active');
    }
}
