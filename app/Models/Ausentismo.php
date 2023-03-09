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
        'total_dias_habiles_ausentismo_periodo',
        'hora_inicio',
        'hora_termino',
        'total_horas_ausentismo',
        'tiene_descuento',
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

    public function tipoAusentismo()
    {
        return $this->belongsTo(TipoAusentismo::class, 'tipo_ausentismo_id');
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

    public function cargo()
    {
        return $this->belongsTo(Cargo::class, 'cargo_id');
    }

    public function meridiano()
    {
        return $this->belongsTo(Meridiano::class, 'meridiano_id');
    }

    protected static function booted()
    {
        static::creating(function ($ausentismo) {
            $days = 0;
            $inicio     = Carbon::parse($ausentismo->fecha_inicio_periodo)->format('Y-m-d');
            $termino    = Carbon::parse($ausentismo->fecha_termino_periodo)->format('Y-m-d');
            for ($i = $inicio; $i <= $termino; $i++) {
                $i_format       = Carbon::parse($i)->isWeekend();
                if($i_format){
                    $days++;
                }
            }

            $ausentismo->uuid                   = Str::uuid();
            $ausentismo->user_created_by        = Auth::user()->id;
            $ausentismo->date_created_user      = Carbon::now()->toDateTimeString();
            $ausentismo->total_dias_habiles_ausentismo_periodo = ($ausentismo->total_dias_ausentismo_periodo - $days);
        });

        static::updating(function ($ausentismo) {
            $ausentismo->user_update_by    = Auth::user()->id;
            $ausentismo->date_updated_user = Carbon::now()->toDateTimeString();
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
}
