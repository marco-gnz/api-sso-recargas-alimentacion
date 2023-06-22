<?php

namespace App\Http\Controllers\Recarga;

use App\Events\CartolaEnviadaMasivo;
use App\Exports\Esquemas\EsquemasPlanillaExport;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Export\PlanillaExportRequest;
use App\Http\Resources\RecargaResumenResource;
use App\Models\Esquema;
use App\Models\Recarga;
use App\Models\RecargaEstado;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Str;

class StatusRecargaController extends Controller
{
    public function sendEmailsCartola($codigo)
    {
        try {
            $recarga        = Recarga::where('codigo', $codigo)->firstOrFail();
            $total_to_email = 0;
            if ($recarga) {
                $esquemas = $recarga->esquemas()->where('active', true)->where('monto_total_cancelar', '>', 0)->get();

                if (count($esquemas) > 0) {
                    foreach ($esquemas as $esquema) {
                        if (($esquema->funcionario) && ($esquema->funcionario->email)) {
                            $total_to_email++;
                            CartolaEnviadaMasivo::dispatch($esquema);
                        }
                    }
                    return response()->json([
                        'status'         => 'Success',
                        'title'          => "Ok!",
                        'message'        => "{$total_to_email} cartolas agregadas en cola para enviar.",
                    ]);
                } else {
                    return response()->json([
                        'status'         => 'Error',
                        'title'          => 'No se encontraton cartolas.',
                        'message'        => null,
                    ]);
                }
            }
        } catch (\Exception $error) {
            return response()->json($error->getMessage());
        }
    }

    public function publicarRecarga($codigo)
    {
        try {
            $recarga = Recarga::where('codigo', $codigo)->firstOrFail();

            if ($recarga) {
                $esquemas       = $recarga->esquemas;
                $alertas        = $this->searchAlertas($esquemas);
                $others_alertas = $this->othersAlertas($recarga);

                return response()->json([
                    'status'         => 'Success',
                    'title'          => null,
                    'message'        => null,
                    'errores'        => $alertas->errores,
                    'advertencias'   => $alertas->advertencias,
                    'others_alertas' => $others_alertas->data
                ]);
            }
        } catch (\Exception $error) {
            return response()->json($error->getMessage());
        }
    }

    private function camposPlanilla()
    {
        $campos = [
            (object) [
                'slug'       => 'users.rut',
                'id'         => 'rut',
                'nombre'     => 'Rut funcionario',
            ],
            (object) [
                'slug'       => 'users.dv',
                'id'         => 'dv',
                'nombre'     => 'DV funcionario',
            ],
            (object) [
                'slug'       => 'users.rut_completo',
                'id'         => 'rut_completo',
                'nombre'     => 'Rut completo funcionario',
            ],
            (object) [
                'slug'       => 'users.nombres',
                'id'         => 'nombres',
                'nombre'     => 'Nombre funcionario',
            ],
            (object) [
                'slug'       => 'users.apellidos',
                'id'         => 'apellidos',
                'nombre'     => 'Apellidos funcionario',
            ],
            (object) [
                'slug'       => 'users.email',
                'id'         => 'email',
                'nombre'     => 'Email funcionario',
            ],
            (object) [
                'slug'       => 'esquemas.es_turnante',
                'id'         => 'es_turnante',
                'nombre'     => 'Turno',
            ],
            (object) [
                'slug'       => 'esquemas.calculo_contrato',
                'id'         => 'calculo_contrato',
                'nombre'     => 'Total días de contrato',
            ],
            (object) [
                'slug'       => 'esquemas.fecha_alejamiento',
                'id'         => 'fecha_alejamiento',
                'nombre'     => 'Fecha de alejamiento',
            ],
            (object) [
                'slug'       => 'esquemas.total_dias_turno_largo',
                'id'         => 'total_dias_turno_largo',
                'nombre'     => 'Total días turno largo',
            ],
            (object) [
                'slug'       => 'esquemas.total_dias_turno_nocturno',
                'id'         => 'total_dias_turno_nocturno',
                'nombre'     => 'Total días turno nocturno',
            ],
            (object) [
                'slug'       => 'esquemas.total_dias_libres',
                'id'         => 'total_dias_libres',
                'nombre'     => 'Total días libres',
            ],
            (object) [
                'slug'       => 'esquemas.total_dias_turno_largo_en_periodo_contrato',
                'id'         => 'total_dias_turno_largo_en_periodo_contrato',
                'nombre'     => 'Total días turno largo en contrato',
            ],
            (object) [
                'slug'       => 'esquemas.total_dias_turno_nocturno_en_periodo_contrato',
                'id'         => 'total_dias_turno_nocturno_en_periodo_contrato',
                'nombre'     => 'Total días turno nocturno en contrato',
            ],
            (object) [
                'slug'       => 'esquemas.total_dias_libres_en_periodo_contrato',
                'id'         => 'total_dias_libres_en_periodo_contrato',
                'nombre'     => 'Total días libres en contrato',
            ],
            (object) [
                'slug'       => 'esquemas.calculo_turno',
                'id'         => 'calculo_turno',
                'nombre'     => 'Total días turno',
            ],
            (object) [
                'slug'       => 'esquemas.calculo_grupo_uno',
                'id'         => 'calculo_grupo_uno',
                'nombre'     => 'Total días grupo 1',
            ],
            (object) [
                'slug'       => 'esquemas.ausentismos_grupo_uno',
                'id'         => 'ausentismos_grupo_uno',
                'nombre'     => 'Ausentismos grupo 1',
            ],
            (object) [
                'slug'       => 'esquemas.calculo_grupo_dos',
                'id'         => 'calculo_grupo_dos',
                'nombre'     => 'Total días grupo 2',
            ],
            (object) [
                'slug'       => 'esquemas.ausentismos_grupo_dos',
                'id'         => 'ausentismos_grupo_dos',
                'nombre'     => 'Ausentismos grupo 2',
            ],
            (object) [
                'slug'       => 'esquemas.calculo_grupo_tres',
                'id'         => 'calculo_grupo_tres',
                'nombre'     => 'Total días grupo 3',
            ],
            (object) [
                'slug'       => 'esquemas.ausentismos_grupo_tres',
                'id'         => 'ausentismos_grupo_tres',
                'nombre'     => 'Ausentismos grupo 3',
            ],
            (object) [
                'slug'       => 'esquemas.calculo_viaticos',
                'id'         => 'calculo_viaticos',
                'nombre'     => 'Total días viáticos',
            ],
            (object) [
                'slug'       => 'esquemas.calculo_dias_ajustes',
                'id'         => 'calculo_dias_ajustes',
                'nombre'     => 'Total días ajustes',
            ],
            (object) [
                'slug'       => 'esquemas.total_monto_ajuste',
                'id'         => 'total_monto_ajuste',
                'nombre'     => 'Total monto ajustes',
            ],
            (object) [
                'slug'       => 'esquemas.total_dias_cancelar',
                'id'         => 'total_dias_cancelar',
                'nombre'     => 'Total días a cancelar',
            ],
            (object) [
                'slug'       => 'esquemas.monto_total_cancelar',
                'id'         => 'monto_total_cancelar',
                'nombre'     => 'Total monto a cancelar',
            ],
        ];

        return $campos;
    }

    public function generarPlanilla($codigo)
    {
        try {
            $recarga = Recarga::where('codigo', $codigo)->firstOrFail();

            if ($recarga) {
                $campos = $this->camposPlanilla();

                return response()->json([
                    'status'        => 'Success',
                    'title'         => null,
                    'message'       => null,
                    'campos'        => $campos,
                ]);
            }
        } catch (\Exception $error) {
            return response()->json($error->getMessage());
        }
    }

    private function withRecarga()
    {
        $with = [
            'seguimiento',
            'reglas.grupoAusentismo',
            'reglas.tipoAusentismo',
            'reglas.meridianos',
            'establecimiento',
            'userCreatedBy',
            'userUpdateBy',
            'users',
            'contratos',
            'viaticos',
            'reajustes'
        ];

        return $with;
    }

    public function withFnReglas($load_grupo)
    {
        $load_grupo = (int)$load_grupo;
        $function   = ['reglas' => function ($query) use ($load_grupo) {
            $query->where('grupo_id', $load_grupo)->get();
        }];
        return $function;
    }

    public function publicarRecargaAction($codigo)
    {
        try {
            $with           = $this->withRecarga();
            $withFnReglas   = $this->withFnReglas(1);
            $recarga        = Recarga::with($with)->with($withFnReglas)->where('codigo', $codigo)->firstOrFail();

            if ($recarga) {
                $esquemas = $recarga->esquemas;
                $others_alertas = $this->othersAlertas($recarga);

                $message = null;
                $update = null;

                if ($others_alertas->value) {
                    $message = 'No es posible publicar. Existen alertas pendientes.';
                } else {
                    if ($recarga->last_status === 0) {
                        $update = $recarga->update([
                            'last_status' => Recarga::STATUS_PUBLICADA_EJECUTIVO
                        ]);
                    } else if ($recarga->last_status === 1) {
                        $update = $recarga->update([
                            'last_status' => Recarga::STATUS_PUBLICADA_SUPER_ADMIN
                        ]);
                    }

                    if ($update) {
                        $message = 'Recarga publicada.';

                        $new_status = RecargaEstado::create([
                            'status' => $recarga->last_status,
                            'recarga_id' => $recarga->id
                        ]);
                    }
                }

                $recarga->{'total_clp'} = $esquemas->sum('monto_total_cancelar');

                return response()->json([
                    'status' => 'Success',
                    'title' => null,
                    'message' => $message,
                    'value' => $others_alertas->value,
                    'recarga' => RecargaResumenResource::make($recarga),
                ]);
            }
        } catch (\Exception $error) {
            return response()->json($error->getMessage());
        }
    }


    public function generarPlanillaAction(PlanillaExportRequest $request)
    {
        try {
            $recarga = Recarga::where('codigo', $request->codigo)->firstOrFail();
            $name_field = 'planilla_resumen.xlsx';
            return Excel::download(new EsquemasPlanillaExport($recarga->id, $request->campos_id, $request->campos_slug, $request->campos_nombre), $name_field);
        } catch (\Exception $error) {
            return response()->json($error->getMessage());
        }
    }

    private function othersAlertas($recarga)
    {
        $others_alertas     = [];
        $ajustes_pendientes = $recarga->reajustes()->where('last_status', 0)->count();
        $contratos          = $recarga->contratos()->count();
        $ausentismos        = $recarga->ausentismos()->count();
        $viaticos           = $recarga->viaticos()->count();
        $asignaciones       = $recarga->asignaciones()->count();
        $asistencias        = $recarga->asistencias()->count();

        if ($ajustes_pendientes > 0) {
            $alerta_ajustes_pendiente = (object) [
                'message' => "EXISTEN AJUSTES PENDIENTES POR VALIDAR ({$ajustes_pendientes}).",
            ];
            array_push($others_alertas, $alerta_ajustes_pendiente);
        }

        if ($contratos <= 0) {
            $alerta_ajustes_pendiente = (object) [
                'message' => "NO EXISTEN CONTRATOS",
            ];
            array_push($others_alertas, $alerta_ajustes_pendiente);
        }

        if ($ausentismos <= 0) {
            $alerta_ausentismos = (object) [
                'message' => "NO EXISTEN AUSENTISMOS",
            ];
            array_push($others_alertas, $alerta_ausentismos);
        }

        if ($viaticos <= 0) {
            $alerta_viaticos = (object) [
                'message' => "NO EXISTEN VIÁTICOS",
            ];
            array_push($others_alertas, $alerta_viaticos);
        }

        if ($asignaciones <= 0) {
            $alerta_asignaciones = (object) [
                'message' => "NO EXISTEN ASIGNACIONES",
            ];
            array_push($others_alertas, $alerta_asignaciones);
        }

        if ($asistencias <= 0) {
            $alerta_asistencias = (object) [
                'message' => "NO EXISTEN TURNOS DE ASISTENCIA",
            ];
            array_push($others_alertas, $alerta_asistencias);
        }

        $data = (object) [
            'value'          => count($others_alertas) > 0 ? true : false,
            'data'           => $others_alertas
        ];

        return $data;
    }

    private function searchAlertas($esquemas)
    {
        try {
            $errores_search = [];
            $advertencias_search = [];

            foreach ($esquemas as $esquema) {
                $error_uno = $this->errorUno($esquema);
                $error_dos = $this->errorDos($esquema);
                $errores = $this->errores($error_uno, $error_dos);

                if (!empty($errores)) {
                    foreach ($errores as $error) {
                        $errores_search[] = $error;
                    }
                }

                $adv_1 = $this->advertenciaUno($esquema);
                $adv_2 = $this->advertenciaDos($esquema);
                $adv_3 = $this->advertenciaTres($esquema);
                $adv_4 = $this->advertenciaCuatro($esquema);
                $adv_5 = $this->advertenciaCinco($esquema);
                $advertencias = $this->advertencias($adv_1, $adv_2, $adv_3, $adv_4, $adv_5);

                if (!empty($advertencias)) {
                    foreach ($advertencias as $advertencia) {
                        $advertencias_search[] = $advertencia;
                    }
                }
            }

            if (!empty($errores_search)) {
                $errores_unique = collect($errores_search)->unique('code')->values()->all();
            } else {
                $errores_unique = [];
            }

            if (!empty($advertencias_search)) {
                $advertencias_unique = collect($advertencias_search)->unique('code')->values()->all();
            } else {
                $advertencias_unique = [];
            }

            $alertas = (object) [
                'errores' => $errores_unique,
                'advertencias' => $advertencias_unique
            ];

            return $alertas;
        } catch (\Exception $error) {
            return $error->getMessage();
        }
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

    private function errorUno($esquema)
    {
        $is_error = false;

        $total_ausentismos = $esquema->grupo_uno_n_registros + $esquema->grupo_dos_n_registros + $esquema->grupo_tres_n_registros;
        if ((!$esquema->active) && ($esquema->contrato_n_registros > 0 || $total_ausentismos > 0 || $esquema->viaticos_n_registros > 0)) {
            $is_error = true;
        }

        return $is_error;
    }

    private function errorDos($esquema)
    {
        $is_error = false;

        if ($esquema->es_turnante === 3) {
            $is_error = true;
        }

        return $is_error;
    }

    private function errores($error_uno, $error_dos)
    {
        $errores = [];
        if ($error_uno) {
            $new_error = (object) [
                'code'          => 1,
                'message'       => $response = Esquema::ERROR_NOM[1],
            ];

            array_push($errores, $new_error);
        }

        if ($error_dos) {
            $new_error = (object) [
                'code'          => 3,
                'message'       => $response = Esquema::ERROR_NOM[3],
            ];

            array_push($errores, $new_error);
        }
        return $errores;
    }

    public function eliminarCarga($codigo)
    {
        $recarga = Recarga::where('codigo', $codigo)->firstOrFail();

        if ($recarga) {
            $total_no_turnantes_pa = 0;
            $total_turnantes_pa = 0;

            $total_no_turnantes_pg = 0;
            $total_turnantes_pg = 0;

            $esquemas_no_turnantes  = $recarga->esquemas()->where('es_turnante', 2)->where('active', true)->get();
            $esquemas_turnantes     = $recarga->esquemas()->whereIn('es_turnante', [1, 3])->where('active', true)->get();

            foreach ($esquemas_no_turnantes as $esquema) {
                $total_no_turnantes_pa += $esquema->ausentismos()->where('grupo_id', 2)
                    ->where('tipo_ausentismo_id', 15)
                    ->where(function ($query) {
                        $query->whereIn('meridiano_id', [2, 3]);
                    })->sum('total_dias_habiles_ausentismo_periodo');

                $total_no_turnantes_pg += $esquema->ausentismos()->where('grupo_id', 2)
                    ->where('tipo_ausentismo_id', 13)
                    ->where(function ($query) {
                        $query->whereIn('meridiano_id', [2, 3]);
                    })->sum('total_dias_habiles_ausentismo_periodo');
            }

            foreach ($esquemas_turnantes as $esquema) {
                $total_turnantes_pa += $esquema->ausentismos()->where('grupo_id', 2)
                    ->where('tipo_ausentismo_id', 15)
                    ->where(function ($query) {
                        $query->whereIn('meridiano_id', [2, 3]);
                    })->sum('total_dias_ausentismo_periodo');

                $total_turnantes_pg += $esquema->ausentismos()->where('grupo_id', 2)
                    ->where('tipo_ausentismo_id', 13)
                    ->where(function ($query) {
                        $query->whereIn('meridiano_id', [2, 3]);
                    })->sum('total_dias_ausentismo_periodo');
            }

            $total_pa = $total_no_turnantes_pa  + $total_turnantes_pa;
            $total_pg = $total_no_turnantes_pg  + $total_turnantes_pg;

            $response = (object) [
                'total_pa'          => $total_pa,
                'total_pg'          => $total_pg,
            ];


            return $response;
        }
    }
}
