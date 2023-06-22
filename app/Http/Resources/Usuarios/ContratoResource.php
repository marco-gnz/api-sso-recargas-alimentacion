<?php

namespace App\Http\Resources\Usuarios;

use Carbon\Carbon;
use Illuminate\Http\Resources\Json\JsonResource;

class ContratoResource extends JsonResource
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
            'fecha_inicio'                          => $this->fecha_inicio_periodo ? Carbon::parse($this->fecha_inicio_periodo)->format('d-m-Y') : null,
            'fecha_termino'                         => $this->fecha_termino_periodo ? Carbon::parse($this->fecha_termino_periodo)->format('d-m-Y') : null,
        ];
    }
}
