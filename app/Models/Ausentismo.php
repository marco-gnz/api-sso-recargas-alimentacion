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
        'total_dias_habiles_ausentismo_periodo_meridiano',
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
        'esquema_id',
        'user_created_by',
        'date_created_user',
        'user_update_by',
        'date_updated_user'
    ];

    public function recarga()
    {
        return $this->belongsTo(Recarga::class, 'recarga_id');
    }

    public function esquema()
    {
        return $this->belongsTo(Esquema::class, 'esquema_id');
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
                if ($i_format) {
                    $days++;
                }
            }

            $ausentismo->uuid                                   = Str::uuid();
            $ausentismo->user_created_by                        = Auth::user()->id;
            $ausentismo->date_created_user                      = Carbon::now()->toDateTimeString();
            $total_dias_habiles_ausentismo_periodo              = ($ausentismo->total_dias_ausentismo_periodo - $days);
            $total_dias_habiles_ausentismo_periodo              = $total_dias_habiles_ausentismo_periodo <= 0 ? 0 : $total_dias_habiles_ausentismo_periodo;
            $ausentismo->total_dias_habiles_ausentismo_periodo  = $total_dias_habiles_ausentismo_periodo;
        });

        static::created(function ($ausentismo) {
            $feriados_count                                     = $ausentismo->recarga->feriados()->where('active', true)->whereBetween('fecha', [$ausentismo->fecha_inicio_periodo, $ausentismo->fecha_termino_periodo])->count();
            $total                                              = $ausentismo->total_dias_habiles_ausentismo_periodo - $feriados_count;
            $ausentismo->total_dias_habiles_ausentismo_periodo  = $total;
            $ausentismo->save();
        });

        static::updating(function ($ausentismo) {
            $ausentismo->user_update_by    = Auth::user()->id;
            $ausentismo->date_updated_user = Carbon::now()->toDateTimeString();
        });

        static::deleted(function ($ausentismo) {
            if ($ausentismo->esquema) {
                $grupo_id = $ausentismo->grupo_id;

                switch ($grupo_id) {
                    case 1:
                        $ausentismo->esquema->total_dias_grupo_uno = 0;
                        $ausentismo->esquema->total_dias_habiles_grupo_uno = 0;
                        $ausentismo->esquema->total_dias_feriados_grupo_uno = 0;
                        $ausentismo->esquema->grupo_uno_n_registros = 0;
                        $ausentismo->esquema->calculo_grupo_uno = 0;
                        $ausentismo->esquema->save();
                        break;

                    case 2:
                        $ausentismo->esquema->total_dias_grupo_dos = 0;
                        $ausentismo->esquema->total_dias_habiles_grupo_dos = 0;
                        $ausentismo->esquema->total_dias_feriados_grupo_dos = 0;
                        $ausentismo->esquema->grupo_dos_n_registros = 0;
                        $ausentismo->esquema->calculo_grupo_dos = 0;
                        $ausentismo->esquema->save();
                        break;

                    case 3:
                        $ausentismo->esquema->total_dias_grupo_tres = 0;
                        $ausentismo->esquema->total_dias_habiles_grupo_tres = 0;
                        $ausentismo->esquema->total_dias_feriados_grupo_tres = 0;
                        $ausentismo->esquema->grupo_tres_n_registros = 0;
                        $ausentismo->esquema->calculo_grupo_tres = 0;
                        $ausentismo->esquema->save();
                        break;
                }
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

    public function scopeTipoAusentismo($query, $ids)
    {
        if ($ids) {
            return $query->whereIn('tipo_ausentismo_id', $ids);
        }
    }
}
