<?php

namespace App\Http\Resources;

use App\Models\Ausentismo;
use Carbon\Carbon;
use Illuminate\Http\Resources\Json\JsonResource;

class AsistenciaRecargaResource extends JsonResource
{

    public function totalAusentismosEnDiasNoLibre($funcionario)
    {
        $total_x = 0;
        $total_l = 0;
        $total_n = 0;

        if (count($funcionario->asistencias) > 0) {
            foreach ($funcionario->asistencias as $asistencia) {
                if (($asistencia) && ($asistencia->tipoAsistenciaTurno->nombre === 'X')) {
                    $total_x += Ausentismo::where('recarga_id', $asistencia->recarga->id)
                        ->where('user_id', $funcionario->id)
                        ->where(function ($query) use ($asistencia) {
                            $query->where('fecha_inicio_periodo', '<=', $asistencia->fecha)
                                ->where('fecha_termino_periodo', '>=', $asistencia->fecha);
                        })
                        ->count();
                } else if (($asistencia) && ($asistencia->tipoAsistenciaTurno->nombre === 'L')) {
                    $total_l += Ausentismo::where('recarga_id', $asistencia->recarga->id)
                        ->where('user_id', $funcionario->id)
                        ->where(function ($query) use ($asistencia) {
                            $query->where('fecha_inicio_periodo', '<=', $asistencia->fecha)
                                ->where('fecha_termino_periodo', '>=', $asistencia->fecha);
                        })
                        ->count();
                } else if (($asistencia) && ($asistencia->tipoAsistenciaTurno->nombre === 'N')) {
                    $total_n += Ausentismo::where('recarga_id', $asistencia->recarga->id)
                        ->where('user_id', $funcionario->id)
                        ->where(function ($query) use ($asistencia) {
                            $query->where('fecha_inicio_periodo', '<=', $asistencia->fecha)
                                ->where('fecha_termino_periodo', '>=', $asistencia->fecha);
                        })
                        ->count();
                }
            }
        }
        return array($total_x, $total_l, $total_n);
    }

    public function totalTurnos($funcionario)
    {
        $total_l_general = 0;
        $total_n_general = 0;
        $total_x_general = 0;

        $total_l = 0;
        $total_n = 0;
        $total_x = 0;

        if (count($funcionario->asistencias)) {
            foreach ($funcionario->asistencias as $asistencia) {
                $fecha      = $asistencia->fecha;
                if (($asistencia->tipoAsistenciaTurno) && ($asistencia->tipoAsistenciaTurno->nombre === 'L')) {
                    $total_l_general++;
                    $total_l += $funcionario->contratos()
                        ->where(function ($query) use ($fecha) {
                            $query->where('fecha_inicio_periodo', '<=', $fecha)
                                ->where('fecha_termino_periodo', '>=', $fecha);
                        })
                        ->count();
                }else if(($asistencia->tipoAsistenciaTurno) && ($asistencia->tipoAsistenciaTurno->nombre === 'N')){
                    $total_n_general++;
                    $total_n += $funcionario->contratos()
                        ->where(function ($query) use ($fecha) {
                            $query->where('fecha_inicio_periodo', '<=', $fecha)
                                ->where('fecha_termino_periodo', '>=', $fecha);
                        })
                        ->count();
                }else if(($asistencia->tipoAsistenciaTurno) && ($asistencia->tipoAsistenciaTurno->nombre === 'X')){
                    $total_x_general++;
                    $total_x += $funcionario->contratos()
                        ->where(function ($query) use ($fecha) {
                            $query->where('fecha_inicio_periodo', '<=', $fecha)
                                ->where('fecha_termino_periodo', '>=', $fecha);
                        })
                        ->count();
                }
            }
        }
        $data_l = (object) [
            'total_general'         => $total_l_general,
            'total_en_contrato'     => $total_l
        ];

        $data_n = (object) [
            'total_general'         => $total_n_general,
            'total_en_contrato'     => $total_n
        ];

        $data_x = (object) [
            'total_general'         => $total_x_general,
            'total_en_contrato'     => $total_x
        ];

        $data = (object) [
            'largo'      => $data_l,
            'nocturno'   => $data_n,
            'libre'      => $data_x,
        ];

        return $data;
    }

    public function toArray($request)
    {
        return [
            'id'                    => $this->id,
            'uuid'                  => $this->uuid,
            'rut_completo'          => $this->rut_completo,
            'nombres'               => $this->apellidos,
            'asistencias_list'      => AsistenciaUniqueRecargaResource::collection($this->asistencias()->with('tipoAsistenciaTurno', 'funcionario')->get()),
            'total_asistencia'      => $this->totalTurnos($this)
        ];
    }
}
