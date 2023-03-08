<?php

namespace App\Http\Resources;

use Carbon\Carbon;
use Illuminate\Http\Resources\Json\JsonResource;

class AsistenciaFindResource extends JsonResource
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
            'id'                                => $this->id,
            'fecha'                             => $this->fecha != null ? Carbon::parse($this->fecha)->format('d-m-Y') : null,
            'funcionario_nombres'               => $this->funcionario != null ? "{$this->funcionario->nombre_completo}" : null,
            'tipo_asistencia_turno_id'          => $this->tipo_asistencia_turno_id,
            'tipo_asistencia_turno_nombre'      => $this->tipoAsistenciaTurno != null ? $this->tipoAsistenciaTurno->descripcion : null,
            'user_created_by'                   => $this->userCreatedBy != null ? "{$this->userCreatedBy->nombre_completo}" : null,
            'observaciones'                     => AsistenciaObservacionesResource::collection($this->observaciones)
        ];
    }
}
