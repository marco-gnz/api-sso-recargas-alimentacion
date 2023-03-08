<?php

namespace App\Http\Resources;

use Carbon\Carbon;
use Illuminate\Http\Resources\Json\JsonResource;

class RecargaReglasResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
        $hora_inicio    = $this->hora_inicio ? Carbon::parse($this->hora_inicio)->format('H:i') : null;
        $hora_termino   = $this->hora_termino ? Carbon::parse($this->hora_termino)->format('H:i') : null;
        return [
            'id'                        => $this->id,
            'nombre_tipo_ausentismo'    => $this->tipoAusentismo ? $this->tipoAusentismo->nombre : null,
            'numero_grupo'              => $this->grupoAusentismo ? (int)$this->grupoAusentismo->n_grupo : null,
            'nombre_grupo'              => $this->grupoAusentismo ? $this->grupoAusentismo->nombre : null,
            'value_turno_funcionario'   => $this->turno_funcionario,
            'meridianos'                => $this->meridianos,
            'hora_inicio'               => $hora_inicio,
            'hora_termino'              => $hora_termino,
            'meridianos'                => $this->meridianos ? $this->meridianos->pluck('nombre')->implode(' - ') : null,
            'count_ausentismos'         => $this->ausentismos()->count()
        ];
    }
}
