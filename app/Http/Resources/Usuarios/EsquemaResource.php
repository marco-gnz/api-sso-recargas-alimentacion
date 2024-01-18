<?php

namespace App\Http\Resources\Usuarios;

use Carbon\Carbon;
use Illuminate\Http\Resources\Json\JsonResource;

class EsquemaResource extends JsonResource
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
        $tz = 'America/Santiago';

        return [
            'monto_total_cancelar_format'       => "$" . number_format($this->monto_total_cancelar, 0, ",", "."),
            'mes_beneficio'                     => Carbon::createFromDate($this->recarga->anio_beneficio,$this->recarga->mes_beneficio, '01', $tz)->locale('es')->monthName,
            'anio_beneficio'                    => $this->recarga->anio_beneficio,
            'establecimiento'                   => $this->recarga->establecimiento->sigla
        ];
    }
}
