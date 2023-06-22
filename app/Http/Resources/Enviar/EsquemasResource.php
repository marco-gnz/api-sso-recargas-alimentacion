<?php

namespace App\Http\Resources\Enviar;

use Carbon\Carbon;
use Illuminate\Http\Resources\Json\JsonResource;

class EsquemasResource extends JsonResource
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
        $tz                                 = 'America/Santiago';

        return [
            'uuid'              => $this->uuid,
            'anio_beneficio'    => $this->recarga->anio_beneficio,
            'mes_beneficio'     => Carbon::createFromDate($this->recarga->anio_beneficio,$this->recarga->mes_beneficio, '01', $tz)->formatLocalized('%B'),
            'beneficio'         => $this->active ? 'Con beneficio' : 'Sin beneficio',
            'establecimiento'   => $this->recarga->establecimiento->nombre

        ];
    }
}
