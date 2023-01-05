<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class Asistencia extends Model
{
    protected $table = "asistencias";
    protected $primaryKey = 'id';

    protected $fillable = [
        'uuid',
        'dia',
        'mes',
        'anio',
        'fecha',
        'observacion',
        'user_id',
        'recarga_id',
        'establecimiento_id',
        'tipo_asistencia_turno_id',
        'user_created_by',
        'date_created_user',
        'user_update_by',
        'date_updated_user'
    ];

    public function funcionario()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function recarga()
    {
        return $this->belongsTo(Recarga::class, 'recarga_id');
    }

    public function establecimiento()
    {
        return $this->belongsTo(Establecimiento::class, 'establecimiento_id');
    }

    public function tipoAsistenciaTurno()
    {
        return $this->belongsTo(TipoAsistenciaTurno::class, 'tipo_asistencia_turno_id');
    }

    protected static function booted()
    {
        static::creating(function ($asistencia) {
            $asistencia->uuid                   = Str::uuid();
            $asistencia->user_created_by        = Auth::user()->id;
            $asistencia->date_created_user      = Carbon::now()->toDateTimeString();
        });

        static::updating(function ($asistencia) {
            $asistencia->user_update_by         = Auth::user()->id;
            $asistencia->date_updated_user      = Carbon::now()->toDateTimeString();
        });
    }
}
