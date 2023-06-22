<?php

namespace App\Http\Resources;

use Carbon\Carbon;
use Illuminate\Http\Resources\Json\JsonResource;

class FeriadosResource extends JsonResource
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
            'fecha_corta'               => $this->fecha ? Carbon::parse($this->fecha)->format('d-m-Y') : null,
            'fecha_larga'               => $this->fecha ? Carbon::parse($this->fecha)->formatLocalized('%A') : null,
            'fecha_not_format'          => $this->fecha ? Carbon::parse($this->fecha)->format('Y-m-d') : null,
            'disabled'                  => Carbon::parse($this->fecha)->isWeekend(),
            'nombre'                    => $this->nombre ? $this->nombre : null,
            'irrenunciable'             => $this->irrenunciable != null ? ($this->irrenunciable ? 'Si' : 'No') : null,
            'irrenunciable_value'       => $this->irrenunciable != null ? ($this->irrenunciable ? true : false) : null,
            'tipo'                      => $this->tipo ? $this->tipo : null,
            'observacion'               => $this->comentarios ? $this->comentarios : null
        ];
    }
}
