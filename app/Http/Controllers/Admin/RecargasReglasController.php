<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Reglas\StoreReglaController;
use App\Http\Resources\RecargaReglasResource;
use App\Http\Resources\ReglaResource;
use App\Models\GrupoAusentismo;
use App\Models\Meridiano;
use App\Models\Recarga;
use App\Models\Regla;
use App\Models\ReglaHorario;
use App\Models\SeguimientoRecarga;
use App\Models\TipoAusentismo;
use Illuminate\Http\Request;

class RecargasReglasController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth:sanctum']);
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

    public function returnReglasToGrupo(Request $request)
    {
        try {
            $grupo      = GrupoAusentismo::find($request->grupo_id);
            $recarga    = Recarga::where('codigo', $request->recarga_codigo)->first();

            if ($grupo && $recarga) {
                $reglas = Regla::with('tipoAusentismo', 'meridianos')->where('recarga_id', $recarga->id)->where('grupo_id', $grupo->id)->get();

                return response()->json(
                    array(
                        'status'    => 'Success',
                        'title'     => null,
                        'message'   => null,
                        'reglas'    => RecargaReglasResource::collection($reglas)
                    )
                );
            }
        } catch (\Exception $error) {
            return $error->getMessage();
        }
    }

    public function returnTiposAusentismos($codigo)
    {
        try {
            $reglas             = [];
            $recarga            = Recarga::where('codigo', $codigo)->first();
            $tipos_ausentismos  = TipoAusentismo::where('estado', true)->whereNotIn('id', $recarga->reglas()->pluck('tipo_ausentismo_id'))->orderBy('nombre', 'asc')->get();

            if ($recarga) {
                foreach ($tipos_ausentismos as $tipo_ausentismo) {
                    $tipo_ausentismo->{'recarga_id'}                    = $recarga->id;
                    $tipo_ausentismo->{'tipo_ausentismo_id'}            = $tipo_ausentismo->id;
                    $tipo_ausentismo->{'nombre'}                        = $tipo_ausentismo->nombre;
                    $tipo_ausentismo->{'grupo_id'}                      = 1;
                    $tipo_ausentismo->{'active'}                        = true;
                    $tipo_ausentismo->{'active_tipo_dias'}              = false;
                    $tipo_ausentismo->{'tipo_dias_turnante'}            = true;
                    $tipo_ausentismo->{'tipo_dias_no_turnante'}         = true;
                    $tipo_ausentismo->{'meridiano_turnante'}            = [];
                    $tipo_ausentismo->{'meridiano_no_turnante'}         = [];

                    $tipo_ausentismo->{'hora_inicio_turnante_am'}       = NULL;
                    $tipo_ausentismo->{'hora_termino_turnante_am'}      = NULL;
                    $tipo_ausentismo->{'hora_inicio_turnante_pm'}       = NULL;
                    $tipo_ausentismo->{'hora_termino_turnante_pm'}      = NULL;

                    $tipo_ausentismo->{'hora_inicio_no_turnante_am'}    = NULL;
                    $tipo_ausentismo->{'hora_termino_no_turnante_am'}   = NULL;
                    $tipo_ausentismo->{'hora_inicio_no_turnante_pm'}    = NULL;
                    $tipo_ausentismo->{'hora_termino_no_turnante_pm'}   = NULL;

                    $tipo_ausentismo->{'turno'}                         = 1;
                    array_push($reglas, $tipo_ausentismo);
                }
            }
            return response()->json($reglas, 200);
        } catch (\Exception $error) {
            return $error->getMessage();
        }
    }

    public function storeReglas(StoreReglaController $request)
    {
        try {
            $new_reglas = [];
            $reglas     = $request['reglas'];
            $recarga    = Recarga::find($reglas[0]['recarga_id']);

            foreach ($reglas as $regla) {
                $active = $regla['active'] ? true : false;
                if ($active) {
                    $grupo = GrupoAusentismo::find($regla['grupo_id']);
                    $n_grupo = (int)$grupo->n_grupo;
                    if ($n_grupo === 1) {
                        if ($regla['active_tipo_dias']) {
                            $data_1 = [
                                'recarga_id'                => $recarga->id,
                                'tipo_ausentismo_id'        => $regla['tipo_ausentismo_id'],
                                'grupo_id'                  => $grupo->id,
                                'active'                    => $regla['active'],
                                'turno_funcionario'         => false,
                                'active_tipo_dias'          => true,
                                'tipo_dias'                 => $regla['tipo_dias_no_turnante']
                            ];

                            $data_2 = [
                                'recarga_id'                => $recarga->id,
                                'tipo_ausentismo_id'        => $regla['tipo_ausentismo_id'],
                                'grupo_id'                  => $grupo->id,
                                'active'                    => $regla['active'],
                                'turno_funcionario'         => true,
                                'active_tipo_dias'          => true,
                                'tipo_dias'                 => $regla['tipo_dias_turnante']
                            ];
                            $regla_1 = Regla::create($data_1);
                            $regla_2 = Regla::create($data_2);

                            if ($regla_1) {
                                array_push($new_reglas, $regla_1);
                            }
                            if ($regla_2) {
                                array_push($new_reglas, $regla_2);
                            }
                        } else {
                            $data = [
                                'recarga_id'                => $recarga->id,
                                'tipo_ausentismo_id'        => $regla['tipo_ausentismo_id'],
                                'grupo_id'                  => $grupo->id,
                                'active'                    => $regla['active'],
                            ];

                            $regla = Regla::create($data);

                            if ($regla) {
                                array_push($new_reglas, $regla);
                            }
                        }
                    } else if ($n_grupo === 2) {
                        $data_1 = [
                            'recarga_id'                => $recarga->id,
                            'tipo_ausentismo_id'        => $regla['tipo_ausentismo_id'],
                            'grupo_id'                  => $grupo->id,
                            'active'                    => $regla['active'],
                            'turno_funcionario'         => false
                        ];

                        $data_2 = [
                            'recarga_id'                => $recarga->id,
                            'tipo_ausentismo_id'        => $regla['tipo_ausentismo_id'],
                            'grupo_id'                  => $grupo->id,
                            'active'                    => $regla['active'],
                            'turno_funcionario'         => true
                        ];

                        $regla_1 = Regla::create($data_1);

                        if ($regla_1) {
                            $regla_1->meridianos()->attach($regla['meridiano_no_turnante'], ['active' => true]);
                            $meridianos_restantes_1 = Meridiano::whereNotIn('id', $regla['meridiano_no_turnante'])->get();
                            if (count($meridianos_restantes_1) > 0) {
                                $regla_1->meridianos()->attach($meridianos_restantes_1->pluck('id'), ['active' => false]);
                            }
                        }

                        $regla_2 = Regla::create($data_2);

                        if ($regla_2) {
                            $regla_2->meridianos()->attach($regla['meridiano_turnante'], ['active' => true]);

                            $meridianos_restantes_2 = Meridiano::whereNotIn('id', $regla['meridiano_turnante'])->get();
                            if (count($meridianos_restantes_2) > 0) {
                                $regla_2->meridianos()->attach($meridianos_restantes_2->pluck('id'), ['active' => false]);
                            }
                        }

                        if ($regla_1) {
                            array_push($new_reglas, $regla_1);
                        }

                        if ($regla_2) {
                            array_push($new_reglas, $regla_2);
                        }
                    } else if ($n_grupo === 3) {
                        /* $data_1 = [
                            'recarga_id'                => $regla['recarga_id'],
                            'tipo_ausentismo_id'        => $regla['tipo_ausentismo_id'],
                            'grupo_id'                  => $grupo->id,
                            'active'                    => $regla['active'],
                            'turno_funcionario'         => false,
                            'hora_inicio'               => $regla['hora_inicio_no_turnante'],
                            'hora_termino'              => $regla['hora_termino_no_turnante']
                        ];

                        $data_2 = [
                            'recarga_id'                => $regla['recarga_id'],
                            'tipo_ausentismo_id'        => $regla['tipo_ausentismo_id'],
                            'grupo_id'                  => $grupo->id,
                            'active'                    => $regla['active'],
                            'turno_funcionario'         => true,
                            'hora_inicio'               => $regla['hora_inicio_turnante'],
                            'hora_termino'              => $regla['hora_termino_turnante']
                        ];

                        $regla_1 = Regla::create($data_1);
                        $regla_2 = Regla::create($data_2); */

                        $data_1 = [
                            'recarga_id'                => $regla['recarga_id'],
                            'tipo_ausentismo_id'        => $regla['tipo_ausentismo_id'],
                            'grupo_id'                  => $grupo->id,
                            'active'                    => $regla['active'],
                            'turno_funcionario'         => false,
                        ];

                        $regla_1 = Regla::create($data_1);

                        if ($regla_1) {
                            $add_horario_1 = $regla_1->horarios()->create([
                                'hora_inicio'    => $regla['hora_inicio_no_turnante_am'],
                                'hora_termino'   => $regla['hora_termino_no_turnante_am'],
                            ]);

                            $add_horario_2 = $regla_1->horarios()->create([
                                'hora_inicio'    => $regla['hora_inicio_no_turnante_pm'],
                                'hora_termino'   => $regla['hora_termino_no_turnante_pm'],
                            ]);

                            if ($regla_1) {
                                array_push($new_reglas, $regla_1);
                            }
                        }

                        $data_2 = [
                            'recarga_id'                => $regla['recarga_id'],
                            'tipo_ausentismo_id'        => $regla['tipo_ausentismo_id'],
                            'grupo_id'                  => $grupo->id,
                            'active'                    => $regla['active'],
                            'turno_funcionario'         => true,
                        ];

                        $regla_2 = Regla::create($data_2);

                        if ($regla_2) {
                            $add_horario_1 = $regla_2->horarios()->create([
                                'hora_inicio'    => $regla['hora_inicio_turnante_am'],
                                'hora_termino'   => $regla['hora_termino_turnante_am'],
                            ]);

                            $add_horario_2 = $regla_2->horarios()->create([
                                'hora_inicio'    => $regla['hora_inicio_turnante_pm'],
                                'hora_termino'   => $regla['hora_termino_turnante_pm'],
                            ]);

                            if ($regla_2) {
                                array_push($new_reglas, $regla_2);
                            }
                        }
                    }
                }
            }

            if (count($new_reglas) > 0) {
                return $this->successResponse($new_reglas, count($new_reglas) . ' reglas ingresadas correctamente.', null, 200);
            } else {
                return $this->errorResponse('No se ingresaron reglas', 500);
            }
        } catch (\Exception $error) {
            return $error->getMessage();
        }
    }

    public function deleteReglaInRecarga($id)
    {
        $regla = Regla::find($id);

        if ($regla) {
            $delete = $regla->delete();

            if ($delete) {
                $message = 'Regla eliminada con éxito';
                return $this->successResponse(null, $message, 200);
            }
        }
        return $regla;
    }

    public function getRegla($id)
    {
        try {
            $regla = Regla::with('meridianos')->find($id);

            if ($regla) {
                return response()->json(
                    array(
                        'status'    => 'Success',
                        'title'     => null,
                        'message'   => null,
                        'regla'     => ReglaResource::make($regla)
                    )
                );
            }
        } catch (\Exception $error) {
            return response()->json($error->getMessage());
        }
    }

    public function updateRegla($id, Request $request)
    {
        try {
            $regla = Regla::find($id);
            $grupo = GrupoAusentismo::find($request->grupo_id);

            if (!$regla || !$grupo) {
                return response()->json([
                    'status' => 'Error',
                    'message' => 'No se encontró la regla o el grupo.',
                ]);
            }

            $n_grupo = (int) $grupo->n_grupo;
            $updateData = [
                'grupo_id'          => $grupo->id,
                'active_tipo_dias'  => $n_grupo === 1 ? $request->active_tipo_dias : false,
                'tipo_dias'         => $n_grupo === 1 ? $request->tipo_dias : null,
            ];
            $regla->update($updateData);
            $regla = $regla->fresh();

            if ($n_grupo === 1) {
                $regla->horarios()->delete();
                $regla->meridianos()->detach();
            } elseif ($n_grupo === 3) {
                $regla->meridianos()->detach();

                if ($request->horarios) {
                    foreach ($request->horarios as $horario) {
                        $horario_bd = ReglaHorario::find($horario['id']);
                        if ($horario_bd) {
                            $horario_bd->update([
                                'hora_inicio'   => $horario['hora_inicio_value'],
                                'hora_termino'  => $horario['hora_termino_value'],
                            ]);
                        }
                    }
                }
            } elseif ($n_grupo === 2 && $request->meridianos) {
                $regla->horarios()->delete();
                $regla->meridianos()->detach();
                $regla->meridianos()->attach($request->meridianos, ['active' => true]);

                $not_meridianos = Meridiano::whereNotIn('id', $request->meridianos)->get();
                if ($not_meridianos->isNotEmpty()) {
                    $regla->meridianos()->attach($not_meridianos->pluck('id'), ['active' => false]);
                }
            }

            return response()->json([
                'status' => 'Success',
                'title' => 'Regla modificada con éxito.',
                'message' => null,
                'regla' => RecargaReglasResource::make($regla),
            ]);
        } catch (\Exception $error) {
            return response()->json($error->getMessage());
        }
    }


    /* public function updateRegla($id, Request $request)
    {
        try {
            $regla = Regla::find($id);
            $grupo = GrupoAusentismo::find($request->grupo_id);

            if ($regla && $grupo) {
                $n_grupo = (int)$grupo->n_grupo;
                $update  = $regla->update([
                    'grupo_id'          => $grupo->id,
                    'active_tipo_dias'  => $n_grupo === 1 ? ($request->active_tipo_dias) : false,
                    'tipo_dias'         => $n_grupo === 1 ? ($request->tipo_dias) : null
                ]);
                $regla = $regla->fresh();

                if ($n_grupo === 1) {
                    $delete_horarios = $regla->horarios()->delete();
                }
                if ($n_grupo === 3) {
                    $regla->meridianos()->detach();

                    if ($request->horarios) {
                        foreach ($request->horarios as $horario) {
                            $horario_bd = ReglaHorario::find($horario['id']);
                            if ($horario_bd) {
                                $update = $horario_bd->update([
                                    'hora_inicio'           => $horario['hora_inicio_value'],
                                    'hora_termino'          => $horario['hora_termino_value']
                                ]);
                            }
                        }
                    }
                }

                if (($n_grupo === 2 && ($request->meridianos))) {
                    $delete_horarios    = $regla->horarios()->delete();
                    $delete             = $regla->meridianos()->detach();

                    if ($delete) {
                        $regla->meridianos()->attach($request->meridianos, ['active' => true]);

                        $not_meridianos = Meridiano::whereNotIn('id', $request->meridianos)->get();

                        if (count($not_meridianos) > 0) {
                            $regla->meridianos()->attach($not_meridianos->pluck('id'), ['active' => false]);
                        }
                    }
                }
            }

            if ($regla) {
                return response()->json(
                    array(
                        'status'    => 'Success',
                        'title'     => 'Regla modificada con éxito.',
                        'message'   => null,
                        'regla'     => RecargaReglasResource::make($regla)
                    )
                );
            }
        } catch (\Exception $error) {
            return response()->json($error->getMessage());
        }
    } */
}
