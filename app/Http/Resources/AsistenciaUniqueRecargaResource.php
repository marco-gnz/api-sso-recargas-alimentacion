<?php

namespace App\Http\Resources;

use Carbon\Carbon;
use Illuminate\Http\Resources\Json\JsonResource;

class AsistenciaUniqueRecargaResource extends JsonResource
{

    public function toArray($request)
    {
        return [
            'id'                        => $this->id,
            'fecha'                     => Carbon::parse($this->fecha)->format('d-m-Y'),
            'funcionario_nombres'       => $this->funcionario != null ? $this->funcionario->apellidos : null,
            'tipo_asistencia_turno'     => $this->tipoAsistenciaTurno
        ];
    }
}
