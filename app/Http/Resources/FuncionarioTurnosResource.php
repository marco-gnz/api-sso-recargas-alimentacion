<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class FuncionarioTurnosResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
        return [
            'uuid'                              => $this->uuid,
            'anio'                              => $this->anio,
            'mes'                               => $this->mes,
            'asignacion_tercer_turno'           => (int)$this->asignacion_tercer_turno,
            'bonificacion_asignacion_turno'     => (int)$this->bonificacion_asignacion_turno,
            'asignacion_cuarto_turno'           => (int)$this->asignacion_cuarto_turno,
            'es_turnante'                       => $this->es_turnante ? true : false,

            'nombre_proceso'                    => $this->proceso != null ? $this->proceso->nombre : null,
            'nombre_calidad'                    => $this->calidad != null ? $this->calidad->nombre : null,
            'nombre_establecimiento'            => $this->establecimiento != null ? $this->establecimiento->sigla : null,
            'nombre_unidad'                     => $this->unidad != null ? $this->unidad->nombre : null,
            'nombre_planta'                     => $this->planta != null ? $this->planta->nombre : null
        ];
    }
}
