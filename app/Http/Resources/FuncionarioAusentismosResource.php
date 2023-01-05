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
        return [
            'id'                            => $this->id,
            'fecha_inicio'                  => $this->fecha_inicio != null ? Carbon::parse($this->fecha_inicio)->format('d-m-Y') : null,
            'fecha_termino'                 => $this->fecha_termino != null ? Carbon::parse($this->fecha_termino)->format('d-m-Y') : null,
            'fecha_inicio_periodo'          => $this->fecha_inicio_periodo != null ? Carbon::parse($this->fecha_inicio_periodo)->format('d-m-Y') : null,
            'fecha_termino_periodo'         => $this->fecha_termino_periodo != null ? Carbon::parse($this->fecha_termino_periodo)->format('d-m-Y') : null,
            'total_dias_ausentismo'         => $this->total_dias_ausentismo,
            'total_dias_ausentismo_periodo' => $this->total_dias_ausentismo_periodo,
            'nombre_grupo_ausentismo'       => $this->grupoAusentismo->nombre,
            'establecimiento'               => $this->establecimiento->sigla,
            'nombre_tipo_ausentismo'        => $this->tipoAusentismo->nombre
        ];
    }
}
