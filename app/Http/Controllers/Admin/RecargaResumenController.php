<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Recargas\BeneficioUserRequest;
use App\Http\Resources\FuncionariosResumenResource;
use App\Http\Resources\RecargaResumenResource;
use App\Http\Resources\TablaResumenResource;
use App\Models\Ausentismo;
use App\Models\Recarga;
use App\Models\User;
use Illuminate\Http\Request;
use App\Http\Controllers\Admin\Calculos\PagoBeneficioController;
use App\Http\Resources\FuncionarioAusentismosResource;
use App\Http\Resources\FuncionarioContratosResource;
use App\Http\Resources\FuncionarioTurnosResource;
use App\Http\Resources\FuncionarioViaticosResource;
use App\Models\Esquema;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Admin\Calculos\ActualizarEsquemaController;

class RecargaResumenController extends Controller
{
    public function __construct(PagoBeneficioController $PagoBeneficioController)
    {
        /* $this->middleware(['auth:sanctum']); */
        $this->PagoBeneficioController = $PagoBeneficioController;
    }

    protected function successResponse($data, $title = null, $message = null, $code = 200)
    {
        return response()->json([
            'status'    => 'Success',
            'title'     => $title,
            'message'   => $message,
            'data'      => $data
        ], $code);
    }

    protected function errorResponse($message = null, $code)
    {
        return response()->json([
            'status'    => 'Error',
            'message'   => $message,
            'data'      => null
        ], $code);
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

    public function returnFindRecarga($codigo)
    {
        try {
            $with = $this->withRecarga();
            $recarga = Recarga::where('codigo', $codigo)->withCount('users')->withCount('ausentismos')->withCount('asignaciones')->withCount('reajustes')->withCount('contratos')->withCount('viaticos')->withCount('esquemas')->with($with)->first();

            if ($recarga) {
                return $this->successResponse(RecargaResumenResource::make($recarga), null, null, 200);
            } else {
                return $this->errorResponse('No existen registros.', 404);
            }
        } catch (\Exception $error) {
            return $error->getMessage();
            /* return $this->errorResponse($error->getMessage(), 500); */
        }
    }

    public function withFnAusentismos($recarga)
    {
        $function = ['ausentismos' => function ($query) use ($recarga) {
            $query->where('recarga_id', $recarga->id)->get();
        }];
        return $function;
    }

    public function withFnContratos($recarga)
    {
        $function = ['contratos' => function ($query) use ($recarga) {
            $query->where('recarga_id', $recarga->id)->get();
        }];
        return $function;
    }

    public function withFnAsistencias($recarga)
    {
        $function = ['asistencias' => function ($query) use ($recarga) {
            $query->where('recarga_id', $recarga->id)->get();
        }];
        return $function;
    }

    public function withFnAjustes($recarga)
    {
        $function = ['reajustes' => function ($query) use ($recarga) {
            $query->where('recarga_id', $recarga->id)->get();
        }];
        return $function;
    }

    public function withFnTurnos($recarga)
    {
        $function = ['turnos' => function ($query) use ($recarga) {
            $query->where('recarga_id', $recarga->id)->get();
        }];
        return $function;
    }

    public function withFnViaticos($recarga)
    {
        $function = ['viaticos' => function ($query) use ($recarga) {
            $query->where('recarga_id', $recarga->id)->get();
        }];
        return $function;
    }

    public function withFnReglas($load_grupo)
    {
        $load_grupo = (int)$load_grupo;
        $function   = ['reglas' => function ($query) use ($load_grupo) {
            $query->where('grupo_id', $load_grupo)->get();
        }];
        return $function;
    }

    public function withReReglas($load_grupo)
    {
        $load_grupo = (int)$load_grupo;
        $function   = ['recarga.reglas' => function ($query) use ($load_grupo) {
            $query->where('grupo_id', $load_grupo)->get();
        }];
        return $function;
    }

    public function withFnRecargas($recarga)
    {
        $function = ['recargas' => function ($query) use ($recarga) {
            $query->where('recarga_user.recarga_id', $recarga->id);
        }];
        return $function;
    }

    private function withFnAusentismosCartola($recarga)
    {
        $function = ['funcionario.ausentismos' => function ($query) use ($recarga) {
            $query->where('recarga_id', $recarga->id);
        }];
        return $function;
    }

    public function returnFuncionariosToRecarga($codigo, Request $request)
    {
        try {
            $new_users              = [];
            $funcionarios           = [];
            $total_clp              = 0;
            $input_query            = $request->input;
            $beneficio              = $request->beneficio;
            $reemplazo              = $request->reemplazo;
            $tipo_ingreso           = $request->tipo_ingreso;
            $fecha_alejamiento      = $request->fecha_alejamiento;
            $turno                  = $request->is_turno;
            $ajustes                = $request->ajustes;
            $exceptions             = $request->errores;
            $opcionesOrdenamiento   = $request->ordenamiento;
            $with                   = $this->withRecarga();
            $withFnReglas           = $this->withFnReglas($request->load_grupo);
            $recarga                = Recarga::with($with)
                ->where('codigo', $codigo)
                ->with($withFnReglas)
                ->withCount('users')
                ->withCount('ausentismos')
                ->withCount('asignaciones')
                ->withCount('reajustes')
                ->withCount('contratos')
                ->withCount('viaticos')
                ->withCount('esquemas')
                ->first();

            $withFnAusentismos      = $this->withFnAusentismos($recarga);
            $withFnContratos        = $this->withFnContratos($recarga);
            $withFnAsistencias      = $this->withFnAsistencias($recarga);
            $withFnAjustes          = $this->withFnAjustes($recarga);
            $withFnTurnos           = $this->withFnTurnos($recarga);
            $withFnViaticos         = $this->withFnViaticos($recarga);
            $withFnRecargas         = $this->withFnRecargas($recarga);

            $withFnAusentismosCartola = $this->withFnAusentismosCartola($recarga);
            $withReReglas             = $this->withReReglas($request->load_grupo);

            $equals   = $request->equals_unidad === 'true' ? true : false;
            $esquemas = $recarga->esquemas()
                ->input($input_query)
                ->beneficio($beneficio)
                ->reemplazo($reemplazo)
                ->tipoIngreso($tipo_ingreso)
                ->fechaAlejamiento($fecha_alejamiento)
                ->turnoAsignaciones($request->turno_asignaciones)
                ->esTurnante($turno)
                ->ajustesEnRecarga($ajustes, $recarga->id)
                ->advertencias($exceptions, $recarga)
                ->leyContrato($request->leyes)
                ->unidadContrato($request->unidades, $equals)
                ->horaContrato($request->horas)
                ->tipoAusentismo($request->tipo_ausentismo)
                ->centroCostoContrato($request->centro_costo)
                ->descuentoTurnoLibre($request->descuento_turno_libre)
                ->with($withReReglas);

            $ordenamientos = $request->ordenamiento;

            if ($request->ordenamiento) {
                $decodificado = array_map(function ($ordenamientos) {
                    return json_decode($ordenamientos, true);
                }, $ordenamientos);
            }
            if ($request->ordenamiento) {
                foreach ($decodificado as $ordenamiento) {
                    $index      = $ordenamiento['index'];
                    $campo      = $ordenamiento['nombre'];
                    $tipoOrden  = $ordenamiento['order'];
                    $index      = (int)$index;
                    if ($index === 1) {
                        $esquemas->join('users', 'esquemas.user_id', '=', 'users.id')
                            ->orderBy('users.apellidos', $tipoOrden);
                    } else {
                        $esquemas->orderBy($campo, $tipoOrden);
                    }
                }
            }
            $esquemas = $esquemas->paginate(30);
            $recarga->{'total_clp'} = $esquemas->sum('monto_total_cancelar');

            return response()->json(
                array(
                    'status'    => 'Success',
                    'title'     => null,
                    'message'   => null,
                    'pagination' => [
                        'total'         => $esquemas->total(),
                        'current_page'  => $esquemas->currentPage(),
                        'per_page'      => $esquemas->perPage(),
                        'last_page'     => $esquemas->lastPage(),
                        'from'          => $esquemas->firstItem(),
                        'to'            => $esquemas->lastPage()
                    ],
                    'users'     => TablaResumenResource::collection($esquemas),
                    'recarga'   => RecargaResumenResource::make($recarga)
                )
            );
        } catch (\Exception $error) {
            return $error->getMessage();
        }
    }

    public function changeBeneficioToUser(BeneficioUserRequest $request)
    {
        try {
            $with           = $this->withRecarga();
            $withFnReglas   = $this->withFnReglas($request->load_grupo);
            $recarga        = Recarga::where('codigo', $request->codigo_recarga)->with($withFnReglas)->withCount('users')->withCount('ausentismos')->withCount('asignaciones')->withCount('reajustes')->withCount('contratos')->withCount('viaticos')->withCount('esquemas')->first();

            $esquema = Esquema::find($request->user_id);

            if ($esquema) {
                $update = $esquema->update(['active' => !$esquema->active]);

                if ($update) {
                    $esquema        = $esquema->fresh();
                    $status         = $esquema->active ? 'habilitado' : 'deshabilitado';
                    $status_count   = $esquema->active ? 'Será ' : 'No será';
                    $title          = 'Esquema modificado';
                    $message        =  "Se ha {$status} el beneficio a {$esquema->funcionario->nombre_completo}. {$status_count} contabilizado en el monto total.";
                    return response()->json(
                        array(
                            'status'    => 'Success',
                            'title'     => $title,
                            'message'   => $message,
                            'user'      => TablaResumenResource::make($esquema),
                            'recarga'   => RecargaResumenResource::make($recarga)
                        )
                    );
                }
            }
        } catch (\Exception $error) {
            return $error->getMessage();
        }
    }

    public function changeTurnoToUser(BeneficioUserRequest $request)
    {
        try {
            $with           = $this->withRecarga();
            $withFnReglas   = $this->withFnReglas($request->load_grupo);
            $recarga        = Recarga::where('codigo', $request->codigo_recarga)->with($withFnReglas)->withCount('users')->withCount('ausentismos')->withCount('asignaciones')->withCount('reajustes')->withCount('contratos')->withCount('viaticos')->withCount('esquemas')->first();

            $esquema = Esquema::find($request->user_id);

            if ($esquema) {
                $update  = $esquema->update(['es_turnante_value' => !$esquema->es_turnante_value]);
                $esquema = $esquema->fresh();


                if ($update) {
                    $cartola_controller = new ActualizarEsquemaController;

                    $update_esquema_1 = $cartola_controller->updateAusentismosGrupoUno($esquema->funcionario, $esquema->recarga, 1);
                    $update_esquema_2 = $cartola_controller->updateAusentismosGrupoDos($esquema->funcionario, $esquema->recarga, 2);
                    $update_esquema_3 = $cartola_controller->updateAusentismosGrupoTres($esquema->funcionario, $esquema->recarga, 3);
                    $update_esquema_4 = $cartola_controller->updateEsquemaViaticos($esquema->funcionario, $esquema->recarga, 3);


                    $esquema = $esquema->fresh();

                    $data_turnante = [
                        'calculo_contrato'   => $esquema->total_dias_contrato,
                        'calculo_grupo_uno'  => $update_esquema_1->total_dias_grupo_uno,
                        'calculo_grupo_dos'  => $update_esquema_2->total_dias_grupo_dos,
                        'calculo_grupo_tres' => $update_esquema_3->total_dias_grupo_tres,
                        'calculo_viaticos'   => $update_esquema_4->calculo_viaticos
                    ];

                    $data_no_turnante = [
                        'calculo_contrato'   => $esquema->total_dias_habiles_contrato,
                        'calculo_grupo_uno'  => $update_esquema_1->total_dias_habiles_grupo_uno,
                        'calculo_grupo_dos'  => $update_esquema_2->total_dias_habiles_grupo_dos,
                        'calculo_grupo_tres' => $update_esquema_3->total_dias_habiles_grupo_tres,
                        'calculo_viaticos'   => $update_esquema_4->calculo_viaticos
                    ];

                    if ($esquema->es_turnante_value) {
                        $esquema->update($data_turnante);
                    } else {
                        $esquema->update($data_no_turnante);
                    }

                    $esquema        = $esquema->fresh();
                    $status         = $esquema->es_turnante_value ? 'turnante' : 'no turnante';
                    $title          = 'Esquema modificado';
                    $message        =  "Modificado a {$status}";
                    return response()->json(
                        array(
                            'status'    => 'Success',
                            'title'     => $title,
                            'message'   => $message,
                            'user'      => TablaResumenResource::make($esquema),
                            'recarga'   => RecargaResumenResource::make($recarga)
                        )
                    );
                }
            }
        } catch (\Exception $error) {
            return $error->getMessage();
        }
    }

    public function changeReemplazoToUser(BeneficioUserRequest $request)
    {
        try {
            $with           = $this->withRecarga();
            $withFnReglas   = $this->withFnReglas($request->load_grupo);
            $recarga        = Recarga::where('codigo', $request->codigo_recarga)->with($withFnReglas)->withCount('users')->withCount('ausentismos')->withCount('asignaciones')->withCount('reajustes')->withCount('contratos')->withCount('viaticos')->withCount('esquemas')->first();

            $esquema        = Esquema::find($request->user_id);

            if ($esquema) {
                $update = $esquema->update(['es_remplazo' => !$esquema->es_remplazo]);

                if ($update) {
                    $esquema        = $esquema->fresh();
                    $reemplazo_message = $esquema->es_remplazo ? 'es reemplazante' : 'no es reemplazante';
                    $message        =  "Se ha modificado a {$esquema->funcionario->nombre_completo} a " . $reemplazo_message . ".";
                    return response()->json(
                        array(
                            'status'    => 'Success',
                            'title'     => 'Modificado',
                            'message'   => $message,
                            'user'      => TablaResumenResource::make($esquema),
                            'recarga'   => RecargaResumenResource::make($recarga)
                        )
                    );
                }
            }
        } catch (\Exception $error) {
            return $error->getMessage();
        }
    }

    public function updateEsquemasStatus($codigo, Request $request)
    {
        try {
            $with           = $this->withRecarga();
            $withFnReglas   = $this->withFnReglas($request->load_grupo);
            $recarga        = Recarga::where('codigo', $codigo)->with($withFnReglas)->withCount('users')->withCount('ausentismos')->withCount('asignaciones')->withCount('reajustes')->withCount('contratos')->withCount('viaticos')->withCount('esquemas')->first();

            if ($recarga) {
                $esquemas = $recarga->esquemas()->whereIn('id', $request->esquemas_id)->get();

                $status = $request->status ? true : false;
                if (count($esquemas) > 0) {
                    $update = $esquemas->toQuery()->update([
                        'active'    => $status
                    ]);

                    if ($update) {
                        $esquemas = $esquemas->fresh();
                        $total = count($esquemas);
                        return response()->json(
                            array(
                                'status'        => 'Success',
                                'title'         => "{$total} esquemas actualizados",
                                'message'       => null
                            )
                        );
                    }
                }
            }
        } catch (\Exception $error) {
            return $error->getMessage();
        }
    }

    public function searchNotFuncionarios($codigo, Request $request)
    {
        try {
            $recarga = Recarga::where('codigo', $codigo)->firstOrFail();
            if ($recarga) {
                $funcionarios = User::input($request->input)
                    ->whereDoesntHave('esquemas', function ($query) use ($recarga) {
                        $query->where('recarga_id', $recarga->id);
                    })->get();

                return response()->json(
                    array(
                        'status'        => 'Success',
                        'title'         => null,
                        'message'       => null,
                        'funcionarios'  => $funcionarios,
                    )
                );
            }
        } catch (\Exception $error) {
            return $error->getMessage();
        }
    }

    public function getRecargasFuncionarioAdicional($codigo_recarga, $funcionario_id)
    {
        try {
            $recarga        = Recarga::where('codigo', $codigo_recarga)->firstOrFail();
            $funcionario    = User::find($funcionario_id);

            if ($recarga && $funcionario) {
                $columnas  = ['ausentismos', 'asistencias', 'turnos', 'viaticos', 'contratos'];
                $array_ids = [];

                foreach ($columnas as $columna) {
                    $ids = $funcionario->$columna()
                        ->where('recarga_id', '!=', $recarga->id)
                        ->whereNull('esquema_id')
                        ->distinct('recarga_id')
                        ->pluck('recarga_id')
                        ->toArray();

                    $array_ids = array_merge($array_ids, $ids);
                }

                $recargas = Recarga::whereIn('id', $array_ids)->get();

                return response()->json([
                    'status' => 'Success',
                    'title' => null,
                    'message' => null,
                    'recargas' => $recargas,
                ]);
            }
        } catch (\Exception $error) {
            return $error->getMessage();
        }
    }

    public function getDatosContractualesFuncionario($codigos_recarga, $funcionario_id)
    {
        try {
            $codigos_recarga    = (array) $codigos_recarga;
            $recargas_id        = Recarga::whereIn('codigo', $codigos_recarga)->pluck('id')->toArray();
            $funcionario        = User::find($funcionario_id);

            if ($recargas_id && $funcionario) {
                $contratos      = $funcionario->contratos()->whereIn('recarga_id', $recargas_id)->whereNull('esquema_id')->get();
                $asignaciones   = $funcionario->turnos()->whereIn('recarga_id', $recargas_id)->whereNull('esquema_id')->get();
                $asistencias    = $funcionario->asistencias()->whereIn('recarga_id', $recargas_id)->whereNull('esquema_id')
                    ->where('tipo_asistencia_turno_id', 3)->get();
                $ausentismos    = $funcionario->ausentismos()->whereIn('recarga_id', $recargas_id)->whereNull('esquema_id')->get();
                $viaticos       = $funcionario->viaticos()->whereIn('recarga_id', $recargas_id)->whereNull('esquema_id')->get();

                return response()->json([
                    'status'        => 'Success',
                    'title'         => null,
                    'contratos'     => FuncionarioContratosResource::collection($contratos),
                    'ausentismos'   => FuncionarioAusentismosResource::collection($ausentismos),
                    'viaticos'      => FuncionarioViaticosResource::collection($viaticos),
                    'asistencias'   => count($asistencias) > 0 ? $asistencias->pluck('fecha')->implode(', ') : [],
                    'asignaciones'  => FuncionarioTurnosResource::collection($asignaciones)
                ]);
            }
        } catch (\Exception $error) {
            return $error->getMessage();
        }
    }

    public function deleteEsquema($esquema_id)
    {
        try {
            $with           = $this->withRecarga();
            $withFnReglas   = $this->withFnReglas(null);
            $esquema        = Esquema::find($esquema_id);
            $recarga        = Recarga::find($esquema->recarga_id)->with($withFnReglas)->withCount('users')->withCount('ausentismos')->withCount('asignaciones')->withCount('reajustes')->withCount('contratos')->withCount('viaticos')->withCount('esquemas')->first();
            if ($esquema) {
                $reajustes = $esquema->reajustes()->count();

                if ($reajustes > 0) {
                    return response()->json(
                        array(
                            'status'    => 'Error',
                            'title'     => 'Error al eliminar cartola.',
                            'message'   => 'Existen registros asociados a la cartola.',
                        )
                    );
                }
                $delete = $esquema->delete();

                $recarga = $recarga->fresh($with)
                    ->loadCount('users')
                    ->loadCount('ausentismos')
                    ->loadCount('asignaciones')
                    ->loadCount('reajustes')
                    ->loadCount('contratos')
                    ->loadCount('viaticos');

                return response()->json(
                    array(
                        'status'    => 'Success',
                        'title'     => 'Ok!',
                        'message'   => 'Cartola eliminada con éxito.',
                        'recarga'   => RecargaResumenResource::make($recarga)
                    )
                );
            }
        } catch (\Exception $error) {
            return $error->getMessage();
        }
    }

    public function storeManualEsquema(Request $request)
    {
        try {
            $with           = $this->withRecarga();
            $withFnReglas   = $this->withFnReglas($request->load_grupo);
            $recarga        = Recarga::with($with)->with($withFnReglas)->where('codigo', $request->codigo)->withCount('users')->withCount('ausentismos')->withCount('asignaciones')->withCount('reajustes')->withCount('contratos')->withCount('esquemas')->withCount('viaticos')->firstOrFail();
            $funcionario    = User::find($request->funcionario_id);

            if ($recarga && $funcionario) {
                $esquema = Esquema::create([
                    'active'        => true,
                    'es_turnante'   => 2,
                    'user_id'       => $funcionario->id,
                    'recarga_id'    => $recarga->id,
                    'tipo_ingreso'  => 1
                ]);

                if ($esquema) {
                    $codigos_recarga_request    = (array) $request->codigos_recarga;
                    $ids_recarga                = Recarga::whereIn('codigo', $codigos_recarga_request)->pluck('id')->toArray();
                    $this->storeContratos($esquema, $ids_recarga);
                    $this->updateEsquemaAsignaciones($esquema, $ids_recarga);
                    $this->updateAusentismosGrupoUno($esquema, $ids_recarga);
                    $this->updateAusentismosGrupoDos($esquema, $ids_recarga);
                    $this->updateAusentismosGrupoTres($esquema, $ids_recarga);
                    $this->updateEsquemaViaticos($esquema, $ids_recarga);
                    $esquema = $esquema->fresh();
                    $recarga = $recarga->fresh($with)
                        ->loadCount('users')
                        ->loadCount('ausentismos')
                        ->loadCount('asignaciones')
                        ->loadCount('reajustes')
                        ->loadCount('contratos')
                        ->loadCount('viaticos');

                    $message = "{$esquema->funcionario->nombre_completo} ha sido ingresado(a) a la recarga {$recarga->codigo}.";
                    $sync_esquema_recarga = $funcionario->recargas()->attach($recarga->id);
                    return response()->json(
                        array(
                            'status'    => 'Success',
                            'title'     => 'Nuevo esquema ingresado.',
                            'message'   => $message,
                            'esquema'   => TablaResumenResource::make($esquema),
                            'recarga'   => RecargaResumenResource::make($recarga)
                        )
                    );
                }
            }
        } catch (\Exception $error) {
            return $error->getMessage();
        }
    }

    private function storeContratos($esquema, $ids_recarga)
    {
        try {
            $feriados_count = 0;
            $contratos      = $esquema->funcionario->contratos()->where(function ($query) use ($ids_recarga) {
                $query->whereIn('recarga_id', $ids_recarga);
            })->whereNull('esquema_id')->get();

            if ($contratos->count() > 0) {
                $contratos->each(function ($item) use ($esquema) {
                    $item->esquema_id = $esquema->id;
                    $item->save();
                });
            }

            $alejamiento    = $contratos->where('alejamiento', true)->first();

            $total_dias_contrato_periodo            = $contratos->sum('total_dias_contrato_periodo');
            $total_dias_habiles_contrato_periodo    = $contratos->sum('total_dias_habiles_contrato_periodo');

            $total_contrato = $this->totalContrato($esquema, $total_dias_contrato_periodo, $total_dias_habiles_contrato_periodo, $feriados_count);
            $data = [
                'total_dias_contrato'           => $total_dias_contrato_periodo,
                'total_dias_habiles_contrato'   => $total_dias_habiles_contrato_periodo,
                'calculo_contrato'              => $total_contrato,
                'fecha_alejamiento'             => $alejamiento ? true : false,
                'contrato_n_registros'          => count($contratos),
            ];
            $update = $esquema->update($data);
        } catch (\Exception $error) {
            return $error->getMessage();
        }
    }

    private function updateEsquemaAsignaciones($esquema, $ids_recarga)
    {
        try {
            $all_asignaciones = $esquema->funcionario->turnos()->where(function ($query) use ($ids_recarga) {
                $query->whereIn('recarga_id', $ids_recarga)
                    ->whereNull('esquema_id');
            })->get();

            if ($all_asignaciones->count() > 0) {
                $all_asignaciones->each(function ($item) use ($esquema) {
                    $item->esquema_id = $esquema->id;
                    $item->save();
                });
            }

            $turno_asignacion = $esquema->funcionario->turnos()->where(function ($query) use ($ids_recarga) {
                $query->whereIn('recarga_id', $ids_recarga)
                    ->where('es_turnante', true)
                    ->whereNull('esquema_id');
            })->get();

            $update = $esquema->update([
                'turno_asignacion' => $turno_asignacion->count() > 0 ? true : false,
            ]);
        } catch (\Exception $error) {
            return $error->getMessage();
        }
    }

    private function updateAusentismosGrupoUno($esquema, $ids_recarga)
    {
        try {
            $total_dias_grupo_uno           = 0;
            $total_dias_habiles_grupo_uno   = 0;
            $turnante                       = $esquema ? ($esquema->es_turnante_value) : null;

            $all_ausentismos = $esquema->funcionario->ausentismos()
                ->whereIn('recarga_id', $ids_recarga)
                ->whereNull('esquema_id');

            if ($all_ausentismos->count() > 0) {
                $all_ausentismos->each(function ($item) use ($esquema) {
                    $item->esquema_id = $esquema->id;
                    $item->save();
                });
            }
            if ($turnante) {
                $ausentismos_not_tipo_dias = $esquema->funcionario->ausentismos()
                    ->whereIn('recarga_id', $ids_recarga)
                    ->where('grupo_id', 1)
                    ->where('tiene_descuento', true)
                    ->whereHas('regla', function ($query) {
                        $query->whereNull('tipo_dias');
                    })
                    /* ->whereNull('esquema_id') */;

                $ausentismos_naturales = $esquema->funcionario->ausentismos()
                    ->whereIn('recarga_id', $ids_recarga)
                    ->where('grupo_id', 1)
                    ->where('tiene_descuento', true)
                    ->whereHas('regla', function ($query) {
                        $query->where('active_tipo_dias', true)
                            ->where('tipo_dias', false);
                    })
                    /* ->whereNull('esquema_id') */
                    ->sum('total_dias_ausentismo_periodo_turno');

                $ausentismos_habiles = $esquema->funcionario->ausentismos()
                    ->whereIn('recarga_id', $ids_recarga)
                    ->where('grupo_id', 1)
                    ->where('tiene_descuento', true)
                    ->whereHas('regla', function ($query) {
                        $query->where('active_tipo_dias', true)
                            ->where('tipo_dias', true);
                    })
                    /* ->whereNull('esquema_id') */
                    ->sum('total_dias_habiles_ausentismo_periodo_turno');


                $total_tipo_dias                = $ausentismos_naturales + $ausentismos_habiles;
                $total_dias_grupo_uno           = $total_tipo_dias + $ausentismos_not_tipo_dias->sum('total_dias_ausentismo_periodo_turno');
                $total_dias_habiles_grupo_uno   = $total_tipo_dias + $ausentismos_not_tipo_dias->sum('total_dias_habiles_ausentismo_periodo_turno');
            } else {
                $ausentismos_not_tipo_dias = $esquema->funcionario->ausentismos()
                    ->whereIn('recarga_id', $ids_recarga)
                    ->where('grupo_id', 1)
                    ->where('tiene_descuento', true)
                    ->whereHas('regla', function ($query) {
                        $query->whereNull('tipo_dias');
                    })
                    /* ->whereNull('esquema_id') */;

                $ausentismos_naturales = $esquema->funcionario->ausentismos()
                    ->whereIn('recarga_id', $ids_recarga)
                    ->where('grupo_id', 1)
                    ->where('tiene_descuento', true)
                    ->whereHas('regla', function ($query) {
                        $query->where('active_tipo_dias', true)
                            ->where('tipo_dias', false);
                    })
                    /* ->whereNull('esquema_id') */
                    ->sum('total_dias_ausentismo_periodo');

                $ausentismos_habiles = $esquema->funcionario->ausentismos()
                    ->whereIn('recarga_id', $ids_recarga)
                    ->where('grupo_id', 1)
                    ->where('tiene_descuento', true)
                    ->whereHas('regla', function ($query) {
                        $query->where('active_tipo_dias', true)
                            ->where('tipo_dias', true);
                    })
                    /* ->whereNull('esquema_id') */
                    ->sum('total_dias_habiles_ausentismo_periodo');


                $total_tipo_dias                = $ausentismos_naturales + $ausentismos_habiles;
                $total_dias_grupo_uno           = $total_tipo_dias + $ausentismos_not_tipo_dias->sum('total_dias_ausentismo_periodo');
                $total_dias_habiles_grupo_uno   = $total_tipo_dias + $ausentismos_not_tipo_dias->sum('total_dias_habiles_ausentismo_periodo');
            }


            $data = [
                'total_dias_grupo_uno'           => $total_dias_grupo_uno,
                'total_dias_habiles_grupo_uno'   => $total_dias_habiles_grupo_uno,
                'grupo_uno_n_registros'          => $esquema->funcionario->ausentismos()
                    ->whereIn('recarga_id', $ids_recarga)
                    ->where('grupo_id', 1)
                    ->where('tiene_descuento', true)->count()
            ];


            $update  = $esquema->update($data);
            $esquema = $esquema->fresh();

            $total_grupo = $this->totalDiasAusentismoGrupo($esquema, 1);
            $esquema->update([
                'calculo_grupo_uno'              => $total_grupo
            ]);
        } catch (\Exception $error) {
            Log::info($error->getMessage());
            return $error->getMessage();
        }
    }

    private function updateAusentismosGrupoDos($esquema, $ids_recarga)
    {
        try {
            $total_dias_grupo_dos           = 0;
            $total_dias_habiles_grupo_dos   = 0;
            $ausentismos = $esquema->funcionario->ausentismos()
                ->whereIn('recarga_id', $ids_recarga)
                ->where('grupo_id', 2)
                ->where('tiene_descuento', true)
                ->whereNull('esquema_id')
                ->get();

            $turnante                       = $esquema ? ($esquema->es_turnante_value) : null;

            $ausentismos = $ausentismos->each(function ($ausentismo) use ($esquema) {
                $ausentismo->esquema_id = $esquema->id;
                $ausentismo->save();
            });

            $ausentismos = $esquema->funcionario->ausentismos()
                ->whereIn('recarga_id', $ids_recarga)
                ->where('grupo_id', 2)
                ->where('tiene_descuento', true)
                ->get();

            foreach ($ausentismos as $ausentismo) {
                if ($ausentismo->regla) {
                    if ($turnante) {
                        $total_dias_grupo_dos           += $ausentismo->total_dias_ausentismo_periodo_turno;
                        $total_dias_habiles_grupo_dos   += $ausentismo->total_dias_habiles_ausentismo_periodo_turno;
                    } else {
                        $total_dias_grupo_dos           += $ausentismo->total_dias_ausentismo_periodo;
                        $total_dias_habiles_grupo_dos   += $ausentismo->total_dias_habiles_ausentismo_periodo;
                    }
                }
            }
            $data = [
                'total_dias_grupo_dos'           => $total_dias_grupo_dos,
                'total_dias_habiles_grupo_dos'   => $total_dias_habiles_grupo_dos,
                'grupo_dos_n_registros'          => count($ausentismos)
            ];

            $update  = $esquema->update($data);
            $esquema = $esquema->fresh();

            $total_grupo = $this->totalDiasAusentismoGrupo($esquema, 2);

            $esquema->update([
                'calculo_grupo_dos' => $total_grupo
            ]);
        } catch (\Exception $error) {
            return $error->getMessage();
        }
    }

    private function updateAusentismosGrupoTres($esquema, $ids_recarga)
    {
        try {
            $total_dias_grupo_tres           = 0;
            $total_dias_habiles_grupo_tres   = 0;
            $ausentismos = $esquema->funcionario->ausentismos()
                ->whereIn('recarga_id', $ids_recarga)
                ->where('grupo_id', 3)
                ->where('tiene_descuento', true)
                ->whereNull('esquema_id')
                ->get();

            $turnante                       = $esquema ? ($esquema->es_turnante_value) : null;

            $ausentismos->each(function ($ausentismo) use ($esquema) {
                $ausentismo->esquema_id = $esquema->id;
                $ausentismo->save();
            });

            $ausentismos = $esquema->funcionario->ausentismos()
                ->whereIn('recarga_id', $ids_recarga)
                ->where('grupo_id', 3)
                ->where('tiene_descuento', true)
                ->get();

            foreach ($ausentismos as $ausentismo) {
                if ($turnante) {
                    $total_dias_grupo_tres           += $ausentismo->total_dias_ausentismo_periodo_turno;
                    $total_dias_habiles_grupo_tres   += $ausentismo->total_dias_habiles_ausentismo_periodo_turno;
                } else {
                    $total_dias_grupo_tres           += $ausentismo->total_dias_ausentismo_periodo;
                    $total_dias_habiles_grupo_tres   += $ausentismo->total_dias_habiles_ausentismo_periodo;
                }
            }

            $data = [
                'total_dias_grupo_tres'           => $total_dias_grupo_tres,
                'total_dias_habiles_grupo_tres'   => $total_dias_habiles_grupo_tres,
                'grupo_tres_n_registros'          => count($ausentismos)

            ];
            $update  = $esquema->update($data);
            $esquema = $esquema->fresh();

            $total_grupo = $this->totalDiasAusentismoGrupo($esquema, 3);
            $esquema->update([
                'calculo_grupo_tres' => $total_grupo
            ]);
        } catch (\Exception $error) {
            return $error->getMessage();
        }
    }

    private function updateEsquemaViaticos($esquema, $ids_recarga)
    {
        try {
            $viaticos = $esquema->funcionario->viaticos()->whereIn('recarga_id', $ids_recarga)
                ->whereNull('esquema_id')
                ->get();

            if ($viaticos->count() > 0) {
                $viaticos->each(function ($viatico) use ($esquema) {
                    $viatico->esquema_id = $esquema->id;
                    $viatico->save();
                });
            }

            if ($turnante) {
                $data = [
                    'total_dias_viaticos'           => $viaticos->where('valor_viatico', '>', 0)->sum('total_dias_periodo_turno'),
                    'total_dias_habiles_viaticos'   => $viaticos->where('valor_viatico', '>', 0)->sum('total_dias_habiles_periodo_turno'),
                    'viaticos_n_registros'          => count($viaticos),
                ];
            } else {
                $data = [
                    'total_dias_viaticos'           => $viaticos->where('valor_viatico', '>', 0)->sum('total_dias_periodo'),
                    'total_dias_habiles_viaticos'   => $viaticos->where('valor_viatico', '>', 0)->sum('total_dias_habiles_periodo'),
                    'viaticos_n_registros'          => count($viaticos),
                ];
            }
            $update  = $esquema->update($data);
            $esquema = $esquema->fresh();

            $total_viaticos        = $this->totalDiasViaticos($esquema);
            $esquema->update([
                'calculo_viaticos' => $total_viaticos
            ]);
        } catch (\Exception $error) {
            return $error->getMessage();
        }
    }

    private function totalDiasViaticos($esquema)
    {
        $total_viaticos  = 0;
        switch ($esquema->es_turnante_value) {
            case 1:
                $total_viaticos = $esquema->total_dias_viaticos;
                break;
            case 0:
                $total_viaticos = $esquema->total_dias_habiles_viaticos;
                break;
        }
        return $total_viaticos;
    }

    private function totalDiasAusentismoGrupo($esquema, $id_grupo)
    {
        $total_ausentismos  = 0;
        switch ($esquema->es_turnante_value) {
            case 1:
                switch ($id_grupo) {
                    case 1:
                        $total_ausentismos = $esquema->total_dias_grupo_uno;
                        break;
                    case 2:
                        $total_ausentismos = $esquema->total_dias_grupo_dos;
                        break;
                    case 3:
                        $total_ausentismos = $esquema->total_dias_grupo_tres;
                        break;
                }
                break;
            case 0:
                switch ($id_grupo) {
                    case 1:
                        $total              = $esquema->total_dias_habiles_grupo_uno - $esquema->total_dias_feriados_grupo_uno;
                        $total_ausentismos  = $total;
                        break;
                    case 2:
                        $total              = $esquema->total_dias_habiles_grupo_dos - $esquema->total_dias_feriados_grupo_dos;
                        $total_ausentismos  = $total;
                        break;
                    case 3:
                        $total              = $esquema->total_dias_habiles_grupo_tres - $esquema->total_dias_feriados_grupo_tres;
                        $total_ausentismos  = $total;
                        break;
                }
                break;
        }
        return $total_ausentismos;
    }

    private function totalContrato($esquema, $total_dias_contrato_periodo, $total_dias_habiles_contrato_periodo, $feriados_count)
    {
        $total_dias_contrato = $total_dias_habiles_contrato_periodo - $feriados_count;
        if ($esquema->es_turnante === 1) {
            $total_dias_contrato = $total_dias_contrato_periodo;
        }
        return $total_dias_contrato;
    }

    public function deleteDataRecarga(Request $request, $codigo, $modulo)
    {
        try {
            $with    = $this->withRecarga();
            $recarga = Recarga::with($with)->where('codigo', $codigo)->withCount('users')->withCount('ausentismos')->withCount('asignaciones')->withCount('reajustes')->withCount('contratos')->withCount('esquemas')->withCount('viaticos')->firstOrFail();

            switch ($modulo) {
                case 'esquema':
                    $response = $this->deleteEsquemas($recarga);
                    break;

                case 'contrato':
                    $response = $this->deleteRecords($recarga, 'contratos', null);
                    break;

                case 'asignacion':
                    $response = $this->deleteRecords($recarga, 'asignaciones', null);
                    break;

                case 'asistencias':
                    $response = $this->deleteRecords($recarga, 'asistencias', null);
                    break;

                case 'ausentismos_grupo_uno':
                    $response = $this->deleteRecords($recarga, 'ausentismos', 1);
                    break;

                case 'ausentismos_grupo_dos':
                    $response = $this->deleteRecords($recarga, 'ausentismos', 2);
                    break;

                case 'ausentismos_grupo_tres':
                    $response = $this->deleteRecords($recarga, 'ausentismos', 3);
                    break;

                case 'viatico':
                    $response = $this->deleteRecords($recarga, 'viaticos', null);
                    break;
            }

            $withReReglas             = $this->withReReglas($request->load_grupo);
            $esquemas = $recarga->esquemas()
                ->with($withReReglas);

            $ordenamientos = $request->ordenamiento;

            if ($request->ordenamiento) {
                $decodificado = array_map(function ($ordenamientos) {
                    return json_decode($ordenamientos, true);
                }, $ordenamientos);
            }

            if ($request->ordenamiento) {
                foreach ($decodificado as $ordenamiento) {
                    $index      = $ordenamiento['index'];
                    $campo      = $ordenamiento['nombre'];
                    $tipoOrden  = $ordenamiento['order'];
                    $index      = (int)$index;
                    if ($index === 1) {
                        $esquemas->join('users', 'esquemas.user_id', '=', 'users.id')
                            ->orderBy('users.apellidos', $tipoOrden);
                    } else {
                        $esquemas->orderBy($campo, $tipoOrden);
                    }
                }
            }

            $esquemas = $esquemas->paginate(30);
            $recarga->{'total_clp'} = $esquemas->sum('monto_total_cancelar');

            if ($response->delete) {
                return response()->json(
                    array(
                        'status'  => $response->status,
                        'title'   => $response->title,
                        'message' => $response->message,
                        'recarga' => RecargaResumenResource::make($recarga),
                        'users'     => TablaResumenResource::collection($esquemas),
                    )
                );
            } else {
                return response()->json(
                    array(
                        'status'  => 'Error',
                        'title'   => 'Error al eliminar datos',
                        'message' => null,
                        'recarga' => null
                    )
                );
            }
        } catch (\Exception $error) {
            return $error->getMessage();
        }
    }

    private function deleteEsquemas($recarga)
    {
        try {
            $relatedModels = [
                'Contratos'     => $recarga->contratos()->count(),
                'Asignaciones' => $recarga->asignaciones()->count(),
                'Asistencias' => $recarga->asistencias()->count(),
                'Ausentismos' => $recarga->ausentismos()->count(),
                'Viaticos' => $recarga->viaticos()->count(),
                'Reajustes' => $recarga->reajustes()->count(),
            ];

            foreach ($relatedModels as $modelName => $count) {
                if ($count > 0) {
                    $response = (object) [
                        'status'    => 'Error',
                        'delete'    => true,
                        'title'     => 'Error al eliminar cartolas',
                        'message'   => "Existen registros asociados a las cartolas de {$modelName}."
                    ];

                    return $response;
                }
            }

            $total      = $recarga->esquemas()->count();
            $esquemas   = $recarga->esquemas()->get();

            if ($total <= 0) {
                $response = (object) [
                    'status'    => 'Error',
                    'delete'    => true,
                    'title'     => 'Error al eliminar datos',
                    'message'   => 'No existen registros para eliminar'
                ];

                return $response;
            }

            $esquemas->each(function ($esquema) {
                $esquema->delete();
            });

            $response = (object) [
                'status' => 'Success',
                'delete' => true,
                'title' => "{$total} registros eliminados en recarga {$recarga->codigo}.",
                'message' => null
            ];
        } catch (\Exception $error) {
            $response = (object) [
                'status' => 'Error',
                'delete' => false,
                'title' => null
            ];
        }

        return $response;
    }

    private function deleteRecords($recarga, $relation, $grupo_id)
    {
        try {
            if (!$grupo_id) {
                $total   = $recarga->$relation()->count();
                $records = $recarga->$relation()->get();
            } else {
                $total   = $recarga->$relation()->where('grupo_id', $grupo_id)->count();
                $records = $recarga->$relation()->where('grupo_id', $grupo_id)->get();
            }

            if ($total <= 0) {
                $response = (object) [
                    'status'  => 'Error',
                    'delete'  => true,
                    'title'   => 'Error al eliminar datos',
                    'message' => 'No existen registros para eliminar'
                ];

                return $response;
            }

            $records->each(function ($record) {
                $record->delete();
            });

            $response = (object) [
                'status'  => 'Success',
                'delete'  => true,
                'title'   => "{$total} registros eliminados en recarga {$recarga->codigo}.",
                'message' => null
            ];
        } catch (\Exception $error) {
            $response = (object) [
                'status'  => 'Error',
                'delete' => false,
                'title'  => null
            ];
        }

        return $response;
    }

    private function mapIngredients($recargas)
    {
        return collect($recargas)->map(function ($i) {
            return ['beneficio' => $i];
        });
    }
}
