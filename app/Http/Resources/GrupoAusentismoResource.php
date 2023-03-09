<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class GrupoAusentismoResource extends JsonResource
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
            'id'                => $this->id,
            'n_grupo'           => $this->n_grupo,
            'nombre'            => $this->nombre,
            'descripcion'       => $this->descripcion,
            'total_ausentismos' => $this->ausentismos()->count()
        ];
    }
}
