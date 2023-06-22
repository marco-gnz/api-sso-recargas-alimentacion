<?php

namespace App\Http\Resources;

use Carbon\Carbon;
use Illuminate\Http\Resources\Json\JsonResource;

class ReglaHorariosResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
        $hora_inicio    = $this->hora_inicio ? Carbon::parse($this->hora_inicio)->format('H:i') : null;
        $hora_termino   = $this->hora_termino ? Carbon::parse($this->hora_termino)->format('H:i') : null;

        return [
            'id'                        => $this->id,
            'hora_inicio_value'         => $this->hora_inicio,
            'hora_termino_value'        => $this->hora_termino,
            'hora_inicio'               => $hora_inicio,
            'hora_termino'              => $hora_termino
        ];
    }
}
