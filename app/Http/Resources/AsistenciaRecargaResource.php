<?php

namespace App\Http\Resources;

use Carbon\Carbon;
use Illuminate\Http\Resources\Json\JsonResource;

class AsistenciaRecargaResource extends JsonResource
{

    public function toArray($request)
    {
        return [
            'id'                    => $this->id,
            'uuid'                  => $this->uuid,
            'nombres'               => $this->apellidos,
            'asistencias_list'      => AsistenciaUniqueRecargaResource::collection($this->asistencias()->with('tipoAsistenciaTurno')->get()),
            'total_turno_largo'     => $this->asistencias()->where('tipo_asistencia_turno_id', 1)->count(),
            'total_turno_nocturno'  => $this->asistencias()->where('tipo_asistencia_turno_id', 2)->count(),
            'total_dias_libres'     => $this->asistencias()->where('tipo_asistencia_turno_id', 3)->count()
        ];
    }
}
