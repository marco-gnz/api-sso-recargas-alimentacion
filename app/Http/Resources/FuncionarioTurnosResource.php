<?php

namespace App\Http\Resources;

use App\Models\Esquema;
use Illuminate\Http\Resources\Json\JsonResource;

class FuncionarioTurnosResource extends JsonResource
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

    private function existeFuncionarioEnRecarga($funcionario, $recarga)
    {
        $existe = false;

        $query_results = $recarga->whereHas('users', function ($query) use ($funcionario) {
            $query->where('recarga_user.user_id', $funcionario->id);
        })->whereHas('contratos', function ($query) use ($funcionario) {
            $query->where('user_id', $funcionario->id);
        })
            ->count();

        if ($query_results > 0) {
            $existe = true;
        }
        return $existe;
    }

    public function toArray($request)
    {
        return [
            'uuid'                              => $this->uuid,
            'esquema_uuid'                      => $this->esquema ? $this->esquema->uuid : null,
            'funcionario_uuid'                  => $this->funcionario ? $this->funcionario->uuid : null,
            'nombres'                           => $this->funcionario ? $this->funcionario->nombre_completo : null,
            'anio'                              => $this->anio,
            'mes'                               => $this->mes,
            'asignacion_tercer_turno'           => (int)$this->asignacion_tercer_turno,
            'bonificacion_asignacion_turno'     => (int)$this->bonificacion_asignacion_turno,
            'asignacion_cuarto_turno'           => (int)$this->asignacion_cuarto_turno,
            'asignacion_turno'                  => $this->es_turnante ? true : false,
            'nombre_proceso'                    => $this->proceso != null ? $this->proceso->nombre : null,
            'existe_funcionario'                => $this->esquema ? true : false,
            'es_turnante'                       => $this->esquema ? Esquema::TURNANTE_NOM[$this->esquema->es_turnante] : null,
            'es_turnante_type'                  => $this->esquema ? ($this->esquema->es_turnante === 1 ? 'warning' : ($this->esquema->es_turnante === 2 ? 'primary' : 'danger')) : null,
        ];
    }
}
