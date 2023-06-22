<?php

namespace App\Http\Resources;

use Carbon\Carbon;
use Illuminate\Http\Resources\Json\JsonResource;

class ReglaResource extends JsonResource
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
            'grupo_id'                  => $this->grupo_id,
            'turno_funcionario'         => $this->turno_funcionario,
            'active_tipo_dias'          => $this->active_tipo_dias ? true : false,
            'tipo_dias'                 => $this->tipo_dias ? true : false,
            'meridianos'                => $this->meridianos ? $this->meridianos()->where('active', true)->pluck('meridianos.id')->toArray() : [],
            'horarios'                  => $this->horarios ? ReglaHorariosResource::collection($this->horarios) : null
        ];
    }
}
