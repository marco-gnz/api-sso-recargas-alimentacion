<?php

namespace App\Http\Resources;

use App\Models\Reajuste;
use Carbon\Carbon;
use Illuminate\Http\Resources\Json\JsonResource;

class FuncionariosResumenResource extends JsonResource
{

    public function diasCancelar($total_dias_ausentismo, $recarga, $turno, $dias_libres, $total_reajustes_days, $total_dias_contrato, $total_dias_contrato_habiles, $count_feriados_in_periodo, $total_dias_viaticos)
    {
        $total_dias_cancelar = 0;

        if ($total_dias_contrato < $recarga->total_dias_mes) {

            $total_dias_cancelar = ($total_dias_contrato_habiles - $total_dias_ausentismo - $count_feriados_in_periodo - $total_dias_viaticos);

            /* if (!$turno) {
                $total_dias_cancelar = ($total_dias_contrato - $total_dias_ausentismo);
            } else if ($turno) {
                $total_dias_cancelar = ($total_dias_contrato - $dias_libres);
            } */
        } else {
            if (!$turno) {
                $total_dias_cancelar = ($recarga->total_dias_laborales_beneficio - $total_dias_ausentismo - $count_feriados_in_periodo - $total_dias_viaticos);
            } else if ($turno) {
                $total_dias_cancelar = ($recarga->total_dias_mes_beneficio - $dias_libres - $total_dias_viaticos);
            }
        }

        /* if (!$turno) {
            $total_dias_cancelar = ($recarga->total_dias_habiles - $total_dias_ausentismo);
        } else if ($turno) {
            $total_dias_cancelar = ($recarga->total_dias_mes - $dias_libres);
        }

        if ($total_dias_cancelar < 0) {
            $total_dias_cancelar = 0;
        }

        $total_dias_cancelar = ($total_dias_cancelar + $total_reajustes_days); */

        $total_dias_cancelar = ($total_dias_cancelar + $total_reajustes_days);

        if ($total_dias_cancelar < 0) {
            $total_dias_cancelar = 0;
        }

        return $total_dias_cancelar;
    }

    public function totalGrupos($funcionario)
    {
        $total_grupo_uno  = 0;
        $total_grupo_dos  = 0;
        $total_grupo_tres = 0;

        $get_total_grupo_uno  = $funcionario->ausentismos()->where('recarga_id', $this->recarga->id)->where('grupo_id', 1)->get(); //total_dias_ausentismo_periodo
        $get_total_grupo_dos  = $funcionario->ausentismos()->where('recarga_id', $this->recarga->id)->where('grupo_id', 2)->get();
        $get_total_grupo_tres = $funcionario->ausentismos()->where('recarga_id', $this->recarga->id)->where('grupo_id', 3)->where('tiene_descuento', true)->get();

        $total_grupo_uno     += $get_total_grupo_uno->sum('total_dias_ausentismo_periodo');
        $total_grupo_dos     += $get_total_grupo_dos->sum('total_dias_ausentismo_periodo');
        $total_grupo_tres    += $get_total_grupo_tres->sum('total_dias_ausentismo_periodo');

        $ausentismos_all_grupo_uno  = [];
        $ausentismos_all_grupo_dos  = [];
        $ausentismos_all_grupo_tres = [];

        $data_1 = [];
        $data_2 = [];
        $data_3 = [];

        $reglas_recarga = $this->recarga->reglas()->get()->unique('tipo_ausentismo_id');

        foreach ($reglas_recarga as $regla) {
            if ($regla->grupo_id === 1) {
                $data_1 = [
                    'nombre'    => $regla->tipoAusentismo->nombre,
                    'sigla'     => strtolower($regla->tipoAusentismo->sigla),
                    'total'     => $funcionario->ausentismos()->where('recarga_id', $this->recarga->id)->where('grupo_id', 1)->where('tipo_ausentismo_id', $regla->tipoAusentismo->id)->sum('total_dias_ausentismo_periodo')
                ];
                array_push($ausentismos_all_grupo_uno, $data_1);
            } else if ($regla->grupo_id === 2) {
                $data_2 = [
                    'nombre'    => $regla->tipoAusentismo->nombre,
                    'sigla'     => strtolower($regla->tipoAusentismo->sigla),
                    'total'     => $funcionario->ausentismos()->where('recarga_id', $this->recarga->id)->where('grupo_id', 2)->where('tipo_ausentismo_id', $regla->tipoAusentismo->id)->sum('total_dias_ausentismo_periodo')
                ];
                array_push($ausentismos_all_grupo_dos, $data_2);
            } else {
                $data_3 = [
                    'nombre'    => $regla->tipoAusentismo->nombre,
                    'sigla'     => strtolower($regla->tipoAusentismo->sigla),
                    'total'     => $funcionario->ausentismos()->where('recarga_id', $this->recarga->id)->where('grupo_id', 3)->where('tipo_ausentismo_id', $regla->tipoAusentismo->id)->where('tiene_descuento', true)->sum('total_dias_ausentismo_periodo')
                ];
                array_push($ausentismos_all_grupo_tres, $data_3);
            }
        }

        $data = [
            [
                'nombre_grupo'          => '1',
                'total_ausentismos'     => $total_grupo_uno,
                'tipos_ausentismos'     => $ausentismos_all_grupo_uno,
                'total_registros'       => $get_total_grupo_uno->count()
            ],
            [
                'nombre_grupo'          => '2',
                'total_ausentismos'     => $total_grupo_dos,
                'tipos_ausentismos'     => $ausentismos_all_grupo_dos,
                'total_registros'       => $get_total_grupo_dos->count()
            ],
            [
                'nombre_grupo'          => '3',
                'total_ausentismos'     => $total_grupo_tres,
                'tipos_ausentismos'     => $ausentismos_all_grupo_tres,
                'total_registros'       => $funcionario->ausentismos()->where('recarga_id', $this->recarga->id)->where('grupo_id', 3)->whereIn('tiene_descuento', [true, false])->count()
            ],
        ];

        return $data;
    }

    public function esTurnante($funcionario, $recarga)
    {
        $turnante = false;

        $turno      = $funcionario->turnos()->where('recarga_id', $recarga->id)->where('es_turnante', true)->first();
        $asistencia = $funcionario->asistencias()->where('recarga_id', $recarga->id)->first();

        if (($turno) && ($turno->es_turnante && $asistencia)) {
            $turnante = true;
        } else if ($asistencia && !$turno) {
            $turnante = true;
        } else if ($turno && !$asistencia) {
            $turnante = null;
        }

        return $turnante;
    }

    public function toArray($request)
    {
        $count_feriados_in_periodo = 0;
        $array = [];
        $date = Carbon::now();
        $es_turnante                 = $this->esTurnante($this, $this->recarga);
        $turno                       = $this->turnos()->where('recarga_id', $this->recarga->id)->first();
        $total_dias_ausentismo       = $this->ausentismos()->where('recarga_id', $this->recarga->id)->whereIn('grupo_id', [1, 2, 3])->sum('total_dias_ausentismo_periodo');
        $dias_libres                 = $this->asistencias()->where('recarga_id', $this->recarga->id)->where('tipo_asistencia_turno_id', 3)->count();
        $total_reajustes_days        = $this->reajustes()->where('tipo_reajuste', 0)->where('last_status', 1)->sum('dias');
        $total_reajustes_count       = $this->reajustes()->where('tipo_reajuste', 0)->where('recarga_id', $this->recarga->id)->count();
        $total_reajustes_monto       = $this->reajustes()->where('tipo_reajuste', 1)->where('last_status', 1)->sum('monto_ajuste');
        $total_reajustes_monto_count = $this->reajustes()->where('tipo_reajuste', 1)->where('recarga_id', $this->recarga->id)->count();
        $total_contratos             = $this->contratos()->where('recarga_id', $this->recarga->id)->get();
        foreach ($total_contratos as $value) {
            $count_feriados_in_periodo   += $this->recarga->feriados->where('active', true)->whereBetween('fecha', [$value->fecha_inicio_periodo, $value->fecha_termino_periodo])->count();
        }
        $total_dias_contrato         = $this->contratos()->where('recarga_id', $this->recarga->id)->sum('total_dias_contrato_periodo');
        $total_dias_contrato_habiles = $this->contratos()->where('recarga_id', $this->recarga->id)->sum('total_dias_habiles_contrato_periodo');
        $total_dias_viaticos         = $this->viaticos()->where('recarga_id', $this->recarga->id)->where('valor_viatico', '>', 0)->sum('total_dias');

        $total_dias_cancelar         = $this->diasCancelar($total_dias_ausentismo, $this->recarga, $es_turnante, $dias_libres, $total_reajustes_days, $total_dias_contrato, $total_dias_contrato_habiles, $count_feriados_in_periodo, $total_dias_viaticos);

        return [
            'id'                            => $this->id,
            'uuid'                          => $this->uuid,
            'beneficio'                     => $this->recargas()->first()->pivot->beneficio ? true : false,
            'rut_completo'                  => $this->rut_completo,
            'apellidos'                     => $this->apellidos,
            'nombre_completo'               => $this->nombre_completo,
            'turno'                         => $es_turnante,
            'tipo_pago'                     => $turno != null ? ($turno->proceso ? $turno->proceso->nombre : null) : null,
            'grupo_uno'                     => $this->ausentismos()->where('recarga_id', $this->recarga->id)->where('grupo_id', 1)->sum('total_dias_ausentismo_periodo'),
            'grupo_dos'                     => $this->ausentismos()->where('recarga_id', $this->recarga->id)->where('grupo_id', 2)->sum('total_dias_ausentismo_periodo'),
            'grupo_tres'                    => $this->ausentismos()->where('recarga_id', $this->recarga->id)->where('grupo_id', 3)->where('tiene_descuento', true)->sum('total_dias_ausentismo_periodo'),
            'total_grupos'                  => $this->totalGrupos($this),
            'dias_cancelar'                 => $total_dias_cancelar,
            'total_reajustes'               => $total_reajustes_days,
            'total_reajustes_count'         => $total_reajustes_count,
            'total_reajustes_monto'         => "$" . number_format($total_reajustes_monto, 0, ",", "."),
            'total_reajustes_monto_count'   => $total_reajustes_monto_count,
            'total_cancelar'                => ($this->recarga->monto_dia * $total_dias_cancelar) + $total_reajustes_monto,
            'dias_libres'                   => $dias_libres,
            'total_dias_contrato'           => round($total_dias_contrato_habiles, 0)
        ];
    }
}
