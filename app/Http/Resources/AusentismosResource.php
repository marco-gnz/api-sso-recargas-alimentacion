<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class AusentismosResource extends JsonResource
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
            'funcionario'               => $this->funcionario,
            'grupo_uno'                 => $this->funcionario
        ];
    }
}
