<?php

namespace App\Http\Resources\Usuarios;

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
        $contrato = $this->contratos()->orderBy('fecha_termino_periodo', 'DESC')->first();
        $esquema  = $this->esquemas()->whereHas('recarga', function ($q) {
            $q->where('active', true);
        })->where('active', true)->orderBy('id', 'DESC')->first();

        return [
            'uuid'              => $this->uuid,
            'id'                => $this->id,
            'rut'               => $this->rut,
            'dv'                => $this->dv,
            'rut_completo'      => $this->rut_completo,
            'nombres'           => $this->nombres,
            'apellidos'         => $this->apellidos,
            'email'             => $this->email,
            'contrato'          => $contrato ? ContratoResource::make($contrato) : null,
            'esquema'           => $esquema ? EsquemaResource::make($esquema) : null
        ];
    }
}
