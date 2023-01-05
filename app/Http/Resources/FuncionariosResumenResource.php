<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class FuncionariosResumenResource extends JsonResource
{

    public function diasCancelar($total_dias_ausentismo, $recarga, $turno, $total_dias_cancelar = 0)
    {

        if (!$turno) {
            $total_dias_cancelar = ($recarga->total_dias_habiles - $total_dias_ausentismo);
        } else {
            $total_dias_cancelar = ($recarga->total_dias_mes - $total_dias_ausentismo);
        }

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

        $total_grupo_uno  += $funcionario->ausentismos()->where('recarga_id', $this->recarga->id)->where('grupo_id', 1)->sum('total_dias_ausentismo_periodo');
        $total_grupo_dos  += $funcionario->ausentismos()->where('recarga_id', $this->recarga->id)->where('grupo_id', 2)->sum('total_dias_ausentismo_periodo');
        $total_grupo_tres += $funcionario->ausentismos()->where('recarga_id', $this->recarga->id)->where('grupo_id', 3)->sum('total_dias_ausentismo_periodo');

        $ausentismos_all_grupo_uno  = [];
        $ausentismos_all_grupo_dos  = [];
        $ausentismos_all_grupo_tres = [];

        $data_1 = [];
        $data_2 = [];
        $data_3 = [];

        $reglas = $this->recarga->reglas;

        foreach ($reglas as $regla) {
            if ($regla->grupo_id === 1) {
                $data_1 = [
                    'nombre'    => $regla->tipoAusentismo->nombre,
                    'sigla'     => strtolower($regla->tipoAusentismo->sigla),
                    'total'     => $funcionario->ausentismos()->where('recarga_id', $this->recarga->id)->where('grupo_id', 1)->where('tipo_ausentismo_id', $regla->tipoAusentismo->id)->sum('total_dias_ausentismo_periodo')
                ];
            } else if ($regla->grupo_id === 2) {
                $data_2 = [
                    'nombre'    => $regla->tipoAusentismo->nombre,
                    'sigla'     => strtolower($regla->tipoAusentismo->sigla),
                    'total'     => $funcionario->ausentismos()->where('recarga_id', $this->recarga->id)->where('grupo_id', 2)->where('tipo_ausentismo_id', $regla->tipoAusentismo->id)->sum('total_dias_ausentismo_periodo')
                ];
            } else {
                $data_3 = [
                    'nombre'    => $regla->tipoAusentismo->nombre,
                    'sigla'     => strtolower($regla->tipoAusentismo->sigla),
                    'total'     => $funcionario->ausentismos()->where('recarga_id', $this->recarga->id)->where('grupo_id', 3)->where('tipo_ausentismo_id', $regla->tipoAusentismo->id)->sum('total_dias_ausentismo_periodo')
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
        ];

        return $data;
    }

    public function esTurnante($funcionario, $recarga)
    {
        $turnante = false;

        $turno      = $funcionario->turnos()->where('recarga_id', $recarga->id)->where('es_turnante', true)->first();
        $asistencia = $funcionario->asistencias()->where('recarga_id', $recarga->id)->first();

        if(($turno) && ($turno->es_turnante && $asistencia)){
            $turnante = true;
        }else if($asistencia && !$turno){
            $turnante = true;
        }else if($turno && !$asistencia){
            $turnante = null;
        }

        return $turnante;
    }

    public function toArray($request)
    {
        $turno                      = $this->turnos()->where('recarga_id', $this->recarga->id)->first();
        $total_dias_ausentismo      = $this->ausentismos()->where('recarga_id', $this->recarga->id)->whereIn('grupo_id', [1, 2, 3])->sum('total_dias_ausentismo_periodo');
        $total_dias_cancelar        = $this->diasCancelar($total_dias_ausentismo, $this->recarga, $this->turno);

        return [
            'id'                    => $this->id,
            'uuid'                  => $this->uuid,
            'beneficio'             => $this->recargas()->first()->pivot->beneficio ? true : false,
            'rut_completo'          => $this->rut_completo,
            'apellidos'             => $this->apellidos,
            'nombre_completo'       => $this->nombre_completo,
            'turno'                 => $this->esTurnante($this, $this->recarga),
            'tipo_pago'             => $turno != null ? ($turno->proceso ? $turno->proceso->nombre : null) : null,
            'grupo_uno'             => $this->ausentismos()->where('recarga_id', $this->recarga->id)->where('grupo_id', 1)->sum('total_dias_ausentismo_periodo'),
            'total_grupos'          => $this->totalGrupos($this),
            'dias_cancelar'         => $total_dias_cancelar,
            'total_cancelar'        => $this->recarga->monto_dia * $total_dias_cancelar,
            'dias_libres'           => $this->asistencias()->where('recarga_id', $this->recarga->id)->where('tipo_asistencia_turno_id', 3)->count()
        ];
    }
}
