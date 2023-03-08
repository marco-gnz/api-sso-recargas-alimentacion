<?php

namespace App\Http\Resources;

use Carbon\Carbon;
use Illuminate\Http\Resources\Json\JsonResource;

class FuncionarioContratosResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
        $feriados_count                         = $this->recarga->feriados()->where('active', true)->whereBetween('fecha', [$this->fecha_inicio_periodo, $this->fecha_termino_periodo])->count();
        $total_dias_habiles_contrato_periodo    = ($this->total_dias_habiles_contrato_periodo - $feriados_count);
        return [
            'fecha_inicio'                          => $this->fecha_inicio ? Carbon::parse($this->fecha_inicio)->format('d-m-Y') : null,
            'fecha_termino'                         => $this->fecha_termino ? Carbon::parse($this->fecha_termino)->format('d-m-Y') : null,
            'total_dias_contrato'                   => $this->total_dias_contrato ? $this->total_dias_contrato : null,
            'fecha_inicio_periodo'                  => $this->fecha_inicio ? Carbon::parse($this->fecha_inicio_periodo)->format('d-m-Y') : null,
            'fecha_termino_periodo'                 => $this->fecha_termino_periodo ? Carbon::parse($this->fecha_termino_periodo)->format('d-m-Y') : null,
            'total_dias_contrato_periodo'           => $this->total_dias_contrato_periodo ? round($this->total_dias_contrato_periodo).' d' : null,
            'total_dias_habiles_contrato_periodo'   => $total_dias_habiles_contrato_periodo,
            'unidad_nombre'                         => $this->unidad ? $this->unidad->nombre : null,
            'planta_nombre'                         => $this->planta ? $this->planta->nombre : null,
            'cargo_nombre'                          => $this->cargo ? $this->cargo->nombre : null,
            'ley_nombre'                            => $this->ley ? $this->ley->codigo : null,
            'hora_nombre'                           => $this->hora ? $this->hora->nombre : null,
            'alejamiento'                           => $this->alejamiento ? true : false
        ];
    }
}
