<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class RecargaReglasResource extends JsonResource
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
            'id'                        => $this->id,
            'nombre_tipo_ausentismo'    => $this->tipoAusentismo ? $this->tipoAusentismo->nombre : null,
            'numero_grupo'              => $this->grupoAusentismo ? (int)$this->grupoAusentismo->n_grupo : null,
            'nombre_grupo'              => $this->grupoAusentismo ? $this->grupoAusentismo->n_grupo : null,
            'value_turno_funcionario'   => $this->turno_funcionario,
            'active_tipo_dias'          => $this->active_tipo_dias ? true : false,
            'tipo_dias'                 => $this->tipo_dias ? 'Hábiles' : 'Naturales',
            'meridianos'                => $this->meridianos ? $this->meridianos : null,
            'count_ausentismos'         => $this->ausentismos()->count(),
            'horarios'                  => $this->horarios ? ReglaHorariosResource::collection($this->horarios) : null
        ];
    }
}
