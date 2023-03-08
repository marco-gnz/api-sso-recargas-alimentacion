<?php

namespace App\Http\Resources;

use Carbon\Carbon;
use Illuminate\Http\Resources\Json\JsonResource;

class FuncionarioAusentismosResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
        $feriados_count             = $this->recarga->feriados()->where('active', true)->whereBetween('fecha', [$this->fecha_inicio_periodo, $this->fecha_termino_periodo])->count();
        $valor_viatico              = $this->valor_viatico != null ? number_format($this->valor_viatico, 0, ",", ".") : null;
        $total_dias_habiles_periodo = ($this->total_dias_habiles_ausentismo_periodo - $feriados_count);

        $hora_inicio   = $this->regla->hora_inicio != null ? Carbon::parse($this->regla->hora_inicio)->format('H:i') : null;
        $hora_termino  = $this->regla->hora_termino != null ? Carbon::parse($this->regla->hora_termino)->format('H:i') : null;
        return [
            'id'                            => $this->id,
            'fecha_inicio'                  => $this->fecha_inicio != null ? Carbon::parse($this->fecha_inicio)->format('d-m-Y') : null,
            'fecha_termino'                 => $this->fecha_termino != null ? Carbon::parse($this->fecha_termino)->format('d-m-Y') : null,
            'fecha_inicio_periodo'          => $this->fecha_inicio_periodo != null ? Carbon::parse($this->fecha_inicio_periodo)->format('d-m-Y') : null,
            'fecha_termino_periodo'         => $this->fecha_termino_periodo != null ? Carbon::parse($this->fecha_termino_periodo)->format('d-m-Y') : null,
            'total_dias_ausentismo'         => $this->total_dias_ausentismo,
            'total_dias_ausentismo_periodo' => $this->total_dias_ausentismo_periodo,
            'total_dias_habiles_periodo'    => $total_dias_habiles_periodo,
            'nombre_grupo_ausentismo'       => $this->grupoAusentismo->nombre,
            'nombre_tipo_ausentismo'        => $this->tipoAusentismo->nombre,
            'nombre_meridiano'              => $this->meridiano != null ? $this->meridiano->nombre : null,
            'hora_inicio'                   => $this->hora_inicio != null ? Carbon::parse($this->hora_inicio)->format('H:i') : null,
            'hora_termino'                  => $this->hora_termino != null ? Carbon::parse($this->hora_termino)->format('H:i') : null,
            'hora_inicio_regla'             => $hora_inicio,
            'hora_termino_regla'            => $hora_termino,
            'tiene_descuento'               => $this->tiene_descuento != null ? ($this->tiene_descuento ? true : false) : null,
            'total_horas'                   => $this->total_horas_ausentismo,
            'regla'                         => $this->regla->hora_inicio != null ? "{$this->regla->hora_inicio} {$this->regla->hora_termino}" : null
        ];
    }
}
