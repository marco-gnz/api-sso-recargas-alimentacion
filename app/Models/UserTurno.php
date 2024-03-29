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
        'folio',
        'asignacion_tercer_turno',
        'bonificacion_asignacion_turno',
        'asignacion_cuarto_turno',
        'es_turnante',
        'user_id',
        'recarga_id',
        'proceso_id',
        'calidad_id',
        'esquema_id',
        'establecimiento_id',
        'unidad_id',
        'planta_id',
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

    public function proceso()
    {
        return $this->belongsTo(ProcesoTurno::class, 'proceso_id');
    }

    public function calidad()
    {
        return $this->belongsTo(Calidad::class, 'calidad_id');
    }

    public function establecimiento()
    {
        return $this->belongsTo(Establecimiento::class, 'establecimiento_id');
    }

    public function unidad()
    {
        return $this->belongsTo(Unidad::class, 'unidad_id');
    }

    public function planta()
    {
        return $this->belongsTo(Planta::class, 'planta_id');
    }

    public function userBy()
    {
        return $this->belongsTo(User::class, 'user_created_by');
    }

    public function userByUpdate()
    {
        return $this->belongsTo(User::class, 'user_update_by');
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

        static::deleted(function ($asignacion) {
            if ($asignacion->esquema) {
                $asignacion->esquema->turno_asignacion = false;
                $asignacion->esquema->save();
            }
        });
    }

    public function scopeInput($query, $input)
    {
        if ($input)
            return $query->where(function ($query) use ($input) {
                $query->whereHas('funcionario', function ($query) use ($input) {
                    $query->where('rut_completo', 'like', '%' . $input . '%')
                        ->orWhere('rut', 'like', '%' . $input . '%')
                        ->orWhere('nombres', 'like', '%' . $input . '%')
                        ->orWhere('apellidos', 'like', '%' . $input . '%')
                        ->orWhere('nombre_completo', 'like', '%' . $input . '%');
                });
            });
    }

    public function scopeValueAsignaciones($query, $asignaciones)
    {
        if ($asignaciones)
            return $query->where(function ($query) use ($asignaciones) {
                if (in_array('3_turno', $asignaciones) && in_array('b_turno', $asignaciones)  && in_array('4_turno', $asignaciones)) {
                    $query->where('asignacion_tercer_turno', '>', 0)->where('bonificacion_asignacion_turno', '>', 0)->where('asignacion_cuarto_turno', '>', 0);
                } else if (in_array('3_turno', $asignaciones)) {
                    $query->where('asignacion_tercer_turno', '>', 0);
                } else if (in_array('b_turno', $asignaciones)) {
                    $query->where('bonificacion_asignacion_turno', '>', 0);
                } else if (in_array('4_turno', $asignaciones)) {
                    $query->where('asignacion_cuarto_turno', '>', 0);
                }
            });
    }
}
