<?php

namespace App\Http\Resources;

use App\Models\Esquema;
use Carbon\Carbon;
use Illuminate\Http\Resources\Json\JsonResource;

class RecargaContratosResource extends JsonResource
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
        $feriados_count                         = $this->recarga->feriados()->where('active', true)->whereBetween('fecha', [$this->fecha_inicio_periodo, $this->fecha_termino_periodo])->count();
        $total_dias_habiles_contrato_periodo    = ($this->total_dias_habiles_contrato_periodo - $feriados_count);
        return [
            'uuid'                                  => $this->uuid,
            'esquema_uuid'                          => $this->esquema ? $this->esquema->uuid : null,
            'fecha_inicio'                          => $this->fecha_inicio ? Carbon::parse($this->fecha_inicio)->format('d-m-Y') : null,
            'fecha_termino'                         => $this->fecha_termino ? Carbon::parse($this->fecha_termino)->format('d-m-Y') : null,
            'total_dias_contrato'                   => $this->total_dias_contrato ? $this->total_dias_contrato : null,
            'fecha_inicio_periodo'                  => $this->fecha_inicio ? Carbon::parse($this->fecha_inicio_periodo)->format('d-m-Y') : null,
            'fecha_termino_periodo'                 => $this->fecha_termino_periodo ? Carbon::parse($this->fecha_termino_periodo)->format('d-m-Y') : null,
            'total_dias_contrato_periodo'           => $this->total_dias_contrato_periodo ? round($this->total_dias_contrato_periodo) . ' d' : null,
            'total_dias_habiles_contrato_periodo'   => $this->total_dias_habiles_contrato_periodo,
            'funcionario_nombres'                   => $this->funcionario ? $this->funcionario->nombre_completo : null,
            'funcionario_uuid'                      => $this->funcionario ? $this->funcionario->uuid : null,
            'unidad_nombre'                         => $this->unidad ? $this->unidad->nombre : null,
            'planta_nombre'                         => $this->planta ? $this->planta->nombre : null,
            'cargo_nombre'                          => $this->cargo ? $this->cargo->nombre : null,
            'ley_nombre'                            => $this->ley ? $this->ley->codigo : null,
            'hora_nombre'                           => $this->hora ? $this->hora->nombre : null,
            'alejamiento'                           => $this->alejamiento ? true : false,
            'existe_funcionario'                    => $this->esquema ? true : false,
            'es_turnante'                           => $this->esquema ? Esquema::TURNANTE_NOM[$this->esquema->es_turnante] : null,
            'es_turnante_type'                      => $this->esquema ? ($this->esquema->es_turnante === 1 ? 'warning' : ($this->esquema->es_turnante === 2 ? 'primary' : 'danger')) : null,
        ];
    }
}
