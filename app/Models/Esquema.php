<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class Esquema extends Model
{
    public const TURNANTE           = 1;
    public const NO_TURNANTE        = 2;
    public const TURNANTE_ERROR     = 3;

    public const ERROR_1            = 1;
    public const ERROR_2            = 2;
    public const ERROR_3            = 3;

    public const ADVERTENCIA_1      = 1;
    public const ADVERTENCIA_2      = 2;
    public const ADVERTENCIA_3      = 3;
    public const ADVERTENCIA_4      = 4;
    public const ADVERTENCIA_5      = 5;

    public const TURNANTE_NOM = [
        self::TURNANTE               => 'Si',
        self::NO_TURNANTE            => 'No',
        self::TURNANTE_ERROR         => 'ERROR_T',
    ];

    public const ERROR_NOM = [
        self::ERROR_1               => 'SIN BENEFICIO CON AUSENTISMOS/VIÁTICOS/CONTRATOS CARGADOS',
        self::ERROR_2               => 'FECHAS DE AUSENTISMOS ENTRELAZADAS',
        self::ERROR_3               => 'ERROR EN TURNO',
    ];

    public const ADVERTENCIA_NOM = [
        self::ADVERTENCIA_1         => 'CONTRATO CON FECHA DE ALEJAMIENTO',
        self::ADVERTENCIA_2         => 'DÍAS A CANCELAR EN 0',
        self::ADVERTENCIA_3         => 'MONTO TOTAL A CANCELAR MAYOR AL ESTIMADO',
        self::ADVERTENCIA_4         => 'PAGOS MAYOR AL ESTIMADO SIN AJUSTES',
        self::ADVERTENCIA_5         => 'TIENE MAS DÍAS A CANCELAR QUE CONTRATO'
    ];

    protected $table = "esquemas";
    protected $primaryKey = 'id';

    public $timestamps = false;

    protected $fillable = [
        'uuid',
        'tipo_ingreso',
        'active',
        'es_turnante',
        'es_turnante_value',
        'es_remplazo',
        'turno_asignacion',
        'total_dias_contrato',
        'total_dias_habiles_contrato',
        'total_dias_feriados_contrato',
        'contrato_n_registros',
        'calculo_contrato',
        'fecha_alejamiento',
        'total_dias_turno_largo',
        'total_dias_turno_nocturno',
        'total_dias_libres',
        'total_dias_turno_largo_en_periodo_contrato',
        'total_dias_turno_nocturno_en_periodo_contrato',
        'total_dias_libres_en_periodo_contrato',
        'total_dias_feriados_turno_en_periodo_contrato',
        'total_dias_feriados_turno',
        'calculo_turno',
        'total_turno',
        'total_dias_grupo_uno',
        'total_dias_habiles_grupo_uno',
        'total_dias_feriados_grupo_uno',
        'grupo_uno_n_registros',
        'calculo_grupo_uno',
        'total_dias_grupo_dos',
        'total_dias_habiles_grupo_dos',
        'total_dias_feriados_grupo_dos',
        'grupo_dos_n_registros',
        'calculo_grupo_dos',
        'total_dias_grupo_tres',
        'total_dias_habiles_grupo_tres',
        'total_dias_feriados_grupo_tres',
        'grupo_tres_n_registros',
        'calculo_grupo_tres',
        'fechas_cruzadas',
        'total_dias_viaticos',
        'total_dias_habiles_viaticos',
        'total_dias_feriados_viaticos',
        'viaticos_n_registros',
        'calculo_viaticos',
        'total_dias_ajustes',
        'total_dias_habiles_ajustes',
        'total_dias_feriados_ajustes',
        'ajustes_dias_n_registros',
        'calculo_dias_ajustes',
        'total_monto_ajuste',
        'ajustes_monto_n_registros',
        'total_dias_cancelar',
        'monto_total_cancelar',
        'user_id',
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

    public function contratos()
    {
        return $this->hasMany(RecargaContrato::class)->orderBy('fecha_inicio_periodo', 'ASC');
    }

    public function turnos()
    {
        return $this->hasMany(UserTurno::class)->orderBy('asignacion_tercer_turno', 'ASC');
    }

    public function asistencias()
    {
        return $this->hasMany(Asistencia::class)->orderBy('fecha', 'ASC');
    }

    public function ausentismos()
    {
        return $this->hasMany(Ausentismo::class)->orderBy('fecha_inicio_periodo', 'ASC');
    }

    public function viaticos()
    {
        return $this->hasMany(Viatico::class)->orderBy('fecha_inicio_periodo', 'ASC');
    }

    public function reajustes()
    {
        return $this->hasMany(Reajuste::class);
    }

    public function userCreatedBy()
    {
        return $this->belongsTo(User::class, 'user_created_by');
    }

    public static function esTurnante($esquema)
    {
        $es_turnante = 2;
        $total_registros_turnos = $esquema->total_dias_turno_largo_en_periodo_contrato + $esquema->total_dias_turno_nocturno_en_periodo_contrato  + $esquema->total_dias_libres_en_periodo_contrato;

        if (($esquema->turno_asignacion && $esquema->calculo_turno > 0 && $esquema->contrato_n_registros > 0) || ($esquema->calculo_turno === true > 0 && $esquema->contrato_n_registros > 0)) {
            $es_turnante = 1;
        } else if (!$esquema->turno_asignacion && $esquema->calculo_turno > 0 && $esquema->contrato_n_registros > 0) {
            $es_turnante = $esquema->es_remplazo ? 1 : 3;
        } else if ($esquema->calculo_turno <= 0 && $esquema->turno_asignacion && $esquema->contrato_n_registros > 0) {
            $es_turnante = $esquema->es_remplazo ? 1 : 3;
        } else if ($esquema->contrato_n_registros <= 0 && $esquema->turno_asignacion  && $esquema->calculo_turno > 0) {
            $es_turnante = $esquema->es_remplazo ? 1 : 3;
        }

        return $es_turnante;
    }

    public function totalDiasCancelar($esquema)
    {
        $total_dias_cancelar    = 0;
        $total_contrato         = 0;
        $total_ausentismos      = 0;
        $total_viaticos         = 0;
        $total_dias_ajustes     = 0;
        $total_monto_ajustes    = 0;
        if ($esquema->es_turnante_value) {
            $total_contrato      = $esquema->calculo_turno;
            $total_ausentismos   = $esquema->calculo_grupo_uno + $esquema->calculo_grupo_dos + $esquema->total_dias_grupo_tres;
            $total_viaticos      = $esquema->total_dias_viaticos;
            $total_dias_ajustes  = $esquema->calculo_dias_ajustes;
            $total_monto_ajustes = $esquema->total_monto_ajuste;

            $total_dias_cancelar = $total_contrato - ($total_ausentismos + $total_viaticos) + $total_dias_ajustes;
            $total_dias_cancelar = $total_dias_cancelar > 0 ? $total_dias_cancelar : 0;
        } else {
            $total_contrato      = $esquema->calculo_contrato;
            $total_ausentismos   = $esquema->calculo_grupo_uno + $esquema->calculo_grupo_dos + $esquema->total_dias_grupo_tres;
            $total_viaticos      = $esquema->calculo_viaticos;
            $total_dias_ajustes  = $esquema->calculo_dias_ajustes;
            $total_monto_ajustes = $esquema->total_monto_ajuste;

            $total_dias_cancelar = $total_contrato - ($total_ausentismos + $total_viaticos) + $total_dias_ajustes;
            $total_dias_cancelar = $total_dias_cancelar > 0 ? $total_dias_cancelar : 0;
        }
        /* switch ($esquema->es_turnante) {
            case 1:
            case 3:
                $total_contrato      = $esquema->calculo_turno;
                $total_ausentismos   = $esquema->calculo_grupo_uno + $esquema->calculo_grupo_dos + $esquema->total_dias_grupo_tres;
                $total_viaticos      = $esquema->total_dias_viaticos;
                $total_dias_ajustes  = $esquema->calculo_dias_ajustes;
                $total_monto_ajustes = $esquema->total_monto_ajuste;

                $total_dias_cancelar = $total_contrato - ($total_ausentismos + $total_viaticos) + $total_dias_ajustes;
                $total_dias_cancelar = $total_dias_cancelar > 0 ? $total_dias_cancelar : 0;


                break;

            case 2:
                $total_contrato      = $esquema->calculo_contrato;
                $total_ausentismos   = $esquema->calculo_grupo_uno + $esquema->calculo_grupo_dos + $esquema->total_dias_grupo_tres;
                $total_viaticos      = $esquema->calculo_viaticos;
                $total_dias_ajustes  = $esquema->calculo_dias_ajustes;
                $total_monto_ajustes = $esquema->total_monto_ajuste;

                $total_dias_cancelar = $total_contrato - ($total_ausentismos + $total_viaticos) + $total_dias_ajustes;
                $total_dias_cancelar = $total_dias_cancelar > 0 ? $total_dias_cancelar : 0;
                break;
        } */



        return $total_dias_cancelar;
    }

    protected static function booted()
    {
        static::creating(function ($esquema) {
            $esquema->es_turnante            = 2;
            $esquema->uuid                   = Str::uuid();
            $esquema->user_created_by        = Auth::user()->id;
            $esquema->date_created_user      = Carbon::now()->toDateTimeString();
        });

        static::created(function ($esquema) {
            $total_dias_cancelar             = self::totalDiasCancelar($esquema);
            $monto_total_dias                = $total_dias_cancelar * $esquema->recarga->monto_dia;
            $monto_total_cancelar            = $monto_total_dias + $esquema->total_monto_ajuste;

            $esquema->total_dias_cancelar    = $total_dias_cancelar;
            $esquema->monto_total_cancelar   = $monto_total_cancelar;
        });

        static::updating(function ($esquema) {
            $es_turnante                    = self::esTurnante($esquema);
            $total_dias_cancelar            = self::totalDiasCancelar($esquema);

            $monto_total_dias               = $total_dias_cancelar * $esquema->recarga->monto_dia;
            $monto_total_cancelar           = $monto_total_dias + $esquema->total_monto_ajuste;

            $esquema->es_turnante           = $es_turnante;
            $esquema->total_dias_cancelar   = $total_dias_cancelar;
            $esquema->monto_total_cancelar  = $monto_total_cancelar;
            $esquema->date_updated_user     = Carbon::now()->toDateTimeString();
        });

        static::deleting(function ($esquema) {
            if($esquema->contratos){
                $esquema->contratos()->update(['esquema_id' => null]);
            }

            if($esquema->turnos){
                $esquema->turnos()->update(['esquema_id' => null]);
            }

            if($esquema->asistencias){
                $esquema->asistencias()->update(['esquema_id' => null]);
            }

            if($esquema->ausentismos){
                $esquema->ausentismos()->update(['esquema_id' => null]);
            }

            if($esquema->viaticos){
                $esquema->viaticos()->update(['esquema_id' => null]);
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

    public function scopeBeneficio($query, $array)
    {
        if ($array) {
            return $query->whereIn('active', $array);
        }
    }

    public function scopeTipoIngreso($query, $array)
    {
        if ($array) {
            return $query->whereIn('tipo_ingreso', $array);
        }
    }

    public function scopeFechaAlejamiento($query, $array)
    {
        if ($array) {
            return $query->whereIn('fecha_alejamiento', $array);
        }
    }

    public function scopeReemplazo($query, $array)
    {
        if ($array) {
            return $query->whereIn('es_remplazo', $array);
        }
    }

    public function scopeTurnoAsignaciones($query, $array)
    {
        if ($array) {
            return $query->whereIn('turno_asignacion', $array);
        }
    }

    public function scopeEsTurnante($query, $array)
    {
        if ($array) {
            return $query->whereIn('es_turnante', $array);
        }
    }

    public function scopeLeyContrato($query, $leyes)
    {
        if ($leyes)
            return $query->whereHas('contratos', function ($q) use ($leyes) {
                $q->whereIn('ley_id', $leyes);
            });
    }

    public function scopeUnidadContrato($query, $unidades, $equals)
    {
        if ($unidades)
            return $query->whereHas('contratos', function ($q) use ($unidades, $equals) {
                if ($equals) {
                    $q->whereIn('unidad_id', $unidades);
                } else {
                    $q->whereNotIn('unidad_id', $unidades);
                }
            });
    }

    public function scopeHoraContrato($query, $horas)
    {
        if ($horas)
            return $query->whereHas('contratos', function ($q) use ($horas) {
                $q->whereIn('hora_id', $horas);
            });
    }

    public function scopeTipoAusentismo($query, $horas)
    {
        if ($horas)
            return $query->whereHas('ausentismos', function ($q) use ($horas) {
                $q->whereIn('tipo_ausentismo_id', $horas);
            });
    }

    public function scopeAjustesEnRecarga($query, $ajustes, $id_recarga)
    {
        if ($ajustes)
            return $query->where(function ($q) use ($ajustes, $id_recarga) {
                $q->whereHas('funcionario.reajustes', function ($query) use ($ajustes, $id_recarga) {
                    $query->where('recarga_id', $id_recarga)->whereIn('tipo_reajuste', $ajustes);
                });
            });
    }

    public function scopeAdvertencias($query, $value, $recarga)
    {
        if ($value) {
            if (in_array('advertencias', $value)) {
                return $query->where('fecha_alejamiento', true)
                    ->orWhere('total_dias_cancelar', '<=', 0)
                    ->orWhere('monto_total_cancelar', '>', $recarga->monto_estimado)
                    /* ->orWhereColumn('total_dias_cancelar', '>', 'calculo_contrato') */
                    ->orWhere(function ($q) use ($recarga) {
                        $q->where('monto_total_cancelar', '>', $recarga->monto_estimado)
                            ->where(function ($query) {
                                $query->where('ajustes_dias_n_registros', '<=', 0)
                                    ->where('ajustes_monto_n_registros', '<=', 0);
                            });
                    });
            } else if (in_array('errores', $value)) {
                return $query->where(function ($q) {
                    $q->where('active', false)
                        ->where(function ($query) {
                            $query->where('contrato_n_registros', '>', 0)
                                ->orWhere('viaticos_n_registros', '>', 0)
                                ->orWhere(function ($q) {
                                    $q->where('grupo_uno_n_registros', '>', 0)
                                        ->orWhere('grupo_dos_n_registros', '>', 0)
                                        ->orWhere('grupo_tres_n_registros', '>', 0);
                                });
                        });
                });
            }
        }
    }
}
