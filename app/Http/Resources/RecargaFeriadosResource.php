<?php

namespace App\Http\Resources;

use Carbon\Carbon;
use Illuminate\Http\Resources\Json\JsonResource;

class RecargaFeriadosResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
        setlocale(LC_ALL,"es_ES");
        Carbon::setLocale('es');
        return [
            'id'                        => $this->id,
            'fecha_corta'               => $this->fecha ? Carbon::parse($this->fecha)->format('d-m-Y') : null,
            'fecha_larga'               => $this->fecha ? Carbon::parse($this->fecha)->formatLocalized('%A') : null,
            'nombre'                    => $this->nombre ? $this->nombre : null,
            'irrenunciable'             => $this->irrenunciable ? 'Si' : 'No',
        ];
    }
}
