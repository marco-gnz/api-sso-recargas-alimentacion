<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ReglaHorario extends Model
{
    protected $table = "regla_horarios";
    protected $primaryKey = 'id';

    protected $fillable = [
        'hora_inicio',
        'hora_termino',
        'regla_id'
    ];

    public function regla()
    {
        return $this->belongsTo(Regla::class, 'regla_id');
    }
}
