<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Regla extends Model
{
    protected $table = "reglas";
    protected $primaryKey = 'id';

    protected $fillable = [
        'hora_inicio',
        'hora_termino',
        'active',
        'grupo_id',
        'recarga_id',
        'tipo_ausentismo_id'
    ];

    public function grupoAusentismo()
    {
        return $this->belongsTo(GrupoAusentismo::class, 'grupo_id');
    }

    public function recarga()
    {
        return $this->belongsTo(Recarga::class, 'recarga_id');
    }

    public function tipoAusentismo()
    {
        return $this->belongsTo(TipoAusentismo::class, 'tipo_ausentismo_id');
    }

    public function meridianos()
    {
        return $this->belongsToMany(Meridiano::class);
    }
}
