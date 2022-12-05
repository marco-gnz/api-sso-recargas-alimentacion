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

    public function total($total_dias_cancelar = 0)
    {
        $total = 0;
        /* $total_dias_cancelar = 0; */
        $total_ausentismos = 0;

        /* $total_ausentismos = $this->ausentismos()->where('recarga_id', $this->id)->sum('total_dias_ausentismo_periodo'); */

        foreach ($this->users as $funcionario) {
            $total_ausentismos = $funcionario->ausentismos()->where('recarga_id', $this->id)->sum('total_dias_ausentismo_periodo');


            if (!$funcionario->turno) {
                if ($total_ausentismos > $this->total_dias_habiles) {
                    $total_dias_cancelar += ($total_ausentismos - $this->total_dias_habiles);
                } else {
                    $total_dias_cancelar += ($this->total_dias_habiles - $total_ausentismos);
                }
            } else {
                if ($total_ausentismos > $this->total_dias_mes) {
                    $total_dias_cancelar += ($total_ausentismos - $this->total_dias_mes);
                } else {
                    $total_dias_cancelar += ($this->total_dias_mes - $total_ausentismos);
                }
            }


            /* if ($total_dias_cancelar < 0) {
                $total_dias_cancelar += 0;
            } */
        }

        return $total_dias_cancelar;
    }

    public function totalGrupoUno($recarga)
    {
        $total_grupo = 0;
        $users = $recarga->users()->where('beneficio', true)->get();
        foreach ($users as $user) {
            $total_grupo += $user->ausentismos()->where('recarga_id', $recarga->id)->sum('total_dias_ausentismo_periodo');
        }
        return $total_grupo;
    }

    public function total_grupos($recarga)
    {
        $total_grupo_uno  = 0;
        $total_grupo_dos  = 0;
        $total_grupo_tres = 0;
        $total_grupos     = 0;

        $users = $recarga->users()->where('beneficio', true)->get();
        foreach ($users as $user) {
            $total_grupo_uno  += $user->ausentismos()->where('recarga_id', $recarga->id)->where('grupo_id', 1)->sum('total_dias_ausentismo_periodo');
            $total_grupo_dos  += $user->ausentismos()->where('recarga_id', $recarga->id)->where('grupo_id', 2)->sum('total_dias_ausentismo_periodo');
            $total_grupo_tres += $user->ausentismos()->where('recarga_id', $recarga->id)->where('grupo_id', 3)->sum('total_dias_ausentismo_periodo');

            $total_grupos = ($total_grupo_uno + $total_grupo_dos + $total_grupo_tres);
        }

        $ausentismos_all_grupo_uno  = [];
        $ausentismos_all_grupo_dos  = [];
        $ausentismos_all_grupo_tres = [];

        $data_1 = [];
        $data_2 = [];
        $data_3 = [];

        foreach ($recarga->reglas as $regla) {
            if ($regla->grupo_id === 1) {
                $total = $recarga->ausentismos()->where('recarga_id', $recarga->id)->where('grupo_id', 1)->where('tipo_ausentismo_id', $regla->tipoAusentismo->id)->sum('total_dias_ausentismo_periodo');
                $data_1 = [
                    'nombre'    => $regla->tipoAusentismo->nombre,
                    'sigla'     => strtolower($regla->tipoAusentismo->sigla),
                    'total'     => $total
                ];
            } else if ($regla->grupo_id === 2) {
                $total = $recarga->ausentismos()->where('recarga_id', $recarga->id)->where('grupo_id', 2)->where('tipo_ausentismo_id', $regla->tipoAusentismo->id)->sum('total_dias_ausentismo_periodo');
                $data_2 = [
                    'nombre'    => $regla->tipoAusentismo->nombre,
                    'sigla'     => strtolower($regla->tipoAusentismo->sigla),
                    'total'     => $total
                ];
            } else {
                $total = $recarga->ausentismos()->where('recarga_id', $recarga->id)->where('grupo_id', 3)->where('tipo_ausentismo_id', $regla->tipoAusentismo->id)->sum('total_dias_ausentismo_periodo');
                $data_3 = [
                    'nombre'    => $regla->tipoAusentismo->nombre,
                    'sigla'     => strtolower($regla->tipoAusentismo->sigla),
                    'total'     => $total
                ];
            }
            array_push($ausentismos_all_grupo_uno, $data_1);
            array_push($ausentismos_all_grupo_dos, $data_2);
            array_push($ausentismos_all_grupo_tres, $data_3);
        }

        $data = [
            [
                'nombre_grupo'          => 'GRUPO 1',
                'total_ausentismos'     => $total_grupo_uno,
                'tipos_ausentismos'     => $ausentismos_all_grupo_uno
            ],
            [
                'nombre_grupo'          => 'GRUPO 2',
                'total_ausentismos'     => $total_grupo_dos,
                'tipos_ausentismos'     => $ausentismos_all_grupo_dos
            ],
            [
                'nombre_grupo'          => 'GRUPO 3',
                'total_ausentismos'     => $total_grupo_tres,
                'tipos_ausentismos'     => $ausentismos_all_grupo_tres
            ],
            'total' => $total_grupos
        ];

        return $data;
    }

    public function toArray($request)
    {
        setlocale(LC_ALL, "es_ES");
        Carbon::setLocale('es');
        $tz                         = 'America/Santiago';
        return [
            'id'                            => $this->id,
            'codigo'                        => $this->codigo,
            'anio'                          => $this->anio,
            'mes'                           => Carbon::createFromDate($this->anio, $this->mes, '01', $tz)->formatLocalized('%B'),
            'total_dias_mes'                => $this->total_dias_mes,
            'total_dias_habiles'            => $this->total_dias_habiles,
            'monto_dia'                     => $this->monto_dia,
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
            'total_grupo_uno'               => $this->totalGrupoUno($this),
            'total_grupos'                  => $this->total_grupos($this),
            'monto_total'                   => $this->total(),

            'ausentismos'                   => AusentismosResource::collection($this->ausentismos),
            'establecimiento'               => $this->establecimiento,
            'seguimiento'                   => $this->seguimiento()->with('estado', 'userBy')->orderBy('created_at', 'DESC')->get(),
            'reglas'                        => $this->reglas,
            'user_created_by'               => $this->userCreatedBy,
            'user_update_by'                => $this->userUpdateBy

        ];
    }
}
