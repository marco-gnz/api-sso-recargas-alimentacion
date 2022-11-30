<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Auth;

class UserTurno extends Model
{
    protected $table = "user_turnos";
    protected $primaryKey = 'id';

    protected $fillable = [
        'uuid',
        'anio',
        'mes',
        'asignacion_tercer_turno',
        'bonificacion_asignacion_turno',
        'asignacion_cuarto_turno',
        'es_turnante',
        'user_id',
        'recarga_id',
        'proceso_id',
        'calidad_id',
        'establecimiento_id',
        'unidad_id',
        'planta_id',
        'user_created_by',
        'date_created_user',
        'user_update_by',
        'date_updated_user'
    ];

    public function proceso()
    {
        return $this->belongsTo(ProcesoTurno::class, 'proceso_id');
    }

    protected static function booted()
    {
        static::creating(function ($recarga) {
            $recarga->uuid              = Str::uuid();
            $recarga->user_created_by   = Auth::user()->id;
            $recarga->date_created_user = Carbon::now()->toDateTimeString();
        });

        static::updating(function ($recarga) {
            $recarga->user_update_by    = Auth::user()->id;
            $recarga->date_updated_user = Carbon::now()->toDateTimeString();
        });
    }
}
