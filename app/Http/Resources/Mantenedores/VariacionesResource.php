<?php

namespace App\Http\Resources\Mantenedores;

use Illuminate\Http\Resources\Json\JsonResource;

class VariacionesResource extends JsonResource
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
            'id'        => $this->id,
            'cod_sirh'  => $this->codigo_sirh ? $this->codigo_sirh : null,
            'name'      => $this->nombre ? $this->nombre : null,
            'sigla'     => $this->sigla ? $this->sigla : null
        ];
    }
}
