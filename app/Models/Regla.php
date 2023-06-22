<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Regla extends Model
{
    protected $table = "reglas";
    protected $primaryKey = 'id';

    protected $fillable = [
        'active',
        'turno_funcionario',
        'active_tipo_dias',
        'tipo_dias',
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
        return $this->belongsToMany(Meridiano::class)->withPivot('active');
    }

    public function ausentismos()
    {
        return $this->hasMany(Ausentismo::class);
    }

    public function horarios()
    {
        return $this->hasMany(ReglaHorario::class);
    }

    protected static function booted()
    {
        static::updated(function ($regla) {
            $regla->ausentismos->each(function ($ausentismo) use ($regla) {
                $ausentismo->grupo_id = $regla->grupo_id;
                $ausentismo->save();
            });
        });
    }
}
