<?php

namespace App\Http\Resources;

use App\Models\Ausentismo;
use Carbon\Carbon;
use Illuminate\Http\Resources\Json\JsonResource;

class AsistenciaUniqueRecargaResource extends JsonResource
{

    private function existAusentismo($asistencia)
    {
        $exist                      = false;
        $tz                         = 'America/Santiago';
        $date_recarga               = Carbon::createFromDate($asistencia->recarga->anio_beneficio, $asistencia->recarga->mes_beneficio, '01', $tz);
        $primer_dia_mes_anterior    = $date_recarga->addMonth()->startOfMonth();
        $mont_last                  = $primer_dia_mes_anterior->format('m');
        $year_last                  = $primer_dia_mes_anterior->format('Y');

        $fecha                      = $asistencia->fecha;

        $ausentismos  = $asistencia->funcionario->ausentismos()
            ->whereHas('recarga', function ($q) use ($mont_last, $year_last) {
                $q->where('mes_beneficio', $mont_last)
                    ->where('anio_beneficio', $year_last)
                    ->where('active', true);
            })
            ->where(function ($query) use ($fecha) {
                $query->where('fecha_inicio_periodo', '<=', $fecha)
                    ->where('fecha_termino_periodo', '>=', $fecha)
                    ->where('tiene_descuento', true);
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
            'exist_ausentismo'          => $this->existAusentismo($this),
            'observaciones_count'       => $this->observaciones()->count(),
            'esquema_uuid'              => $this->esquema ? $this->esquema->uuid : null,
        ];
    }
}
