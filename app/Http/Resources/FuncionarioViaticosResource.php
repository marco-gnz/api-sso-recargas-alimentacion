<?php

namespace App\Http\Resources;

use Carbon\Carbon;
use Illuminate\Http\Resources\Json\JsonResource;

class FuncionarioViaticosResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
        $valor_viatico              = $this->valor_viatico != null ? number_format($this->valor_viatico, 0, ",", ".") : null;
        return [
            'uuid'                      => $this->uuid,
            'fecha_inicio'              => $this->fecha_inicio ? Carbon::parse($this->fecha_inicio)->format('d-m-Y') : NULL,
            'fecha_termino'             => $this->fecha_termino ? Carbon::parse($this->fecha_termino)->format('d-m-Y') : NULL,
            'fecha_inicio_periodo'      => $this->fecha_inicio_periodo ? Carbon::parse($this->fecha_inicio_periodo)->format('d-m-Y') : NULL,
            'fecha_termino_periodo'     => $this->fecha_termino_periodo ? Carbon::parse($this->fecha_termino_periodo)->format('d-m-Y') : NULL,
            'total_dias'                        => $this->total_dias ? $this->total_dias : NULL,
            'total_dias_habiles_periodo'        => $this->total_dias_habiles_periodo,
            'total_dias_periodo_turno'          => $this->total_dias_periodo_turno,
            'total_dias_habiles_periodo_turno'  => $this->total_dias_habiles_periodo_turno,
            'jornada'                   => $this->jornada ? $this->jornada : NULL,
            'tipo_resolucion'           => $this->tipo_resolucion ? $this->tipo_resolucion : NULL,
            'n_resolucion'              => $this->n_resolucion ? $this->n_resolucion : NULL,
            'fecha_resolucion'          => $this->fecha_resolucion ? Carbon::parse($this->fecha_resolucion)->format('d-m-Y') : NULL,
            'tipo_comision'             => $this->tipo_comision ? $this->tipo_comision : NULL,
            'motivo_viatico'            => $this->motivo_viatico ? $this->motivo_viatico : NULL,
            'valor_viatico'             => $this->valor_viatico ? "$".$valor_viatico : NULL,
            'descuento_turno_libre'     => $this->descuento_turno_libre ? true : false
        ];
    }
}
