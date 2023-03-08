<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class ObservacionesAsistencia extends Model
{
    protected $table = "observaciones_asistencias";
    protected $primaryKey = 'id';

    protected $fillable = [
        'fecha',
        'observacion',
        'asistencia_id',
        'tipo_asistencia_turno_id',
        'user_created_by',
        'date_created_user'
    ];

    public function asistencia()
    {
        return $this->belongsTo(Asistencia::class, 'asistencia_id');
    }

    public function tipoAsistenciaTurno()
    {
        return $this->belongsTo(TipoAsistenciaTurno::class, 'tipo_asistencia_turno_id');
    }

    public function userCreatedBy()
    {
        return $this->belongsTo(User::class, 'user_created_by');
    }

    protected static function booted()
    {
        static::creating(function ($asistencia_observacion) {
            $asistencia_observacion->user_created_by        = Auth::user()->id;
            $asistencia_observacion->date_created_user      = Carbon::now()->toDateTimeString();
        });
    }
}
