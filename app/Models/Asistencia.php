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
        'esquema_id',
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

    public function esquema()
    {
        return $this->belongsTo(Esquema::class, 'esquema_id');
    }

    public function establecimiento()
    {
        return $this->belongsTo(Establecimiento::class, 'establecimiento_id');
    }

    public function tipoAsistenciaTurno()
    {
        return $this->belongsTo(TipoAsistenciaTurno::class, 'tipo_asistencia_turno_id');
    }

    public function observaciones()
    {
        return $this->hasMany(ObservacionesAsistencia::class)->orderBy('created_at', 'desc');
    }

    public function userCreatedBy()
    {
        return $this->belongsTo(User::class, 'user_created_by');
    }

    public function createobservaciones(array $attributes)
    {
        $this->observaciones()->create($attributes);
    }

    protected static function booted()
    {
        static::creating(function ($asistencia) {
            $asistencia->uuid                   = Str::uuid();
            $asistencia->user_created_by        = Auth::user()->id;
            $asistencia->date_created_user      = Carbon::now()->toDateTimeString();

            /* $asistencia->createobservaciones([
                'fecha'                     => $asistencia->fecha,
                'asistencia_id'             => $asistencia->id,
                'tipo_asistencia_turno_id'  => $asistencia->tipo_asistencia_turno_id
            ]); */
        });

        static::updating(function ($asistencia) {
            $asistencia->user_update_by         = Auth::user()->id;
            $asistencia->date_updated_user      = Carbon::now()->toDateTimeString();
        });

        static::deleted(function ($asistencia) {
            if ($asistencia->esquema) {
                $asistencia->esquema->es_turnante_value = false;
                $asistencia->esquema->total_dias_turno_largo = 0;
                $asistencia->esquema->total_dias_turno_nocturno = 0;
                $asistencia->esquema->total_dias_libres = 0;
                $asistencia->esquema->total_dias_turno_largo_en_periodo_contrato = 0;
                $asistencia->esquema->total_dias_turno_nocturno_en_periodo_contrato = 0;
                $asistencia->esquema->total_dias_libres_en_periodo_contrato = 0;
                $asistencia->esquema->total_dias_feriados_turno_en_periodo_contrato = 0;
                $asistencia->esquema->total_dias_feriados_turno = 0;
                $asistencia->esquema->calculo_turno = 0;
                $asistencia->esquema->total_turno = 0;
                $asistencia->esquema->save();
            }
        });
    }
}
