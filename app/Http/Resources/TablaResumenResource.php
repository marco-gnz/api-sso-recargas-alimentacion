<?php

namespace App\Http\Resources;

use App\Models\Ausentismo;
use App\Models\Esquema;
use Carbon\Carbon;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

class TablaResumenResource extends JsonResource
{
    public function totalAusentismosGrupos($recarga, $funcionario)
    {
        $ausentismos_all_grupo  = [];
        $reglas                 = $recarga->reglas;
        $reglas                 = $reglas->unique('tipo_ausentismo_id');
        if (count($reglas) > 0) {
            foreach ($reglas as $regla) {
                $data_grupo = (object) [
                    'id'            => $regla->tipoAusentismo->id,
                    'nombre'        => $regla->tipoAusentismo->nombre,
                    'sigla'         => strtolower($regla->tipoAusentismo->sigla),
                    'total_dias'    => $regla->ausentismos()->where('user_id', $funcionario->id)->where('tipo_ausentismo_id', $regla->tipoAusentismo->id)->sum('total_dias_ausentismo_periodo')
                ];
                array_push($ausentismos_all_grupo, $data_grupo);
            }
        }
        return $ausentismos_all_grupo;
    }

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

        $reglas = $this->recarga->reglas()->with('tipoAusentismo')->orderBy('id', 'desc')->get()->unique('tipo_ausentismo_id');

        $ausentismos_all_grupo_uno   = [];
        $ausentismos_all_grupo_dos   = [];
        $ausentismos_all_grupo_tres  = [];

        foreach ($reglas as $regla) {
            if ($regla->grupo_id === 1) {
                $data = [
                    'nombre' => $regla->tipoAusentismo->nombre,
                    'sigla'  => strtolower($regla->tipoAusentismo->sigla),
                    'total_dias' => DB::table('ausentismos')->where('recarga_id', $this->recarga->id)->where('user_id', $this->funcionario->id)->where('tipo_ausentismo_id', $regla->tipoAusentismo->id)->sum('total_dias_ausentismo_periodo')
                ];
                array_push($ausentismos_all_grupo_uno, $data);
            } else if ($regla->grupo_id === 2) {
                $data = (object) [
                    'nombre' => $regla->tipoAusentismo->nombre,
                    'sigla'  => strtolower($regla->tipoAusentismo->sigla),
                    'total_dias' => DB::table('ausentismos')->where('recarga_id', $this->recarga->id)->where('user_id', $this->funcionario->id)->where('tipo_ausentismo_id', $regla->tipoAusentismo->id)->sum('total_dias_ausentismo_periodo')
                ];

                array_push($ausentismos_all_grupo_dos, $data);
            } else if ($regla->grupo_id === 3) {
                $data = (object) [
                    'nombre' => $regla->tipoAusentismo->nombre,
                    'sigla'  => strtolower($regla->tipoAusentismo->sigla),
                    'total_dias' => DB::table('ausentismos')->where('recarga_id', $this->recarga->id)->where('user_id', $this->funcionario->id)->where('tipo_ausentismo_id', $regla->tipoAusentismo->id)->sum('total_dias_ausentismo_periodo')
                ];
                array_push($ausentismos_all_grupo_tres, $data);
            }
        }

        $monto_total_cancelar_data = (object) [
            'monto_total_cancelar_value'      => $this->monto_total_cancelar,
            'monto_total_cancelar_format'     => "$" . number_format($this->monto_total_cancelar, 0, ",", "."),
        ];

        $error_uno      = $this->errorUno($this);
        $errores        = $this->errores($error_uno);

        $adv_1          = $this->advertenciaUno($this);
        $adv_2          = $this->advertenciaDos($this);
        $adv_3          = $this->advertenciaTres($this);
        $adv_4          = $this->advertenciaCuatro($this);
        $adv_5          = $this->advertenciaCinco($this);
        $advertencias   = $this->advertencias($adv_1, $adv_2, $adv_3, $adv_4, $adv_5);
        $last_contrato  = $this->contratos()->with('unidad')->orderBy('fecha_termino_periodo', 'DESC')->first() ?? null;

        return [
            'id'                                                    => $this->id,
            'uuid'                                                  => $this->uuid,
            'tipo_ingreso'                                          => $this->tipo_ingreso ? true : false,
            'fecha_alejamiento'                                     => $this->fecha_alejamiento ? true : false,
            'beneficio'                                             => $this->active,
            'es_remplazo'                                           => $this->es_remplazo ? true : false,
            'turno_asignacion'                                      => $this->turno_asignacion ? true : false,
            'es_turnante'                                           => (int)$this->es_turnante,
            'es_turnante_type'                                      => $this->es_turnante === 1 ? 'warning' : ($this->es_turnante === 2 ? 'primary' : 'danger'),
            'es_turnante_nombre'                                    => Esquema::TURNANTE_NOM[$this->es_turnante],
            'es_turnante_value'                                     => $this->es_turnante_value ? true : false,
            'total_dias_contrato'                                   => $this->calculo_contrato,
            'contrato_n_registros'                                  => $this->contrato_n_registros,
            'total_dias_turno_largo_en_periodo_contrato'            => $this->total_dias_turno_largo_en_periodo_contrato,
            'total_dias_turno_nocturno_en_periodo_contrato'         => $this->total_dias_turno_nocturno_en_periodo_contrato,
            'total_dias_libres_en_periodo_contrato'                 => $this->total_dias_libres_en_periodo_contrato,
            'total_turno'                                           => $this->calculo_turno,
            'total_dias_grupo_uno'              => $this->calculo_grupo_uno,
            'total_dias_habiles_grupo_uno'      => $this->total_dias_habiles_grupo_uno,
            'total_dias_feriados_grupo_uno'     => $this->total_dias_feriados_grupo_uno,
            'grupo_uno_n_registros'             => $this->grupo_uno_n_registros,
            'total_dias_grupo_dos'              => $this->calculo_grupo_dos,
            'total_dias_habiles_grupo_dos'      => $this->total_dias_habiles_grupo_dos,
            'total_dias_feriados_grupo_dos'     => $this->total_dias_feriados_grupo_dos,
            'grupo_dos_n_registros'             => $this->grupo_dos_n_registros,
            'total_dias_grupo_tres'             => $this->calculo_grupo_tres,
            'total_dias_habiles_grupo_tres'     => $this->total_dias_habiles_grupo_tres,
            'total_dias_feriados_grupo_tres'    => $this->total_dias_feriados_grupo_tres,
            'grupo_tres_n_registros'            => $this->grupo_tres_n_registros,
            'calculo_viaticos'                  => $this->calculo_viaticos,
            'viaticos_n_registros'              => $this->viaticos_n_registros,
            'calculo_dias_ajustes'              => $this->calculo_dias_ajustes,
            'ajustes_dias_n_registros'          => $this->ajustes_dias_n_registros,
            'total_monto_ajuste'                => number_format($this->total_monto_ajuste, 0, ",", "."),
            'ajustes_monto_n_registros'         => $this->ajustes_monto_n_registros,
            'funcionario_rut_completo'          => $this->funcionario ? $this->funcionario->funcionario_rut_completo : null,
            'funcionario_nombre_completo'       => $this->funcionario ? $this->funcionario->nombre_completo : null,
            'funcionario_nombre'                => $this->funcionario ? $this->funcionario->nombres : null,
            'funcionario_apellidos'             => $this->funcionario ? $this->funcionario->apellidos : null,
            'recarga_codigo'                    => $this->recarga ? $this->recarga->codigo : null,
            'grupo_uno'                         => [],
            'unidad_nom'                        => ($last_contrato) && ($last_contrato->unidad) ? $last_contrato->unidad->nombre : null,
            'unidad_abre'                       => ($last_contrato) && ($last_contrato->unidad) ? substr($last_contrato->unidad->nombre, 0, 7) : null,
            'c_costo'                           => $last_contrato ? $last_contrato->centro_costo : null,
            'total_dias_cancelar'               => $this->total_dias_cancelar,
            'monto_cancelar'                    => $monto_total_cancelar_data,
            'ausentismos_grupo'                 => $this->totalAusentismosGrupos($this->recarga, $this->funcionario),
            'errores'                           => $errores,
            'advertencias'                      => $advertencias
        ];
    }
}
