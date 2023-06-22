<?php

namespace App\Http\Resources;

use App\Models\GrupoAusentismo;
use App\Models\Reajuste;
use Illuminate\Http\Resources\Json\JsonResource;

class FuncionarioRecargaResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function esTurnante($funcionario)
    {
        $turnante = false;

        $turno      = $funcionario->turnos()->where('recarga_id', $funcionario->recarga->id)->where('es_turnante', true)->first();
        $asistencia = $funcionario->asistencias()->where('recarga_id', $funcionario->recarga->id)->first();

        if (($turno) && ($turno->es_turnante && $asistencia)) {
            $turnante = true;
        } else if ($asistencia && !$turno) {
            $turnante = true;
        } else if ($turno && !$asistencia) {
            $turnante = null;
        }

        return $turnante;
    }

    public function esVigente($funcionario)
    {
        $es_vigente     = false;
        $query_results  = $funcionario->contratos()->where('recarga_id', $funcionario->recarga->id)->count();

        if ($query_results > 0) {
            $es_vigente = true;
        }

        return $es_vigente;
    }

    public function toArray($request)
    {
        $es_turnante                 = $this->esTurnante($this);
        $grupos_all = [];
        $grupos = GrupoAusentismo::orderBy('n_grupo', 'asc')->get();
        foreach ($grupos as $grupo) {
            $grupo->{'ausentismos_count'} = $this->ausentismos()->where('recarga_id', $this->recarga->id)->where('grupo_id', $grupo->id)->count();
            array_push($grupos_all, $grupo);
        }
        $total_reajustes_days       = $this->reajustes()->has('latestStatus')->with('latestStatus')->get()->filter(function (Reajuste $reajuste) {
            return $reajuste->latestStatus->status === 1;
        })->sum('dias');

        return [
            'id'                            => $this->id,
            'uuid'                          => $this->uuid,
            'rut_completo'                  => $this->rut_completo,
            'nombre_completo'               => $this->nombre_completo,
            'apellidos'                     => $this->apellidos,
            'dias_habiles'                  => $es_turnante ? $this->recarga->total_dias_mes_beneficio : $this->recarga->total_dias_laborales_beneficio,
            'turnos_count'                  => $this->turnos()->where('recarga_id', $this->recarga->id)->count(),
            'ausentismos_count'             => $this->ausentismos()->where('recarga_id', $this->recarga->id)->count(),
            'reajustes_count'               => $this->reajustes()->where('recarga_id', $this->recarga->id)->count(),
            'contratos_count'               => $this->contratos()->where('recarga_id', $this->recarga->id)->count(),
            'contratos'                     => $this->contratos()->where('recarga_id', $this->recarga->id)->get(),
            'viaticos_count'                => $this->viaticos()->where('recarga_id', $this->recarga->id)->count(),
            'reajustes_total_days'          => $total_reajustes_days,
            'grupos_ausentismo'             => $grupos_all,
            'dias_libres'                   => $this->asistencias()->where('recarga_id', $this->recarga->id)->where('tipo_asistencia_turno_id', 3)->count(),
            'es_turnante'                   => $es_turnante,
            'es_vigente'                    => true
        ];
    }
}
