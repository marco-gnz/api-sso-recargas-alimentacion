<?php

namespace App\Http\Resources;

use App\Models\Ausentismo;
use Carbon\Carbon;
use Illuminate\Http\Resources\Json\JsonResource;

class AsistenciaUniqueRecargaResource extends JsonResource
{

    private function existAusentismo($asistencia)
    {
        $exist = false;
        $fecha = $asistencia->fecha;
        $ausentismos = Ausentismo::where('recarga_id', $asistencia->recarga->id)
            ->where('user_id', $asistencia->funcionario->id)
            ->where(function ($query) use ($fecha) {
                $query->where('fecha_inicio_periodo', '<=', $fecha)
                    ->where('fecha_termino_periodo', '>=', $fecha);
            })
            ->count();
        if ($ausentismos > 0) {
            $exist = true;
        }
        return $exist;
    }

    private function existContrato($asistencia)
    {
        $exist      = false;
        $fecha      = $asistencia->fecha;
        $contratos  = $asistencia->esquema->contratos()
            ->where(function ($query) use ($fecha) {
                $query->where('fecha_inicio_periodo', '<=', $fecha)
                    ->where('fecha_termino_periodo', '>=', $fecha);
            })
            ->count();

        if ($contratos > 0) {
            $exist = true;
        }

        return $exist;
    }

    public function toArray($request)
    {
        return [
            'id'                        => $this->id,
            'uuid'                      => $this->uuid,
            'fecha'                     => Carbon::parse($this->fecha)->format('d-m-Y'),
            'funcionario_nombres'       => $this->funcionario != null ? "{$this->funcionario->nombres} {$this->funcionario->apellidos}" : null,
            'tipo_asistencia_turno'     => $this->tipoAsistenciaTurno,
            'exist_asistencia'          => false,
            'exist_contrato'            => $this->existContrato($this),
            'observaciones_count'       => $this->observaciones()->count(),
            'esquema_uuid'              => $this->esquema ? $this->esquema->uuid : null,
        ];
    }
}
