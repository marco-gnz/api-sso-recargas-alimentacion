<?php

namespace App\Http\Resources;

use App\Models\ReajusteAlerta;
use Illuminate\Http\Resources\Json\JsonResource;

class AlertasReajuste extends JsonResource
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
            'tipo'          => ReajusteAlerta::TIPO_NOM[$this->tipo],
            'observacion'   => $this->observacion
        ];
    }
}
