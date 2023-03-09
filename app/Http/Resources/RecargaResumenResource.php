<?php

namespace App\Http\Resources;

use Carbon\Carbon;
use Illuminate\Http\Resources\Json\JsonResource;

class RecargaResumenResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */

    public function totalAusentismosGrupos($recarga)
    {
        $ausentismos_all_grupo_uno    = [];
        $ausentismos_all_grupo_dos    = [];
        $ausentismos_all_grupo_tres   = [];
        $reglas_recarga               = $recarga->reglas()->get()->unique('tipo_ausentismo_id');

        foreach ($reglas_recarga as $regla) {
            if ($regla->grupo_id === 1) {
                $data_1 = (object) [
                    'id'      => $regla->tipoAusentismo->id,
                    'nombre'  => $regla->tipoAusentismo->nombre,
                    'sigla'   => strtolower($regla->tipoAusentismo->sigla)
                ];
                array_push($ausentismos_all_grupo_uno, $data_1);
            } else if ($regla->grupo_id === 2) {
                $data_2 = (object) [
                    'id'      => $regla->tipoAusentismo->id,
                    'nombre'  => $regla->tipoAusentismo->nombre,
                    'sigla'   => strtolower($regla->tipoAusentismo->sigla)
                ];
                array_push($ausentismos_all_grupo_dos, $data_2);
            }else if ($regla->grupo_id === 3) {
                $data_3 = (object) [
                    'id'      => $regla->tipoAusentismo->id,
                    'nombre'  => $regla->tipoAusentismo->nombre,
                    'sigla'   => strtolower($regla->tipoAusentismo->sigla)
                ];
                array_push($ausentismos_all_grupo_tres, $data_3);
            }
        }

        $data_grupo_uno = (object) [
            'nombre'                => '1',
            'total_ausentismos'     => $recarga->ausentismos()->where('grupo_id', 1)->count(),
            'data'                  => $ausentismos_all_grupo_uno
        ];

        $data_grupo_dos = (object) [
            'nombre'                => '2',
            'total_ausentismos'     => $recarga->ausentismos()->where('grupo_id', 2)->count(),
            'data'                  => $ausentismos_all_grupo_dos
        ];

        $data_grupo_tres = (object) [
            'nombre'                => '3',
            'total_ausentismos'     => $recarga->ausentismos()->where('grupo_id', 3)->count(),
            'data'                  => $ausentismos_all_grupo_tres
        ];

        $data = (object) [
            'grupo_uno'      => $data_grupo_uno,
            'grupo_dos'      => $data_grupo_dos,
            'grupo_tres'     => $data_grupo_tres,
        ];

        return $data;
    }

    public function toArray($request)
    {
        setlocale(LC_ALL, "es_ES");
        Carbon::setLocale('es');
        $tz                                 = 'America/Santiago';
        $count_feriados_beneficio           = $this->feriados()->where('active', true)->where('anio', $this->anio_beneficio)->where('mes', $this->mes_beneficio)->count();
        $total_dias_habiles_beneficio       = ($this->total_dias_laborales_beneficio - $count_feriados_beneficio);

        $count_feriados_calculo             = $this->feriados()->where('active', true)->where('anio', $this->anio_calculo)->where('mes', $this->mes_calculo)->count();
        $total_dias_habiles_calculo         = ($this->total_dias_laborales_calculo - $count_feriados_calculo);

        $monto_dia                          = $this->monto_dia != null ? number_format($this->monto_dia, 0, ",", ".") : null;
        $monto_estimado                     = $total_dias_habiles_beneficio * $this->monto_dia;

        return [
            'id'                            => $this->id,
            'codigo'                        => $this->codigo,
            'anio_beneficio'                => $this->anio_beneficio,
            'mes_beneficio'                 => Carbon::createFromDate($this->anio_beneficio, $this->mes_beneficio, '01', $tz)->formatLocalized('%B'),
            'anio_calculo'                  => $this->anio_calculo,
            'mes_calculo'                   => Carbon::createFromDate($this->anio_calculo, $this->mes_calculo, '01', $tz)->formatLocalized('%B'),
            'total_dias_mes_beneficio'      => $this->total_dias_mes_beneficio,
            'total_dias_habiles_beneficio'  => $total_dias_habiles_beneficio,
            'total_dias_mes_calculo'        => $this->total_dias_mes_calculo,
            'total_dias_habiles_calculo'    => $total_dias_habiles_calculo,
            'monto_dia'                     => $this->monto_dia ? "$" . $monto_dia : NULL,
            'value_monto_dia'               => $this->monto_dia,
            'monto_estimado_no_turnante'    => $monto_estimado,
            'monto_estimado_no_turnante_format'    => "$" . number_format($monto_estimado, 0, ",", "."),
            'active'                        => $this->active != true ? false : true,
            'n_funcionarios'                => 0,
            'n_funcionarios_vigentes'       => 0,
            'n_funcionarios_no_vigentes'    => 0,
            'total_pagado'                  => 0,
            'last_estado'                   => $this->seguimiento()->latest()->with('estado')->first(),
            'date_created_user'             => $this->date_created_user,
            'date_updated_user'             => $this->date_updated_user,
            'disabled_reglas'               => $this->reglas()->count() > 0 ? true : false,
            'users_count'                   => $this->users_count,
            'reajustes_count'               => $this->reajustes_count,
            'contratos_count'               => $this->contratos_count,
            'viaticos_count'                => $this->viaticos_count,
            'ausentismos_count'             => $this->ausentismos_count,
            'asignaciones_count'            => $this->asignaciones_count,
            'total_grupos'                  => $this->totalAusentismosGrupos($this),
            'monto_total'                   => 0,

            'ausentismos'                   => AusentismosResource::collection($this->ausentismos),
            'establecimiento'               => $this->establecimiento,
            'seguimiento'                   => $this->seguimiento()->with('estado', 'userBy')->orderBy('created_at', 'DESC')->get(),
            'reglas'                        => $this->reglas,
            'user_created_by'               => $this->userCreatedBy,
            'user_update_by'                => $this->userUpdateBy
        ];
    }
}
