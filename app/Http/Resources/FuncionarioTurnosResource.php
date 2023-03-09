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
            'funcionario_uuid'                  => $this->funcionario ? $this->funcionario->uuid : null,
            'nombres'                           => $this->funcionario ? $this->funcionario->nombre_completo : null,
            'anio'                              => $this->anio,
            'mes'                               => $this->mes,
            'asignacion_tercer_turno'           => (int)$this->asignacion_tercer_turno,
            'bonificacion_asignacion_turno'     => (int)$this->bonificacion_asignacion_turno,
            'asignacion_cuarto_turno'           => (int)$this->asignacion_cuarto_turno,
            'es_turnante'                       => $this->es_turnante ? true : false,
            'nombre_proceso'                    => $this->proceso != null ? $this->proceso->nombre : null,
        ];
    }
}
