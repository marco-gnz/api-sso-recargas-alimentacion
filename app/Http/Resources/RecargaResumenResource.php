<?php

namespace App\Http\Resources;

use App\Models\Recarga;
use Carbon\Carbon;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Auth;

class RecargaResumenResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */

    public function totalAusentismosGrupos($recarga)
    {
        $ausentismos_all_grupo  = [];
        $reglas                 = $recarga->whenLoaded('reglas');
        $reglas                 = $reglas->unique('tipo_ausentismo_id');
        if (count($reglas) > 0) {
            foreach ($reglas as $regla) {
                $data_grupo = (object) [
                    'id'      => $regla->tipoAusentismo->id,
                    'nombre'  => $regla->tipoAusentismo->nombre,
                    'sigla'   => strtolower($regla->tipoAusentismo->sigla)
                ];
                array_push($ausentismos_all_grupo, $data_grupo);
            }
        }

        return $ausentismos_all_grupo;
    }

    public function activePlublicar($recarga)
    {
        $usuario_auth       = Auth::user();
        if ($recarga->last_status === 0 && $usuario_auth->hasRole(['ADMIN.SUPER', 'ADMIN.EJECUTIVO'])) {
            return true;
        } else if ($recarga->last_status === 1 && $usuario_auth->hasRole(['ADMIN.SUPER'])) {
            return true;
        }

        return false;
    }

    public function toArray($request)
    {
        setlocale(LC_ALL, "es_ES");
        Carbon::setLocale('es');
        $tz                                 = 'America/Santiago';
        $count_feriados_beneficio           = $this->feriados()->where('active', true)->where('anio', $this->anio_beneficio)->where('mes', $this->mes_beneficio)->count();
        $total_dias_habiles_beneficio       = ($this->total_dias_laborales_beneficio - $count_feriados_beneficio);

        $count_feriados_calculo             = $this->feriados()->where('active', true)->where('anio', $this->anio_calculo)->where('mes', $this->mes_calculo)->count();
        $total_dias_habiles_calculo         = ($this->total_dias_laborales_calculo - $count_feriados_calculo);

        $monto_dia                          = $this->monto_dia != null ? number_format($this->monto_dia, 0, ",", ".") : null;
        $monto_estimado                     = $total_dias_habiles_beneficio * $this->monto_dia;

        $total_pagado_query                 = $this->esquemas()->where('active', true)->sum('monto_total_cancelar');
        $total_calculo_contrato_query       = $this->esquemas()->where('active', true)->sum('calculo_contrato');
        $total_calculo_turno_query          = $this->esquemas()->where('active', true)->sum('calculo_turno');
        $total_calculo_grupo_uno_query      = $this->esquemas()->where('active', true)->sum('calculo_grupo_uno');
        $total_calculo_grupo_dos_query      = $this->esquemas()->where('active', true)->sum('calculo_grupo_dos');
        $total_calculo_grupo_tres_query     = $this->esquemas()->where('active', true)->sum('calculo_grupo_tres');
        $total_calculo_viaticos_query       = $this->esquemas()->where('active', true)->sum('calculo_viaticos');
        $total_pagado                       = "$" . number_format($total_pagado_query, 0, ",", ".");

        return [
            'id'                            => $this->id,
            'codigo'                        => $this->codigo,
            'anio_beneficio'                => $this->anio_beneficio,
            'mes_beneficio'                 => Carbon::createFromDate($this->anio_beneficio, $this->mes_beneficio, '01', $tz)->formatLocalized('%B'),
            'anio_calculo'                  => $this->anio_calculo,
            'mes_calculo'                   => Carbon::createFromDate($this->anio_calculo, $this->mes_calculo, '01', $tz)->formatLocalized('%B'),
            'total_dias_mes_beneficio'      => $this->total_dias_mes_beneficio,
            'total_dias_habiles_beneficio'  => $total_dias_habiles_beneficio,
            'total_dias_mes_calculo'        => $this->total_dias_mes_calculo,
            'total_dias_habiles_calculo'    => $total_dias_habiles_calculo,
            'monto_dia'                     => $this->monto_dia ? "$" . $monto_dia : NULL,
            'value_monto_dia'               => $this->monto_dia,
            'monto_estimado_no_turnante'    => $monto_estimado,
            'monto_estimado_no_turnante_format'    => "$" . number_format($monto_estimado, 0, ",", "."),
            'active'                        => $this->active != true ? false : true,
            'n_funcionarios'                => 0,
            'n_funcionarios_vigentes'       => 0,
            'n_funcionarios_no_vigentes'    => 0,
            'total_pagado'                  => $total_pagado,
            'last_estado'                   => $this->seguimiento()->latest()->with('estado')->first(),
            'date_created_user'             => $this->date_created_user,
            'date_updated_user'             => $this->date_updated_user,
            'disabled_reglas'               => $this->reglas()->count() > 0 ? true : false,
            'users_count'                   => $this->esquemas_count,
            'reajustes_count'               => $this->reajustes_count,
            'contratos_count'               => $this->contratos_count,
            'viaticos_count'                => $this->viaticos_count,
            'ausentismos_count'             => $this->ausentismos_count,
            'asignaciones_count'            => $this->asignaciones_count,
            'total_grupos'                  => $this->totalAusentismosGrupos($this),
            'monto_total'                   => 0,
            'last_status_value'             => $this->last_status,
            'last_status'                   => Recarga::NOM_STATUS[$this->last_status],
            'active_publicar'               => $this->activePlublicar($this),

            /* 'ausentismos'                   => AusentismosResource::collection($this->ausentismos), */
            'establecimiento'               => $this->establecimiento,
            'seguimiento'                   => $this->seguimiento()->with('estado', 'userBy')->orderBy('created_at', 'DESC')->get(),
            'user_created_by'               => $this->userCreatedBy,
            'user_update_by'                => $this->userUpdateBy,
            'total_clp'                     => $this->total_clp ? number_format($this->total_clp, 0, ",", ".") : 0,
            'total_calculo_contrato_query'  => $total_calculo_contrato_query,
            'total_calculo_turno_query'         => $total_calculo_turno_query,
            'total_calculo_grupo_uno_query'     => $total_calculo_grupo_uno_query,
            'total_calculo_grupo_dos_query'     => $total_calculo_grupo_dos_query,
            'total_calculo_grupo_tres_query'    => $total_calculo_grupo_tres_query,
            'total_calculo_viaticos_query'      => $total_calculo_viaticos_query,
        ];
    }
}
