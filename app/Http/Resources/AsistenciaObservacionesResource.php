<?php

namespace App\Http\Resources;

use Carbon\Carbon;
use Illuminate\Http\Resources\Json\JsonResource;

class AsistenciaObservacionesResource extends JsonResource
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
            'fecha'                     => $this->fecha ? Carbon::parse($this->fecha)->format('d-m-Y') : null,
            'observacion'               => $this->observacion ? $this->observacion : null,
            'tipo_asistencia_nombre'    => $this->tipoAsistenciaTurno ? $this->tipoAsistenciaTurno->nombre : null,
            'user_created_by'           => $this->userCreatedBy ? "{$this->userCreatedBy->nombre_completo}" : null,
            'created_at'                => Carbon::parse($this->created_at)->format('d-m-Y H:i a'),
        ];
    }
}
