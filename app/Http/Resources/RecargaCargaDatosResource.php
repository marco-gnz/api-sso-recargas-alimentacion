<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use App\Models\Recarga;
use Carbon\Carbon;

class RecargaCargaDatosResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
        setlocale(LC_ALL,"es_ES");
        Carbon::setLocale('es');
        $tz                                 = 'America/Santiago';
        $monto_dia                          = $this->monto_dia != null ? number_format($this->monto_dia, 0, ",", ".") : null;
        $total_pagado_query                 = $this->esquemas()->where('active', true)->sum('monto_total_cancelar');
        $total_pagado_not_query             = $this->esquemas()->where('active', false)->sum('monto_total_cancelar');
        $total_pagado                       = "$".number_format($total_pagado_query, 0, ",", ".");
        $total_not_pagado                   = "$".number_format($total_pagado_not_query, 0, ",", ".");

        return [
            'id'                            => $this->id,
            'codigo'                        => $this->codigo,
            'anio_beneficio'                => $this->anio_beneficio,
            'mes_beneficio'                 => Carbon::createFromDate($this->anio_beneficio,$this->mes_beneficio, '01', $tz)->locale('es')->monthName,
            'anio_calculo'                  => $this->anio_calculo,
            'mes_calculo'                   => Carbon::createFromDate($this->anio_calculo,$this->mes_calculo, '01', $tz)->locale('es')->monthName,
            'total_dias_mes_beneficio'      => $this->total_dias_mes_beneficio,
            'total_dias_habiles_beneficio'  => $this->total_dias_habiles_beneficio,
            'total_dias_mes_calculo'        => $this->total_dias_mes_calculo ? $this->total_dias_mes_calculo : null,
            'total_dias_habiles_calculo'    => $this->total_dias_habiles_calculo,
            'monto_dia'                     => $this->monto_dia ? "$".$monto_dia : NULL,
            'value_monto_dia'               => $this->monto_dia,
            'monto_estimado_no_turnante'    => $this->monto_estimado,
            'monto_estimado_no_turnante_format'     => "$".number_format($this->monto_estimado, 0, ",", "."),
            'active'                                => $this->active != true ? false : true,
            'n_funcionarios'                => 0,
            'n_funcionarios_vigentes'       => 0,
            'n_funcionarios_no_vigentes'    => 0,
            'total_pagado'                  => $total_pagado,
            'total_pagado_not'              => $total_not_pagado,
            'last_estado'                   => $this->seguimiento()->latest()->with('estado')->first(),
            'date_created_user'             => $this->date_created_user,
            'date_updated_user'             => $this->date_updated_user,
            'users_count'                   => $this->esquemas_count,
            'reajustes_count'               => $this->reajustes_count,
            'contratos_count'               => $this->contratos_count ? $this->contratos_count : null,
            'viaticos_count'                => $this->viaticos_count,
            'ausentismos_count'             => $this->ausentismos_count,
            'asignaciones_count'            => $this->asignaciones_count,
            'last_status_value'             => $this->last_status,
            'last_status'                   => Recarga::NOM_STATUS[$this->last_status],
            'establecimiento'               => $this->establecimiento,
            'user_created_by'               => $this->userCreatedBy,
            'user_update_by'                => $this->userUpdateBy,
            'last_feriado'                  => $this->feriados()->count() > 0 ? Carbon::parse($this->feriados()->orderBy('created_at', 'DESC')->first()->created_at)->format('d-m-Y H:i:s') : null,
            'last_regla'                    => $this->reglas()->count() > 0 ? Carbon::parse($this->reglas()->orderBy('created_at', 'DESC')->first()->created_at)->format('d-m-Y H:i:s') : null,
            'last_contrato'                 => $this->contratos()->count() > 0 ? Carbon::parse($this->contratos()->orderBy('created_at', 'DESC')->first()->created_at)->format('d-m-Y H:i:s') : null,
            'last_asignacion'               => $this->asignaciones()->count() > 0 ? Carbon::parse($this->asignaciones()->orderBy('created_at', 'DESC')->first()->created_at)->format('d-m-Y H:i:s') : null,
            'last_asistencia'               => $this->asistencias()->count() > 0 ? Carbon::parse($this->asistencias()->orderBy('created_at', 'DESC')->first()->created_at)->format('d-m-Y H:i:s') : null,
            'last_ausentismo'               => $this->ausentismos()->count() > 0 ? Carbon::parse($this->ausentismos()->orderBy('created_at', 'DESC')->first()->created_at)->format('d-m-Y H:i:s') : null,
            'last_viatico'                  => $this->viaticos()->count() > 0 ? Carbon::parse($this->viaticos()->orderBy('created_at', 'DESC')->first()->created_at)->format('d-m-Y H:i:s') : null,
        ];
    }
}
