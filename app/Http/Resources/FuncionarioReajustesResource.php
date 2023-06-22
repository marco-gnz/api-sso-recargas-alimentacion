<?php

namespace App\Http\Resources;

use App\Models\Reajuste;
use App\Models\ReajusteEstado;
use Carbon\Carbon;
use Illuminate\Http\Resources\Json\JsonResource;

class FuncionarioReajustesResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
        $fecha_inicio   = $this->fecha_inicio != null ? Carbon::parse($this->fecha_inicio)->format('d-m-Y') : null;
        $fecha_termino  = $this->fecha_termino != null ? Carbon::parse($this->fecha_termino)->format('d-m-Y') : null;
        $valor_dia      = $this->valor_dia != null ? number_format($this->valor_dia, 0, ",", ".") : null;
        $monto_ajuste   = $this->monto_ajuste != null ? number_format($this->monto_ajuste, 0, ",", ".") : null;
        return [
            'uuid'                  => $this->uuid,
            'uuid_funcionario'      => $this->funcionario != null ? "{$this->funcionario->uuid}" : null,
            'nombres_funcionario'   => $this->funcionario != null ? "{$this->funcionario->nombres} {$this->funcionario->apellidos}" : null,
            'fecha_inicio'          => $fecha_inicio,
            'fecha_termino'         => $fecha_termino,
            'dias_periodo'          => $this->dias_periodo,
            'dias_periodo_habiles'  => $this->dias_periodo_habiles,
            'total_dias'            => $this->total_dias,
            'status'                => $this->last_status,
            'incremento'            => $this->incremento ? true : false,
            'incremento_nombre'     => $this->incremento ? 'Incremento' : 'Rebaja',
            'tipo_reajuste'         => $this->tipo_reajuste,
            'tipo_reajuste_nombre'  => Reajuste::TYPE_NOM[$this->tipo_reajuste],
            'status_nombre'         => ReajusteEstado::STATUS_NOM[$this->last_status],
            'valor_dia'             => $valor_dia != null ? "$"."{$valor_dia}" : null,
            'monto_ajuste'          => $monto_ajuste != null ? "$"."{$monto_ajuste}" : null,
            'tipo_ausentismo'       => $this->tipoAusentismo != null ? $this->tipoAusentismo->sigla : null,
            'tipo_incremento'       => $this->tipoIncremento != null ? $this->tipoIncremento->sigla : null,
            'observacion'           => $this->observacion,
            'alertas'               => AlertasReajuste::collection($this->alertas)
        ];
    }
}
