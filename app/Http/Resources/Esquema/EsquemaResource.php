<?php

namespace App\Http\Resources\Esquema;

use App\Models\Esquema;
use Carbon\Carbon;
use Illuminate\Http\Resources\Json\JsonResource;

class EsquemaResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */

    private function errorUno($esquema)
    {
        $is_error = false;

        $total_ausentismos = $esquema->grupo_uno_n_registros + $esquema->grupo_dos_n_registros + $esquema->grupo_tres_n_registros;
        if ((!$esquema->active) && ($esquema->contrato_n_registros > 0 || $total_ausentismos > 0 || $esquema->viaticos_n_registros > 0)) {
            $is_error = true;
        }

        return $is_error;
    }

    private function errores($error_uno)
    {
        $errores = [];
        if ($error_uno) {
            $new_error = (object) [
                'code'          => 1,
                'message'       => $response = Esquema::ERROR_NOM[1],
            ];

            array_push($errores, $new_error);
        }
        return $errores;
    }

    private function advertenciaUno($esquema)
    {
        $is_advertencia = false;

        if ($esquema->fecha_alejamiento) {
            $is_advertencia = true;
        }

        return $is_advertencia;
    }

    private function advertenciaDos($esquema)
    {
        $is_advertencia = false;

        if ($esquema->total_dias_cancelar <= 0) {
            $is_advertencia = true;
        }
        return $is_advertencia;
    }

    private function advertenciaTres($esquema)
    {
        $is_advertencia = false;

        if ($esquema->monto_total_cancelar > $esquema->recarga->monto_estimado) {
            $is_advertencia = true;
        }
        return $is_advertencia;
    }

    private function advertenciaCuatro($esquema)
    {
        $is_advertencia = false;

        if (($esquema->monto_total_cancelar > $esquema->recarga->monto_estimado) && ($esquema->ajustes_dias_n_registros <= 0 && $esquema->ajustes_monto_n_registros <= 0)) {
            $is_advertencia = true;
        }
        return $is_advertencia;
    }

    private function advertenciaCinco($esquema)
    {
        $is_advertencia = false;

        if ($esquema->es_turnante === 1 || $esquema->es_turnante === 3) {
            if ($esquema->total_dias_cancelar > $esquema->calculo_turno) {
                $is_advertencia = true;
            }
        } else if ($esquema->es_turnante === 2) {
            if ($esquema->total_dias_cancelar > $esquema->calculo_contrato) {
                $is_advertencia = true;
            }
        }
        /* switch ($esquema->es_turnante) {
            case 1:
            case 3:
                if ($esquema->calculo_turno > $esquema->total_dias_cancelar) {
                    $is_advertencia = true;
                }
                break;

            case 2:
                if ($esquema->calculo_contrato > $esquema->total_dias_cancelar) {
                    $is_advertencia = true;
                }
                break;
        } */
        return $is_advertencia;
    }
    private function advertencias($adv_1, $adv_2, $adv_3, $adv_4, $adv_5)
    {
        $advertencias = [];
        if ($adv_1) {
            $new_advertencia = (object) [
                'code'          => 1,
                'message'       => Esquema::ADVERTENCIA_NOM[1],
            ];

            array_push($advertencias, $new_advertencia);
        }

        if ($adv_2) {
            $new_advertencia = (object) [
                'code'          => 2,
                'message'       => Esquema::ADVERTENCIA_NOM[2],
            ];

            array_push($advertencias, $new_advertencia);
        }

        if ($adv_3) {
            $new_advertencia = (object) [
                'code'          => 3,
                'message'       => Esquema::ADVERTENCIA_NOM[3],
            ];

            array_push($advertencias, $new_advertencia);
        }

        if ($adv_4) {
            $new_advertencia = (object) [
                'code'          => 4,
                'message'       => Esquema::ADVERTENCIA_NOM[4],
            ];

            array_push($advertencias, $new_advertencia);
        }

        if ($adv_5) {
            $new_advertencia = (object) [
                'code'          => 5,
                'message'       => Esquema::ADVERTENCIA_NOM[5],
            ];

            array_push($advertencias, $new_advertencia);
        }
        return $advertencias;
    }

    public function toArray($request)
    {
        setlocale(LC_ALL, "es_ES");
        Carbon::setLocale('es');
        $tz = 'America/Santiago';

        $monto_total_cancelar_data = (object) [
            'monto_total_cancelar_value'      => $this->monto_total_cancelar,
            'monto_total_cancelar_format'     => "$" . number_format($this->monto_total_cancelar, 0, ",", "."),
        ];

        $total_monto_ajuste     = "$" . number_format($this->total_monto_ajuste, 0, ",", ".");
        $calculo_dias_ajustes   = $this->calculo_dias_ajustes <= 0 ? $this->calculo_dias_ajustes : 0;

        $errores = [];
        $error_uno      = $this->errorUno($this);
        $errores        = $this->errores($error_uno);

        $adv_1          = $this->advertenciaUno($this);
        $adv_2          = $this->advertenciaDos($this);
        $adv_3          = $this->advertenciaTres($this);
        $adv_4          = $this->advertenciaCuatro($this);
        $adv_5          = $this->advertenciaCinco($this);
        $advertencias   = [];
        $advertencias   = $this->advertencias($adv_1, $adv_2, $adv_3, $adv_4, $adv_5);

        return [
            'id'                                                    => $this->id,
            'funcionario_uuid'                                      => $this->funcionario ? $this->funcionario->uuid : null,
            'funcionario_rut_completo'                              => $this->funcionario ? $this->funcionario->rut_completo : null,
            'funcionario_nombre_completo'                           => $this->funcionario ? $this->funcionario->nombre_completo : null,
            'funcionario_apellidos'                                 => $this->funcionario ? $this->funcionario->apellidos : null,

            'recarga_codigo'                                        => $this->recarga->codigo,
            'recarga_establecimiento'                               => $this->recarga->establecimiento->sigla,
            'recarga_anio_beneficio'                                => $this->recarga->anio_beneficio,
            'recarga_mes_beneficio'                                 => Carbon::createFromDate($this->recarga->anio_beneficio, $this->recarga->mes_beneficio, '01', $tz)->locale('es')->monthName,
            'recarga_anio_calculo'                                  => $this->recarga->anio_calculo,
            'recarga_mes_calculo'                                   => Carbon::createFromDate($this->recarga->anio_calculo, $this->recarga->mes_calculo, '01', $tz)->locale('es')->monthName,
            'last_status_value'                                     => $this->recarga->last_status,

            'beneficio'                                             => $this->active,
            'tipo_ingreso'                                          => $this->tipo_ingreso ? 'Manual' : 'Masivo',
            'es_remplazo'                                           => $this->es_remplazo ? 'Si' : 'No',
            'es_turnante'                                           => (int)$this->es_turnante,
            'es_turnante_type'                                      => $this->es_turnante === 1 ? 'warning' : ($this->es_turnante === 2 ? 'primary' : 'danger'),
            'es_turnante_nombre'                                    => Esquema::TURNANTE_NOM[$this->es_turnante],
            'total_dias_contrato'                                   => $this->calculo_contrato,
            'total_turno'                                           => $this->calculo_turno,
            'total_descuento'                                       => $this->calculo_grupo_uno + $this->calculo_grupo_dos + $this->calculo_grupo_tres + $this->calculo_viaticos + $calculo_dias_ajustes,
            'total_monto_ajuste'                                    => $total_monto_ajuste,
            'total_dias_cancelar'                                   => $this->total_dias_cancelar,
            'monto_cancelar'                                        => $monto_total_cancelar_data->monto_total_cancelar_format,
            'total_dias_turno_largo'                                => $this->total_dias_turno_largo,
            'total_dias_turno_nocturno'                             => $this->total_dias_turno_nocturno,
            'total_dias_libres'                                     => $this->total_dias_libres,
            'total_dias_turno_largo_en_periodo_contrato'            => $this->total_dias_turno_largo_en_periodo_contrato,
            'total_dias_turno_nocturno_en_periodo_contrato'         => $this->total_dias_turno_nocturno_en_periodo_contrato,
            'total_dias_libres_en_periodo_contrato'                 => $this->total_dias_libres_en_periodo_contrato,
            'total_turno'                                           => $this->calculo_turno,
            'errores'                                               => $errores,
            'advertencias'                                          => $advertencias,

            'contratos_count'                                       => $this->contratos_count,
            'asistencias_count'                                     => $this->asistencias_count,
            'ausentismos_count'                                     => $this->ausentismos_count,
            'viaticos_count'                                        => $this->viaticos_count,
            'turnos_count'                                          => $this->turnos_count,
            'reajustes_count'                                       => $this->reajustes_count,

            'user_created_by'                                       => $this->userCreatedBy ? $this->userCreatedBy->nombre_completo : null,
            'date_created_by'                                       => $this->date_created_user ? Carbon::parse($this->date_created_user)->format('d-m-Y H:i ')."hrs." : null
        ];
    }
}
