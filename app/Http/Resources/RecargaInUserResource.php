<?php

namespace App\Http\Resources;

use Carbon\Carbon;
use Illuminate\Http\Resources\Json\JsonResource;

class RecargaInUserResource extends JsonResource
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
            'anio_beneficio'                => $this->anio_beneficio,
            'mes_beneficio'                 => Carbon::createFromDate($this->anio_beneficio,$this->mes_beneficio, '01', $tz)->locale('es')->monthName,
            'anio_calculo'                  => $this->anio_calculo,
            'mes_calculo'                   => Carbon::createFromDate($this->anio_calculo,$this->mes_calculo, '01', $tz)->locale('es')->monthName,
            'establecimiento'               => $this->establecimiento ? $this->establecimiento->sigla : null
        ];
    }
}
