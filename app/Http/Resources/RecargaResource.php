<?php

namespace App\Http\Resources;

use Carbon\Carbon;
use Illuminate\Http\Resources\Json\JsonResource;

class RecargaResource extends JsonResource
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
        $tz                         = 'America/Santiago';
        return [
            'id'                            => $this->id,
            'codigo'                        => $this->codigo,
            'anio'                          => $this->anio,
            'mes'                           => Carbon::createFromDate($this->anio,$this->mes, '01', $tz)->formatLocalized('%B'),
            'total_dias_mes'                => $this->total_dias_mes,
            'total_dias_habiles'            => $this->total_dias_habiles,
            'monto_dia'                     => $this->monto_dia,
            'active'                        => $this->active != true ? false : true,
            'n_funcionarios'                => 0,
            'n_funcionarios_vigentes'       => 0,
            'n_funcionarios_no_vigentes'    => 0,
            'total_pagado'                  => 0,
            'last_estado'                   => $this->seguimiento()->latest()->with('estado')->first(),
            'date_created_user'             => $this->date_created_user,
            'date_updated_user'             => $this->date_updated_user,
            'disabled_reglas'               => $this->reglas()->count() > 0 ? true : false,
            'users_count'                   => $this->users_count,

            'establecimiento'               => $this->establecimiento,
            'seguimiento'                   => $this->seguimiento()->with('estado', 'userBy')->orderBy('created_at', 'DESC')->get(),
            'reglas'                        => $this->reglas,
            'user_created_by'               => $this->userCreatedBy,
            'user_update_by'                => $this->userUpdateBy

        ];
    }
}
