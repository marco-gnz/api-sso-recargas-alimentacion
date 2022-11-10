<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class Ausentismo extends Model
{
    protected $table = "ausentismos";
    protected $primaryKey = 'id';

    protected $fillable = [
        'uuid',
        'turno',
        'fecha_inicio',
        'fecha_termino',
        'fecha_inicio_periodo',
        'fecha_termino_periodo',
        'total_dias_ausentismo',
        'total_dias_ausentismo_periodo',
        'hora_inicio',
        'hora_termino',
        'user_id',
        'tipo_ausentismo_id',
        'regla_id',
        'grupo_id',
        'establecimiento_id',
        'unidad_id',
        'planta_id',
        'cargo_id',
        'meridiano_id',
        'recarga_id',
        'user_created_by',
        'date_created_user',
        'user_update_by',
        'date_updated_user'
    ];

    public function recarga()
    {
        return $this->belongsTo(Recarga::class, 'recarga_id');
    }

    public function funcionario()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function grupoAusentismo()
    {
        return $this->belongsTo(GrupoAusentismo::class, 'grupo_id');
    }

    public function regla()
    {
        return $this->belongsTo(Regla::class, 'regla_id');
    }

    protected static function booted()
    {
        static::creating(function ($ausentismo) {
            $ausentismo->uuid                  = Str::uuid();
            $ausentismo->user_created_by   = Auth::user()->id;
            $ausentismo->date_created_user = Carbon::now()->toDateTimeString();
        });

        static::updating(function ($ausentismo) {
            $ausentismo->user_update_by    = Auth::user()->id;
            $ausentismo->date_updated_user = Carbon::now()->toDateTimeString();
        });
    }
}
