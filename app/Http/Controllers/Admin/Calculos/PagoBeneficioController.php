<?php

namespace App\Http\Controllers\Admin\Calculos;

use App\Http\Controllers\Controller;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;

class PagoBeneficioController extends Controller
{

    public function returnFuncionario($funcionario, $recarga)
    {
        $es_turnante                                = $this->esTurnante($funcionario);
        $total_dias_contrato                        = $this->totalDiasContratoDeCorrido($funcionario);
        $total_contrato_condition                   = $this->totalDiasContratoCondition($es_turnante, $funcionario, $recarga);

        $contratos_total                            = (object) ['total_dias_contrato' => (int)$total_dias_contrato, 'total_dias_habiles_contrato' => (int)$total_contrato_condition, 'tiene_dias' => $total_dias_contrato > 0 || $total_contrato_condition > 0 ? true : false];

        $total_grupo_uno                            = $this->totalGrupo($es_turnante, $funcionario, 1, $recarga);
        $total_grupo_dos                            = $this->totalGrupo($es_turnante, $funcionario, 2, $recarga);
        $total_grupo_tres                           = $this->totalGrupo($es_turnante, $funcionario, 3, $recarga);

        $dias_asistencia                           = $this->countDiasAsistencia($funcionario);

        $data_g_1 = (object) [
            'n_registros'      => $total_grupo_uno->n_registros,
            'total_dias'       => $total_grupo_uno->total_dias,
        ];
        $data_g_2 = (object) [
            'n_registros'      => $total_grupo_dos->n_registros,
            'total_dias'       => $total_grupo_dos->total_dias,
        ];
        $data_g_3 = (object) [
            'n_registros'      => $total_grupo_tres->n_registros,
            'total_dias'       => $total_grupo_tres->total_dias,
        ];

        $total_ausentismos                          = (object) [
            'total_dias_grupo_uno'                  => $data_g_1,
            'total_dias_grupo_dos'                  => $data_g_2,
            'total_dias_grupo_tres'                 => $data_g_3,
            'total_dias_ausentismos'                => ($data_g_1->total_dias + $data_g_2->total_dias + $data_g_3->total_dias)
        ];
        $total_pago_normal                          = $this->totalDiasCancelar($recarga, $es_turnante, $total_dias_contrato, $total_contrato_condition, $total_ausentismos, $dias_asistencia);
        $total_viaticos                             = $this->totalDiasViaticos($es_turnante, $funcionario, $recarga);
        $total_ajuste_de_dias                       = $this->totalAjustesDias($funcionario, $recarga);
        $total_ajustes_de_montos                    = $this->totalAjustesMontos($funcionario);


        $total_dias_cancelar                        = $total_pago_normal->total_dias_pagar - $total_viaticos->total_dias + $total_ajuste_de_dias->total_dias;
        $total_dias_cancelar                        = $total_dias_cancelar > 0 ? $total_dias_cancelar : 0;
        $monto_total_cancelar_dias                  = $total_dias_cancelar * $recarga->monto_dia;

        $monto_total_cancelar                       = ($monto_total_cancelar_dias + $total_ajustes_de_montos->total_monto);
        $monto_total_cancelar_format                = "$" . number_format($monto_total_cancelar, 0, ",", ".");

        $total                                      = (object) [
            'total_dias_cancelar'                   => $total_dias_cancelar,
            'monto_total_cancelar'                  => $monto_total_cancelar,
            'monto_total_cancelar_format'           => $monto_total_cancelar_format
        ];

        $count_feriados_beneficio           = $recarga->feriados()->where('active', true)->where('anio', $recarga->anio_beneficio)->where('mes', $recarga->mes_beneficio)->count();
        $total_dias_habiles_beneficio       = ($recarga->total_dias_laborales_beneficio - $count_feriados_beneficio);
        $monto_estimado                     = $total_dias_habiles_beneficio * $recarga->monto_dia;

        $recarga_active = $funcionario->recargas->where('id', $recarga->id)->where('active', true)->first();
        $status         = $recarga_active->pivot->beneficio ? true : false;
        $error_uno      = $this->errorUno($status, $total_ausentismos, $contratos_total->tiene_dias);
        $error_dos      = $this->errorDos();

        $adv_1          = $this->advertenciaUno($funcionario->contratos);
        $adv_2          = $this->advertenciaDos($total->total_dias_cancelar);
        $adv_3          = $this->advertenciaTres($total->monto_total_cancelar, $monto_estimado);
        $adv_4          = $this->advertenciaCuatro($total->monto_total_cancelar, $monto_estimado, $total_ajuste_de_dias->total_monto, $total_ajustes_de_montos->total_monto);
        $adv_5          = $this->advertenciaCinco($es_turnante, $contratos_total, $total->total_dias_cancelar);

        $errores        = $this->errores($error_uno, $error_dos);
        $advertencias   = $this->advertencias($adv_1, $adv_2, $adv_3, $adv_4, $adv_5);


        $funcionario->{'recarga_codigo'}                = $recarga->codigo;
        $funcionario->{'beneficio'}                     = $status;
        $funcionario->{'es_turnante'}                   = $es_turnante;
        $funcionario->{'contratos_total'}               = $contratos_total;
        $funcionario->{'total_ausentismos'}             = $total_ausentismos;
        $funcionario->{'dias_asistencia'}               = $dias_asistencia;
        $funcionario->{'total_pago_normal'}             = $total_pago_normal;
        $funcionario->{'total_viaticos'}                = $total_viaticos;
        $funcionario->{'total_ajuste_de_dias'}          = $total_ajuste_de_dias;
        $funcionario->{'total_ajustes_de_montos'}       = $total_ajustes_de_montos;
        $funcionario->{'total'}                         = $total;
        $funcionario->{'grupos_de_ausentismos'}         = $this->totalGrupos($funcionario, $recarga);
        $funcionario->{'errores'}                       = $errores;
        $funcionario->{'advertencias'}                  = $advertencias;

        return $funcionario;
    }

    private function errores($error_uno, $error_dos)
    {
        $errores = [];
        if ($error_uno) {
            $new_error = (object) [
                'code'          => 1,
                'message'       => $response = User::ERROR_NOM[1],
            ];

            array_push($errores, $new_error);
        }
        return $errores;
    }

    private function advertencias($adv_1, $adv_2, $adv_3, $adv_4, $adv_5)
    {
        $advertencias = [];
        if ($adv_1) {
            $new_advertencia = (object) [
                'code'          => 1,
                'message'       => User::ADVERTENCIA_NOM[1],
            ];

            array_push($advertencias, $new_advertencia);
        }

        if ($adv_2) {
            $new_advertencia = (object) [
                'code'          => 2,
                'message'       => User::ADVERTENCIA_NOM[2],
            ];

            array_push($advertencias, $new_advertencia);
        }

        if ($adv_3) {
            $new_advertencia = (object) [
                'code'          => 3,
                'message'       => User::ADVERTENCIA_NOM[3],
            ];

            array_push($advertencias, $new_advertencia);
        }

        if ($adv_4) {
            $new_advertencia = (object) [
                'code'          => 4,
                'message'       => User::ADVERTENCIA_NOM[4],
            ];

            array_push($advertencias, $new_advertencia);
        }

        if ($adv_5) {
            $new_advertencia = (object) [
                'code'          => 5,
                'message'       => $response = User::ADVERTENCIA_NOM[5],
            ];

            array_push($advertencias, $new_advertencia);
        }
        return $advertencias;
    }

    private function errorUno($status, $total_ausentismos, $tiene_contrato)
    {
        $is_error = false;
        $total_registros_ausentismo = $total_ausentismos->total_dias_grupo_uno->n_registros + $total_ausentismos->total_dias_grupo_dos->n_registros + $total_ausentismos->total_dias_grupo_tres->n_registros;

        if ((!$status) && ($total_registros_ausentismo > 0 || $tiene_contrato)) {
            $is_error = true;
        }
        return $is_error;
    }

    private function errorDos()
    {
        $is_error = false;

        return $is_error;
    }

    private function advertenciaUno($contratos)
    {
        $is_advertencia = false;

        $total = $contratos->where('alejamiento', true)->count();

        if ($total) {
            $is_advertencia = true;
        }
        return $is_advertencia;
    }

    private function advertenciaDos($total_dias_cancelar)
    {
        $is_advertencia = false;

        if ($total_dias_cancelar <= 0) {
            $is_advertencia = true;
        }
        return $is_advertencia;
    }

    private function advertenciaTres($monto_total_cancelar, $monto_estimado)
    {
        $is_advertencia = false;

        if ($monto_total_cancelar > $monto_estimado) {
            $is_advertencia = true;
        }
        return $is_advertencia;
    }

    private function advertenciaCuatro($monto_total_cancelar, $monto_estimado, $total_ajuste_de_dias, $total_ajustes_de_montos)
    {
        $is_advertencia = false;

        if (($monto_total_cancelar > $monto_estimado) && ($total_ajuste_de_dias <= 0 && $total_ajustes_de_montos <= 0)) {
            $is_advertencia = true;
        }
        return $is_advertencia;
    }

    private function advertenciaCinco($es_turnante, $contratos_total, $total_dias_cancelar)
    {
        $is_advertencia = false;

        if ($es_turnante) {
            if ($total_dias_cancelar > $contratos_total->total_dias_contrato) {
                $is_advertencia = true;
            }
        } else {
            if ($total_dias_cancelar > $contratos_total->total_dias_habiles_contrato) {
                $is_advertencia = true;
            }
        }
        return $is_advertencia;
    }

    private function totalDiasContratoDeCorrido($funcionario)
    {
        $total = 0;
        $total = $funcionario->contratos()->sum('total_dias_contrato_periodo');
        return $total;
    }

    private function totalDiasContratoHabiles($funcionario, $recarga)
    {
        $total          = 0;
        $contratos      = $funcionario->contratos()->get();
        $feriados_count = 0;

        if (count($contratos) > 0) {
            foreach ($contratos as $contrato) {
                $feriados_count  += $recarga->feriados()->where('active', true)->whereBetween('fecha', [$contrato->fecha_inicio_periodo, $contrato->fecha_termino_periodo])->count();
            }
        }

        $total = ($contratos->sum('total_dias_habiles_contrato_periodo') - $feriados_count);

        return $total;
    }

    private function turnosFuncionarioInRecarga($funcionario)
    {
        $turnos = $funcionario->turnos()
            ->where(function ($q) {
                $q->where('asignacion_tercer_turno', '>', 0)
                    ->orWhere('asignacion_cuarto_turno', '>', 0);
            })
            ->get();

        return $turnos;
    }

    private function totalGrupos($funcionario, $recarga)
    {
        $ausentismos_all_grupo_uno  = [];
        $ausentismos_all_grupo_dos  = [];
        $ausentismos_all_grupo_tres = [];

        $reglas_recarga             = $recarga->reglas()->get()->unique('tipo_ausentismo_id');

        foreach ($reglas_recarga as $regla) {
            if ($regla->grupo_id === 1) {
                $data_1 = [
                    'nombre'        => $regla->tipoAusentismo->nombre,
                    'sigla'         => strtolower($regla->tipoAusentismo->sigla),
                    'total_dias'    => $funcionario->ausentismos()->where('grupo_id', 1)->where('tipo_ausentismo_id', $regla->tipoAusentismo->id)->sum('total_dias_ausentismo_periodo')
                ];
                array_push($ausentismos_all_grupo_uno, $data_1);
            } else if ($regla->grupo_id === 2) {
                $data_2 = [
                    'nombre'        => $regla->tipoAusentismo->nombre,
                    'sigla'         => strtolower($regla->tipoAusentismo->sigla),
                    'total_dias'    => $funcionario->ausentismos()->where('grupo_id', 2)->where('tipo_ausentismo_id', $regla->tipoAusentismo->id)->sum('total_dias_ausentismo_periodo')
                ];
                array_push($ausentismos_all_grupo_dos, $data_2);
            } else {
                $data_3 = [
                    'nombre'        => $regla->tipoAusentismo->nombre,
                    'sigla'         => strtolower($regla->tipoAusentismo->sigla),
                    'total_dias'    => $funcionario->ausentismos()->where('grupo_id', 3)->where('tipo_ausentismo_id', $regla->tipoAusentismo->id)->sum('total_dias_ausentismo_periodo')
                ];
                array_push($ausentismos_all_grupo_tres, $data_3);
            }
        }
        $grupo_uno = (object) [
            'tipos_ausentismos'      => $ausentismos_all_grupo_uno,
        ];
        $grupo_dos = (object) [
            'tipos_ausentismos'      => $ausentismos_all_grupo_dos,
        ];
        $grupo_tres = (object) [
            'tipos_ausentismos'      => $ausentismos_all_grupo_tres,
        ];

        $data = (object) [
            'grupo_uno'      => $grupo_uno,
            'grupo_dos'      => $grupo_dos,
            'grupo_tres'     => $grupo_tres,
        ];

        return $data;
    }

    private function asistenciasFuncionarioInRecarga($funcionario)
    {
        $asistencias = $funcionario->asistencias()->where('tipo_asistencia_turno_id', 3)->get();

        return $asistencias;
    }

    private function esTurnante($funcionario)
    {
        $es_turnante = false;

        $turnos             = $this->turnosFuncionarioInRecarga($funcionario);
        $total_turnos       = count($turnos);

        $asistencias        = $this->asistenciasFuncionarioInRecarga($funcionario);
        $total_asistencias  = count($asistencias);

        $total_dias_contrato_periodo = $this->totalDiasContratoDeCorrido($funcionario);

        if (($total_turnos > 0 && $total_asistencias > 0 && $total_dias_contrato_periodo > 0) || ($total_asistencias > 0 && $total_dias_contrato_periodo > 0)) {
            $es_turnante = true;
        } else if ($total_turnos <= 0 && $total_asistencias > 0 && $total_dias_contrato_periodo > 0) {
            $es_turnante = null;
        } else if ($total_asistencias <= 0 && $total_turnos > 0 && $total_dias_contrato_periodo > 0) {
            $es_turnante = null;
        } else if ($total_dias_contrato_periodo <= 0 && $total_turnos > 0 && $total_asistencias > 0) {
            $es_turnante = null;
        }

        return $es_turnante;
    }

    private function ausentismosGrupo($funcionario, $id_grupo)
    {
        $total = $funcionario->ausentismos()->where('grupo_id', $id_grupo)->get();
        return $total;
    }

    private function countDiasLibres($funcionario)
    {
        try {
            $total  = count($this->asistenciasFuncionarioInRecarga($funcionario));

            return $total;
        } catch (\Exception $error) {
            return $error->getMessage();
        }
    }

    private function countDiasAsistencia($funcionario)
    {
        try {
            $total_l = 0;
            $total_n = 0;
            $total_x = 0;

            if (count($funcionario->asistencias)) {
                foreach ($funcionario->asistencias as $asistencia) {
                    $fecha      = $asistencia->fecha;
                    if (($asistencia->tipoAsistenciaTurno) && ($asistencia->tipoAsistenciaTurno->nombre === 'L')) {
                        $total_l += $funcionario->contratos()
                            ->where(function ($query) use ($fecha) {
                                $query->where('fecha_inicio_periodo', '<=', $fecha)
                                    ->where('fecha_termino_periodo', '>=', $fecha);
                            })
                            ->count();
                    } else if (($asistencia->tipoAsistenciaTurno) && ($asistencia->tipoAsistenciaTurno->nombre === 'N')) {
                        $total_n += $funcionario->contratos()
                            ->where(function ($query) use ($fecha) {
                                $query->where('fecha_inicio_periodo', '<=', $fecha)
                                    ->where('fecha_termino_periodo', '>=', $fecha);
                            })
                            ->count();
                    } else if (($asistencia->tipoAsistenciaTurno) && ($asistencia->tipoAsistenciaTurno->nombre === 'X')) {
                        $total_x += $funcionario->contratos()
                            ->where(function ($query) use ($fecha) {
                                $query->where('fecha_inicio_periodo', '<=', $fecha)
                                    ->where('fecha_termino_periodo', '>=', $fecha);
                            })
                            ->count();
                    }
                }
            }
            $data = (object) [
                'total_dx'      => $total_x,
                'total_dl'      => $total_l,
                'total_dn'      => $total_n,
                'total_turno'   => ($total_l + $total_n)
            ];

            return $data;
        } catch (\Exception $error) {
            return $error->getMessage();
        }
    }

    private function totalGrupo($es_turnante, $funcionario, $id_grupo, $recarga)
    {
        $total          = 0;
        $feriados_count = 0;
        $ausentismos    = $this->ausentismosGrupo($funcionario, $id_grupo);

        if (count($ausentismos) > 0) {
            foreach ($ausentismos as $ausentismo) {
                $total_dias_habiles_ausentismo_periodo = 0;
                $grupo_id = $ausentismo->grupo_id ? (int)$ausentismo->grupo_id : null;
                switch ($grupo_id) {
                    case 2:
                        $reglas = $ausentismo->regla->whereHas('meridianos', function ($query) use ($ausentismo) {
                            $query->where('meridiano_regla.meridiano_id', $ausentismo->meridiano_id)->where('meridiano_regla.active', true);
                        })->count();

                        if ($reglas > 0) {
                            if ($es_turnante) {
                                $total_dias_habiles_ausentismo_periodo = $ausentismo->total_dias_ausentismo_periodo;
                            } else {
                                $total_dias_habiles_ausentismo_periodo = $ausentismo->total_dias_habiles_ausentismo_periodo;
                            }
                        }
                        break;
                    case 3:
                        $hora_inicio    = Carbon::parse($ausentismo->hora_inicio);
                        $hora_termino   = Carbon::parse($ausentismo->hora_termino);
                        $fecha_inicio   = Carbon::parse($ausentismo->fecha_inicio_periodo);
                        $fecha_termino  = Carbon::parse($ausentismo->fecha_termino_periodo);


                        $hora_inicio_regla    = Carbon::parse($ausentismo->regla->hora_inicio);
                        $hora_termino_regla   = Carbon::parse($ausentismo->regla->hora_termino);

                        $concat_inicio        = "{$fecha_inicio->format('Y-m-d')} {$hora_inicio->format('H:i:s')}";
                        $concat_termino       = "{$fecha_termino->format('Y-m-d')} {$hora_termino->format('H:i:s')}";
                        $concat_inicio_regla  = "{$fecha_inicio->format('Y-m-d')} {$hora_inicio_regla->format('H:i:s')}";
                        $concat_termino_regla = "{$fecha_inicio->format('Y-m-d')} {$hora_termino_regla->format('H:i:s')}";

                        $hora_inicio_archivo   = Carbon::parse($concat_inicio)->timestamp;
                        $hora_termino_archivo  = Carbon::parse($concat_termino)->timestamp;
                        $fecha_inicio_regla    = Carbon::parse($concat_inicio_regla)->timestamp;
                        $fecha_termino_regla   = Carbon::parse($concat_termino_regla)->timestamp;

                        if (($hora_inicio_archivo < $fecha_inicio_regla && $hora_termino_archivo > $fecha_inicio_regla) || ($hora_inicio_archivo < $fecha_termino_regla && $hora_termino_archivo > $fecha_termino_regla) || ($hora_inicio_archivo >= $fecha_inicio_regla && $hora_termino_archivo <= $fecha_termino_regla)) {
                            if ($es_turnante) {
                                $total_dias_habiles_ausentismo_periodo = $ausentismo->total_dias_ausentismo_periodo;
                            } else {
                                $total_dias_habiles_ausentismo_periodo = $ausentismo->total_dias_habiles_ausentismo_periodo;
                            }
                        }
                        break;
                    default:
                        $total_dias_habiles_ausentismo_periodo = $ausentismo->total_dias_habiles_ausentismo_periodo;
                        break;
                }
                if (!$es_turnante) {
                    $feriados_count += $recarga->feriados()->where('active', true)->whereBetween('fecha', [$ausentismo->fecha_inicio_periodo, $ausentismo->fecha_termino_periodo])->count();
                    $total          += $total_dias_habiles_ausentismo_periodo - $feriados_count;
                } else {
                    $total          += $total_dias_habiles_ausentismo_periodo;
                }
            }
        }

        $data = (object) [
            'n_registros'      => count($ausentismos),
            'total_dias'       => $total,
        ];
        return $data;
    }

    private function totalDiasContratoCondition($es_turnante, $funcionario, $recarga)
    {
        $total = 0;
        if ($es_turnante) {
            $total = $this->totalDiasContratoDeCorrido($funcionario);
        } else {
            $total = $this->totalDiasContratoHabiles($funcionario, $recarga);
        }
        return $total;
    }

    private function totalDiasCancelar($recarga, $es_turnante, $total_dias_contrato, $total_contrato_condition, $total_ausentismos, $dias_asistencia)
    {
        $total_dias = 0;
        if ($total_dias_contrato < $recarga->total_dias_mes_beneficio) {
            if ($es_turnante) {
                $total_dias = ($dias_asistencia->total_turno - $total_ausentismos->total_dias_ausentismos);
            } else {
                $total_dias = ($total_contrato_condition - $total_ausentismos->total_dias_ausentismos);
            }
        } else {
            if ($es_turnante) {
                $total_dias = ($dias_asistencia->total_turno - $total_ausentismos->total_dias_ausentismos);
            } else {
                $total_dias = ($total_contrato_condition - $total_ausentismos->total_dias_ausentismos);
            }
        }
        $total_monto = ($total_dias * $recarga->monto_dia);
        $total_monto = (int)$total_monto;

        $data = (object) [
            'total_dias_pagar'      => $total_dias,
            'total_monto'           => $total_monto,
            'total_monto_format'    => "$" . number_format($total_monto, 0, ",", ".")
        ];

        return $data;
    }

    private function totalDiasViaticos($es_turnante, $funcionario, $recarga)
    {
        $n_registros    = 0;
        $total_dias     = 0;
        $viaticos       = $funcionario->viaticos()->get();
        $n_registros    = count($viaticos);
        if ($es_turnante) {
            $total_dias     = $viaticos->where('valor_viatico', '>', 0)->sum('total_dias_periodo');
        } else {
            $total_dias     = $viaticos->where('valor_viatico', '>', 0)->sum('total_dias_habiles_periodo');
        }
        $total_dias         = (int)$total_dias;
        $total_pago         = ($total_dias * $recarga->monto_dia);
        $total_pago         = (int)$total_pago;
        $data = (object) [
            'n_registros'               => $n_registros,
            'total_dias'                => $total_dias,
            'total_descuento'           => $total_pago,
            'total_descuento_format'    => "$" . number_format($total_pago, 0, ",", ".")
        ];
        return $data;
    }

    private function totalAjustesDias($funcionario, $recarga)
    {
        $ajustes        = $funcionario->reajustes()->where('tipo_reajuste', 0)->get();

        $n_registros    = count($ajustes);
        $total_dias     = $ajustes->where('last_status', 1)->sum('dias');

        $total_monto    = ($total_dias * $recarga->monto_dia);
        $total_monto    = (int)$total_monto;
        $data           = (object) [
            'n_registros'           => $n_registros,
            'total_dias'            => $total_dias,
            'total_monto'           => $total_monto,
            'total_monto_format'    => "$" . number_format($total_monto, 0, ",", ".")
        ];

        return $data;
    }

    private function totalAjustesMontos($funcionario)
    {
        $total_monto    = 0;
        $ajustes        = $funcionario->reajustes()->where('tipo_reajuste', 1)->get();

        $n_registros    = count($ajustes);
        $total_monto    = $ajustes->where('last_status', 1)->sum('monto_ajuste');

        $data = (object) [
            'n_registros'           => $n_registros,
            'total_monto'           => $total_monto,
            'total_monto_format'    => "$" . number_format($total_monto, 0, ",", ".")
        ];

        return $data;
    }
}
