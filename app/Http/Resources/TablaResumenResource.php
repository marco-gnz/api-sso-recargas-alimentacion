<?php

namespace App\Http\Resources;

use App\Models\Ausentismo;
use Illuminate\Http\Resources\Json\JsonResource;

class TablaResumenResource extends JsonResource
{
    public function totalDiasContratoDeCorrido($funcionario)
    {
        $total = 0;
        $total = $funcionario->contratos()->sum('total_dias_contrato_periodo');
        return $total;
    }

    public function totalDiasContratoHabiles($funcionario, $recarga)
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

    public function turnosFuncionarioInRecarga($funcionario)
    {
        $turnos = $funcionario->turnos()->get();

        return $turnos;
    }

    public function totalGrupos($funcionario, $recarga)
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
                    'total_dias'    => $funcionario->ausentismos()->where('grupo_id', 3)->where('tipo_ausentismo_id', $regla->tipoAusentismo->id)->where('tiene_descuento', true)->sum('total_dias_ausentismo_periodo')
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

    public function asistenciasFuncionarioInRecarga($funcionario)
    {
        $asistencias = $funcionario->asistencias()->where('tipo_asistencia_turno_id', 3)->get();

        return $asistencias;
    }

    public function esTurnante($funcionario)
    {
        $es_turnante = false;

        $turnos             = $this->turnosFuncionarioInRecarga($funcionario);
        $total_turnos       = count($turnos);

        $asistencias        = $this->asistenciasFuncionarioInRecarga($funcionario);
        $total_asistencias  = count($asistencias);

        $total_dias_contrato_periodo = $this->totalDiasContratoDeCorrido($funcionario);

        if ($total_turnos > 0 && $total_asistencias > 0 && $total_dias_contrato_periodo > 0) {
            $es_turnante = true;
        }

        return $es_turnante;
    }

    public function ausentismosGrupo($funcionario, $id_grupo)
    {
        $total = $funcionario->ausentismos()->where('grupo_id', $id_grupo)->get();
        return $total;
    }

    public function countDiasLibres($funcionario)
    {
        try {
            $total  = count($this->asistenciasFuncionarioInRecarga($funcionario));

            return $total;
        } catch (\Exception $error) {
            return $error->getMessage();
        }
    }

    public function countDiasAsistencia($funcionario)
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

    public function totalGrupo($es_turnante, $funcionario, $id_grupo)
    {
        $total          = 0;
        $ausentismos    = $this->ausentismosGrupo($funcionario, $id_grupo);
        if ($es_turnante) {
            $total = $ausentismos->sum('total_dias_ausentismo_periodo');
        } else {
            $total = $ausentismos->sum('total_dias_habiles_ausentismo_periodo');
        }

        $data = (object) [
            'n_registros'      => count($ausentismos),
            'total_dias'       => $total,
        ];
        return $data;
    }

    public function totalDiasContratoCondition($es_turnante, $funcionario, $recarga)
    {
        $total = 0;
        if ($es_turnante) {
            $total = $this->totalDiasContratoDeCorrido($funcionario);
        } else {
            $total = $this->totalDiasContratoHabiles($funcionario, $recarga);
        }
        return $total;
    }

    public function totalDiasCancelar($recarga, $es_turnante, $total_dias_contrato, $total_contrato_condition, $total_ausentismos, $dias_asistencia)
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

    public function totalDiasViaticos($es_turnante, $funcionario, $recarga)
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

    public function totalAjustesDias($funcionario, $recarga)
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

    public function totalAjustesMontos($funcionario)
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

    public function toArray($request)
    {

        $recarga                                    = $this->recargas->where('active', true)->first();
        $beneficio_en_recarga                       = $recarga->pivot->beneficio ? true : false;
        $es_turnante                                = $this->esTurnante($this);

        $total_dias_contrato                        = $this->totalDiasContratoDeCorrido($this);
        $total_contrato_condition                   = $this->totalDiasContratoCondition($es_turnante, $this, $recarga);

        $contratos                                  = (object) ['total_dias_contrato' => (int)$total_dias_contrato, 'total_dias_habiles_contrato' => (int)$total_contrato_condition];

        $total_grupo_uno                            = $this->totalGrupo($es_turnante, $this, 1);
        $total_grupo_dos                            = $this->totalGrupo($es_turnante, $this, 2);
        $total_grupo_tres                           = $this->totalGrupo($es_turnante, $this, 3);

        $dias_asistencia                           = $this->countDiasAsistencia($this);

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
        $total_viaticos                             = $this->totalDiasViaticos($es_turnante, $this, $recarga);
        $total_ajuste_de_dias                       = $this->totalAjustesDias($this, $recarga);
        $total_ajustes_de_montos                    = $this->totalAjustesMontos($this);


        $total_dias_cancelar                        = $total_pago_normal->total_dias_pagar - ($total_viaticos->total_dias + $total_ajuste_de_dias->total_dias);
        $monto_total_cancelar_dias                  = $total_dias_cancelar * $recarga->monto_dia;

        $monto_total_cancelar                       = ($monto_total_cancelar_dias + $total_ajustes_de_montos->total_monto);
        $monto_total_cancelar_format                = "$" . number_format($monto_total_cancelar, 0, ",", ".");

        $total                                      = (object) [
            'total_dias_cancelar'                   => $total_dias_cancelar,
            'monto_total_cancelar'                  => $monto_total_cancelar,
            'monto_total_cancelar_format'           => $monto_total_cancelar_format
        ];
        return [
            'id'                                            => $this->id,
            'uuid'                                          => $this->uuid,
            'recarga_codigo'                                => $recarga ? $recarga->codigo : null,
            'apellidos'                                     => $this->apellidos,
            'rut_completo'                                  => $this->rut_completo,
            'apellidos'                                     => $this->apellidos,
            'nombre_completo'                               => $this->nombre_completo,
            'beneficio'                                     => $beneficio_en_recarga,
            'es_turnante'                                   => $es_turnante,
            'contratos'                                     => $contratos,
            'total_ausentismos'                             => $total_ausentismos,
            'dias_asistencia'                               => $dias_asistencia,
            'total_pago_normal'                             => $total_pago_normal,
            'total_viaticos'                                => $total_viaticos,
            'total_ajuste_de_dias'                          => $total_ajuste_de_dias,
            'total_ajustes_de_montos'                       => $total_ajustes_de_montos,
            'total'                                         => $total,
            'grupos_de_ausentismos'                         => $this->totalGrupos($this, $recarga)
        ];
    }
}
