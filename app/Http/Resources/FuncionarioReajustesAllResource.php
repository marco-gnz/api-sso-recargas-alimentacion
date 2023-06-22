<?php

namespace App\Http\Resources;

use App\Models\Reajuste;
use App\Models\ReajusteEstado;
use Carbon\Carbon;
use Illuminate\Http\Resources\Json\JsonResource;

class FuncionarioReajustesAllResource extends JsonResource
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
            'nombres_funcionario'   => $this->funcionario != null ? "{$this->funcionario->nombres} {$this->funcionario->apellidos}" : null,
            'fecha_inicio'          => $fecha_inicio,
            'fecha_termino'         => $fecha_termino,
            'dias_periodo'          => $this->dias_periodo,
            'dias_periodo_habiles'  => $this->dias_periodo_habiles,
            'total_dias'            => $this->total_dias,
            'status'                => $this->last_status,
            'incremento'            => $this->incremento ? true : false,
            'incremento_nombre'     => $this->incremento ? 'Incremento' : 'Rebaja',
            'calculo_dias'          => $this->calculo_dias ? 'Días de periodo' : 'Días hábiles',
            'tipo_reajuste'         => $this->tipo_reajuste,
            'tipo_reajuste_nombre'  => Reajuste::TYPE_NOM[$this->tipo_reajuste],
            'valor_dia'             => $valor_dia != null ? "$"."{$valor_dia}" : null,
            'monto_ajuste'          => $monto_ajuste != null ? "$"."{$monto_ajuste}" : null,
            'status_nombre'         => ReajusteEstado::STATUS_NOM[$this->last_status],
            'tipo_ausentismo'       => $this->tipoAusentismo != null ? $this->tipoAusentismo->nombre : null,
            'tipo_incremento'       => $this->tipoIncremento != null ? $this->tipoIncremento->nombre : null,
            'observacion'           => $this->observacion,
            'user_created_by'       => $this->userCreatedBy != null ? "{$this->userCreatedBy->nombres} {$this->userCreatedBy->apellidos}" : null,
            'date_created_user'     => Carbon::parse($this->date_created_user)->format('d-m-Y H:i a'),
            'estados'               => EstadosReajusteResource::collection($this->estados),
            'alertas'               => AlertasReajuste::collection($this->alertas)
        ];
    }
}
