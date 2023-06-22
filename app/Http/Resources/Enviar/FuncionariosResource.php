<?php

namespace App\Http\Resources\Enviar;

use Illuminate\Http\Resources\Json\JsonResource;

class FuncionariosResource extends JsonResource
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
            'uuid'              => $this->uuid,
            'rut_completo'      => $this->rut_completo,
            'nombre_completo'   => $this->nombre_completo
        ];
    }
}
