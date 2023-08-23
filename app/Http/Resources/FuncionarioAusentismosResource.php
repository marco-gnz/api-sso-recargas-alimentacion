<?php

namespace App\Http\Resources;

use App\Models\Esquema;
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

    private function isAusentismo($ausentismo)
    {
        $is_ausentismo = false;

        if ($ausentismo->grupo_id === 2) {
            $reglas = $ausentismo->regla->whereHas('meridianos', function ($query) use ($ausentismo) {
                $query->where('meridiano_regla.meridiano_id', $ausentismo->meridiano_id)->where('meridiano_regla.active', true);
            })->count();

            if ($reglas > 0) {
                $is_ausentismo = true;
            }
        } else if ($ausentismo->grupo_id === 3) {
            $hora_inicio    = Carbon::parse($ausentismo->hora_inicio);
            $hora_termino   = Carbon::parse($ausentismo->hora_termino);
            $fecha_inicio   = Carbon::parse($ausentismo->fecha_inicio_periodo);
            $fecha_termino  = Carbon::parse($ausentismo->fecha_termino_periodo);

            if ($ausentismo->regla) {
                $hora_inicio_regla    = Carbon::parse($ausentismo->regla->hora_inicio);
                $hora_termino_regla   = Carbon::parse($ausentismo->regla->hora_termino);

                $concat_inicio        = "{$fecha_inicio->format('Y-m-d')} {$hora_inicio->format('H:i:s')}";
                $concat_termino       = "{$fecha_termino->format('Y-m-d')} {$hora_termino->format('H:i:s')}";
                $concat_inicio_regla  = "{$fecha_inicio->format('Y-m-d')} {$hora_inicio_regla->format('H:i:s')}";
                $concat_termino_regla = "{$fecha_inicio->format('Y-m-d')} {$hora_termino_regla->format('H:i:s')}";

                $hora_inicio_archivo   = Carbon::parse($concat_inicio)->timestamp;
                $hora_termino_archivo  = Carbon::parse($concat_termino)->timestamp;
                $fecha_inicio_regla    = Carbon::parse($concat_inicio_regla)->timestamp;
                $fecha_termino_regla   = Carbon::parse($concat_termino_regla)->timestamp;

                if ($hora_inicio_archivo < $fecha_inicio_regla && $hora_termino_archivo > $fecha_inicio_regla) {
                    $is_ausentismo = true;
                } else if ($hora_inicio_archivo < $fecha_termino_regla && $hora_termino_archivo > $fecha_termino_regla) {
                    $is_ausentismo = true;
                } else if ($hora_inicio_archivo >= $fecha_inicio_regla && $hora_termino_archivo <= $fecha_termino_regla) {
                    $is_ausentismo = true;
                }
            }
        }
        return $is_ausentismo;
    }

    public function esTurnante($funcionario)
    {
        $es_turnante = false;

        $total_turnos                   = $funcionario->turnos()->where('recarga_id', $this->recarga->id)->where('es_turnante', true)->count();
        $total_asistencias              = $funcionario->asistencias()->where('recarga_id', $this->recarga->id)->count();
        $total_dias_contrato_periodo    = $funcionario->contratos()->where('recarga_id', $this->recarga->id)->count();

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
        $valor_viatico = $this->valor_viatico != null ? number_format($this->valor_viatico, 0, ",", ".") : null;

        return [
            'id'                            => $this->id,
            'funcionario_uuid'              => $this->funcionario != null ? $this->funcionario->uuid : null,
            'esquema_uuid'                  => $this->esquema ? $this->esquema->uuid : null,
            'nombre_funcionario'            => $this->funcionario != null ? $this->funcionario->nombre_completo : null,
            'fecha_inicio'                  => $this->fecha_inicio != null ? Carbon::parse($this->fecha_inicio)->format('d-m-Y') : null,
            'fecha_termino'                 => $this->fecha_termino != null ? Carbon::parse($this->fecha_termino)->format('d-m-Y') : null,
            'fecha_inicio_periodo'          => $this->fecha_inicio_periodo != null ? Carbon::parse($this->fecha_inicio_periodo)->format('d-m-Y') : null,
            'fecha_termino_periodo'         => $this->fecha_termino_periodo != null ? Carbon::parse($this->fecha_termino_periodo)->format('d-m-Y') : null,
            'total_dias_ausentismo'         => $this->total_dias_ausentismo,
            'total_dias_ausentismo_periodo' => (int)$this->total_dias_ausentismo_periodo,
            'total_dias_habiles_periodo'    => (int)$this->total_dias_habiles_ausentismo_periodo,
            'total_dias_ausentismo_periodo_turno'               => (int)$this->total_dias_ausentismo_periodo_turno,
            'total_dias_habiles_ausentismo_periodo_turno'       => (int)$this->total_dias_habiles_ausentismo_periodo_turno,
            'nombre_grupo_ausentismo'       => $this->grupoAusentismo->nombre,
            'nombre_tipo_ausentismo'        => $this->tipoAusentismo->nombre,
            'nombre_meridiano'              => $this->meridiano != null ? $this->meridiano->nombre : null,
            'hora_inicio'                   => $this->hora_inicio != null ? Carbon::parse($this->hora_inicio)->format('H:i') : null,
            'hora_termino'                  => $this->hora_termino != null ? Carbon::parse($this->hora_termino)->format('H:i') : null,
            'tiene_descuento'               => $this->tiene_descuento ? true : false,
            'total_horas'                   => $this->total_horas_ausentismo,
            'existe_funcionario'            => $this->esquema ? true : false,
            'es_turnante'                   => $this->esquema ? Esquema::TURNANTE_NOM[$this->esquema->es_turnante] : null,
            'es_turnante_type'              => $this->esquema ? ($this->esquema->es_turnante === 1 ? 'warning' : ($this->esquema->es_turnante === 2 ? 'primary' : 'danger')) : null,
            'regla_tipo_dias'               => $this->regla ? ($this->regla->active_tipo_dias ? ($this->regla->tipo_dias ? 'Hábiles' : 'Naturales') : 'FuncionarioTurno') : 'FuncionarioTurno',
            'descuento_turno_libre'         => $this->descuento_turno_libre ? true : false
        ];
    }
}
