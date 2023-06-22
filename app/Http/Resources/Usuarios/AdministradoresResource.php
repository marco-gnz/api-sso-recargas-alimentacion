<?php

namespace App\Http\Resources\Usuarios;

use Illuminate\Http\Resources\Json\JsonResource;

class AdministradoresResource extends JsonResource
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
            'uuid'                  => $this->uuid,
            'id'                    => $this->id,
            'rut'                   => $this->rut,
            'dv'                    => $this->dv,
            'active'                => $this->estado ? true : false,
            'rut_completo'          => $this->rut_completo,
            'nombres'               => $this->nombres,
            'apellidos'             => $this->apellidos,
            'email'                 => $this->email,
            'roles'                 => $this->getRoleNames()->implode(', '),
            'roles_id'              => $this->roles ? $this->roles->pluck('id') : [],
            'establecimientos_id'   => $this->establecimientos ? $this->establecimientos->pluck('id') : [],
            'establecimientos_nom'  => $this->establecimientos ? $this->establecimientos->pluck('sigla')->implode(' - ') : null
        ];
    }
}
