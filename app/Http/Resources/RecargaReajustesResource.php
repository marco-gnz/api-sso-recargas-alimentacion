<?php

namespace App\Http\Resources;

use App\Models\Reajuste;
use App\Models\ReajusteEstado;
use Carbon\Carbon;
use Illuminate\Http\Resources\Json\JsonResource;

class RecargaReajustesResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function esTurnante($funcionario, $recarga)
    {
        $es_turnante = false;

        $total_turnos                   = $funcionario->turnos()->where('recarga_id', $recarga->id)->where('es_turnante', true)->count();
        $total_asistencias              = $funcionario->asistencias()->where('recarga_id', $recarga->id)->count();
        $total_dias_contrato_periodo    = $funcionario->contratos()->where('recarga_id', $recarga->id)->count();

        if (($total_turnos > 0 && $total_asistencias > 0 && $total_dias_contrato_periodo > 0) || ($total_asistencias > 0 && $total_dias_contrato_periodo > 0)) {
            $es_turnante = true;
        } else if ($total_turnos <= 0 && $total_asistencias > 0 && $total_dias_contrato_periodo > 0) {
            $es_turnante = null;
        } else if ($total_asistencias <= 0 && $total_turnos > 0 && $total_dias_contrato_periodo > 0) {
            $es_turnante = null;
        } else if ($total_dias_contrato_periodo <= 0 && $total_turnos > 0 && $total_asistencias > 0) {
            $es_turnante = null;
        }

        return $es_turnante;
    }

    public function toArray($request)
    {
        $fecha_inicio   = $this->fecha_inicio != null ? Carbon::parse($this->fecha_inicio)->format('d-m-Y') : null;
        $fecha_termino  = $this->fecha_termino != null ? Carbon::parse($this->fecha_termino)->format('d-m-Y') : null;
        $valor_dia      = $this->valor_dia != null ? number_format($this->valor_dia, 0, ",", ".") : null;
        $monto_ajuste   = $this->monto_ajuste != null ? number_format($this->monto_ajuste, 0, ",", ".") : null;
        return [
            'uuid'                  => $this->uuid,
            'esquema_uuid'          => $this->esquema->uuid,
            'uuid_funcionario'      => $this->funcionario != null ? "{$this->funcionario->uuid}" : null,
            'nombres_funcionario'   => $this->funcionario != null ? "{$this->funcionario->nombres} {$this->funcionario->apellidos}" : null,
            'fecha_inicio'          => $fecha_inicio,
            'fecha_termino'         => $fecha_termino,
            'dias_periodo'          => $this->dias_periodo,
            'total_dias'            => $this->total_dias,
            'status'                => $this->last_status,
            'incremento'            => $this->incremento ? true : false,
            'tipo_reajuste'         => $this->tipo_reajuste,
            'tipo_reajuste_nombre'  => Reajuste::TYPE_NOM[$this->tipo_reajuste],
            'incremento_nombre'     => $this->incremento ? 'Incremento' : 'Rebaja',
            'valor_dia'             => $valor_dia != null ? "$" . "{$valor_dia}" : null,
            'monto_ajuste'          => $monto_ajuste != null ? "$" . "{$monto_ajuste}" : null,
            'status_nombre'         => ReajusteEstado::STATUS_NOM[$this->last_status],
            'tipo_ausentismo'       => $this->tipoAusentismo != null ? $this->tipoAusentismo->sigla : null,
            'tipo_incremento'       => $this->tipoIncremento != null ? $this->tipoIncremento->sigla : null,
            'observacion'           => $this->observacion,
            'es_turnante'           => $this->esTurnante($this->funcionario, $this->recarga)
        ];
    }
}
