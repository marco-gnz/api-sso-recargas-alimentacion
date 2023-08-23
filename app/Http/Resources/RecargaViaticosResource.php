<?php

namespace App\Http\Resources;

use App\Models\Esquema;
use Carbon\Carbon;
use Illuminate\Http\Resources\Json\JsonResource;

class RecargaViaticosResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    private function existeFuncionarioEnRecarga($funcionario, $recarga)
    {
        $existe = false;

        $query_results = $recarga->whereHas('users', function ($query) use ($funcionario) {
            $query->where('recarga_user.user_id', $funcionario->id);
        })->whereHas('contratos', function ($query) use ($funcionario) {
            $query->where('user_id', $funcionario->id);
        })
            ->count();

        if ($query_results > 0) {
            $existe = true;
        }
        return $existe;
    }

    private function esTurnante($funcionario)
    {
        $es_turnante = false;

        $total_turnos                   = $funcionario->turnos()->where('recarga_id', $this->recarga->id)->where('es_turnante', true)->count();
        $total_asistencias              = $funcionario->asistencias()->where('recarga_id', $this->recarga->id)->count();
        $total_dias_contrato_periodo    = $funcionario->contratos()->where('recarga_id', $this->recarga->id)->count();

        if (($total_turnos > 0 && $total_asistencias > 0 && $total_dias_contrato_periodo > 0) || ($total_asistencias > 0 && $total_dias_contrato_periodo > 0)) {
            $es_turnante = true;
        }

        return $es_turnante;
    }

    public function toArray($request)
    {
        $valor_viatico = $this->valor_viatico != null ? number_format($this->valor_viatico, 0, ",", ".") : null;
        return [
            'uuid'                      => $this->uuid,
            'funcionario_nombres'       => $this->funcionario ? $this->funcionario->nombre_completo : null,
            'funcionario_uuid'          => $this->funcionario ? $this->funcionario->uuid : null,
            'esquema_uuid'              => $this->esquema ? $this->esquema->uuid : null,
            'fecha_inicio'              => $this->fecha_inicio ? Carbon::parse($this->fecha_inicio)->format('d-m-Y') : NULL,
            'fecha_termino'             => $this->fecha_termino ? Carbon::parse($this->fecha_termino)->format('d-m-Y') : NULL,

            'fecha_inicio_periodo'      => $this->fecha_inicio_periodo ? Carbon::parse($this->fecha_inicio_periodo)->format('d-m-Y') : NULL,
            'fecha_termino_periodo'     => $this->fecha_termino_periodo ? Carbon::parse($this->fecha_termino_periodo)->format('d-m-Y') : NULL,

            'total_dias'                        => $this->total_dias ? $this->total_dias : NULL,
            'total_dias_habiles_periodo'        => $this->total_dias_habiles_periodo,
            'total_dias_periodo_turno'          => $this->total_dias_periodo_turno,
            'total_dias_habiles_periodo_turno'  => $this->total_dias_habiles_periodo_turno,

            'jornada'                   => $this->jornada ? $this->jornada : NULL,
            'tipo_resolucion'           => $this->tipo_resolucion ? $this->tipo_resolucion : NULL,
            'n_resolucion'              => $this->n_resolucion ? $this->n_resolucion : NULL,
            'fecha_resolucion'          => $this->fecha_resolucion ? Carbon::parse($this->fecha_resolucion)->format('d-m-Y') : NULL,
            'tipo_comision'             => $this->tipo_comision ? $this->tipo_comision : NULL,
            'motivo_viatico'            => $this->motivo_viatico ? $this->motivo_viatico : NULL,
            'valor_viatico'             => $this->valor_viatico ? "$" . $valor_viatico : NULL,
            'existe_funcionario'        => $this->esquema ? true : false,
            'es_turnante'               => $this->esquema ? Esquema::TURNANTE_NOM[$this->esquema->es_turnante] : null,
            'es_turnante_type'          => $this->esquema ? ($this->esquema->es_turnante === 1 ? 'warning' : ($this->esquema->es_turnante === 2 ? 'primary' : 'danger')) : null,
            'descuento_turno_libre'     => $this->descuento_turno_libre ? true : false
        ];
    }
}
