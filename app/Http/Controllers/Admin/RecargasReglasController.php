<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Reglas\StoreReglaController;
use App\Models\GrupoAusentismo;
use App\Models\Recarga;
use App\Models\Regla;
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

                return $this->successResponse($reglas, null, null, 200);
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
                    $tipo_ausentismo->{'recarga_id'}                = $recarga->id;
                    $tipo_ausentismo->{'tipo_ausentismo_id'}        = $tipo_ausentismo->id;
                    $tipo_ausentismo->{'nombre'}                    = $tipo_ausentismo->nombre;
                    $tipo_ausentismo->{'grupo_id'}                  = 1;
                    $tipo_ausentismo->{'active'}                    = true;
                    $tipo_ausentismo->{'meridiano_turnante'}        = [];
                    $tipo_ausentismo->{'meridiano_no_turnante'}     = [];
                    $tipo_ausentismo->{'hora_inicio_no_turnante'}   = NULL;
                    $tipo_ausentismo->{'hora_termino_no_turnante'}  = NULL;
                    $tipo_ausentismo->{'hora_inicio_turnante'}      = NULL;
                    $tipo_ausentismo->{'hora_termino_turnante'}     = NULL;
                    $tipo_ausentismo->{'turno'}                     = 1;
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

                $grupo = GrupoAusentismo::find($regla['grupo_id']);

                $n_grupo = (int)$grupo->n_grupo;

                if ($n_grupo === 1) {
                    $data = [
                        'recarga_id'                => $recarga->id,
                        'tipo_ausentismo_id'        => $regla['tipo_ausentismo_id'],
                        'grupo_id'                  => $grupo->id,
                        'active'                    => $regla['active'],
                        'turno_funcionario'         => NULL
                    ];
                    $regla = Regla::create($data);

                    if ($regla) {
                        array_push($new_reglas, $regla);
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
                        $regla_1->meridianos()->attach($regla['meridiano_no_turnante']);
                    }

                    $regla_2 = Regla::create($data_2);

                    if ($regla_2) {
                        $regla_2->meridianos()->attach($regla['meridiano_turnante']);
                    }

                    if ($regla_1) {
                        array_push($new_reglas, $regla_1);
                    }

                    if ($regla_2) {
                        array_push($new_reglas, $regla_2);
                    }
                } else if ($n_grupo === 3) {
                    $data_1 = [
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
                    $regla_2 = Regla::create($data_2);

                    if ($regla_1) {
                        array_push($new_reglas, $regla_1);
                    }

                    if ($regla_2) {
                        array_push($new_reglas, $regla_2);
                    }
                }

                /* $data = [
                    'recarga_id'                => $regla['recarga_id'],
                    'tipo_ausentismo_id'        => $regla['tipo_ausentismo_id'],
                    'grupo_id'                  => $regla['grupo_id'],
                    'active'                    => $regla['active'],
                    'hora_inicio'               => $regla['hora_inicio'],
                    'hora_termino'              => $regla['hora_termino']
                ];

                $new_regla = Regla::create($data);

                if ($regla['grupo_id'] === 2 && $regla['active'] === true && $regla['meridiano']) {
                    if ($new_regla) {
                        $new_regla->meridianos()->attach($regla['meridiano']);
                    }
                } */
            }

            /* $estado = SeguimientoRecarga::create([
                'recarga_id'    => $recarga->id,
                'estado_id'     => 2
            ]); */

            if (count($new_reglas) > 0) {
                return $this->successResponse($new_reglas, count($new_reglas) . ' reglas ingresadas correctamente.', null, 200);
            } else {
                return $this->errorResponse('No se ingresaron reglas', 500);
            }
        } catch (\Exception $error) {
            return response()->json(array($error->getMessage(), $error->failures()));
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
}
