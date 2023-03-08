<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\AsistenciaRecargaResource;
use App\Http\Resources\FuncionarioAusentismosResource;
use App\Http\Resources\FuncionarioContratosResource;
use App\Http\Resources\FuncionarioReajustesResource;
use App\Http\Resources\FuncionarioRecargaResource;
use App\Http\Resources\FuncionarioTurnosResource;
use App\Http\Resources\FuncionarioViaticosResource;
use App\Http\Resources\RecargaInUserResource;
use App\Models\Asistencia;
use App\Models\Ausentismo;
use App\Models\GrupoAusentismo;
use App\Models\Reajuste;
use App\Models\Recarga;
use App\Models\RecargaContrato;
use App\Models\User;
use App\Models\UserTurno;
use App\Models\Viatico;
use Illuminate\Http\Request;

class RecargaFuncionarioController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth:sanctum']);
    }

    protected function successResponse($data, $title = null, $message = null, $code = 200, $additional = null)
    {
        return response()->json([
            'status'     => 'Success',
            'title'      => $title,
            'message'    => $message,
            'data'       => $data,
            'additional' => $additional
        ], $code);
    }

    protected function errorResponse($message = null, $code)
    {
        return response()->json([
            'status'    => 'Error',
            'message'   => $message,
            'data'      => null,
            'code'      => $code
        ], $code);
    }

    private function withTurno()
    {
        $with = [
            'proceso',
            'calidad',
            'establecimiento',
            'unidad',
            'planta',
            'userBy',
            'userByUpdate'
        ];
        return $with;
    }

    public function withAusentismo()
    {
        $with = [
            'grupoAusentismo',
            'regla',
            'tipoAusentismo',
            'establecimiento',
            'meridiano'
        ];

        return $with;
    }


    public function returnFuncionario($codigo, $uuid)
    {
        try {
            $recarga        = Recarga::where('codigo', $codigo)->first();
            $funcionario    = User::where('uuid', $uuid)->first();

            $funcionario->{'recarga'}   = $recarga;
            return $this->successResponse(FuncionarioRecargaResource::make($funcionario));
        } catch (\Exception $error) {
            return response()->json($error->getMessage());
        }
    }

    public function returnTurnosFuncionario($codigo, $uuid)
    {
        try {
            $funcionario    = User::where('uuid', $uuid)->first();
            $recarga    = Recarga::where('codigo', $codigo)->first();
            $with = $this->withTurno();

            $turnos         = UserTurno::with($with)->where('user_id', $funcionario->id)->where('recarga_id', $recarga->id)->get();

            return response()->json(
                array(
                    'status'    => 'Success',
                    'title'     => null,
                    'message'   => null,
                    'turnos'    => FuncionarioTurnosResource::collection($turnos),
                    'recarga'   => RecargaInUserResource::make($recarga)
                )
            );
        } catch (\Exception $error) {
            return response()->json($error->getMessage());
        }
    }

    public function returnContratosFuncionario($codigo, $uuid)
    {
        try {
            $funcionario    = User::where('uuid', $uuid)->first();
            $recarga        = Recarga::where('codigo', $codigo)->first();
            $contratos      = RecargaContrato::where('user_id', $funcionario->id)->where('recarga_id', $recarga->id)->get();

            return response()->json(
                array(
                    'status'    => 'Success',
                    'title'     => null,
                    'message'   => null,
                    'contratos' => FuncionarioContratosResource::collection($contratos),
                    'recarga'   => RecargaInUserResource::make($recarga)
                )
            );
        } catch (\Exception $error) {
            return response()->json($error->getMessage());
        }
    }

    public function returnAusentismosFuncionario($codigo, $uuid, $grupo)
    {
        try {
            $funcionario    = User::where('uuid', $uuid)->first();
            $recarga        = Recarga::where('codigo', $codigo)->first();
            $grupo          = GrupoAusentismo::where('n_grupo', $grupo)->first();

            $with           = $this->withAusentismo();

            if (!$funcionario || !$recarga || !$grupo) {
                return $this->errorResponse('No existe recurso por buscar. Recargar pagina.', 400);
            } else {
                $ausentismos = Ausentismo::with($with)->where('user_id', $funcionario->id)->where('recarga_id', $recarga->id)->where('grupo_id', $grupo->id)->get();

                return response()->json(
                    array(
                        'status'        => 'Success',
                        'title'         => null,
                        'message'       => null,
                        'ausentismos'   => FuncionarioAusentismosResource::collection($ausentismos),
                        'recarga'       => RecargaInUserResource::make($recarga)
                    )
                );
            }
        } catch (\Exception $error) {
            return response()->json($error->getMessage());
        }
    }

    public function returnAsistenciasFuncionario($codigo, $uuid)
    {
        try {
            $funcionario    = User::where('uuid', $uuid)->get();
            $recarga        = Recarga::where('codigo', $codigo)->first();

            return response()->json(
                array(
                    'status'        => 'Success',
                    'title'         => null,
                    'message'       => null,
                    'asistencias'   => AsistenciaRecargaResource::collection($funcionario),
                    'recarga'       => RecargaInUserResource::make($recarga)
                )
            );
        } catch (\Exception $error) {
            return response()->json($error->getMessage());
        }
    }

    public function returnViaticosFuncionario($codigo, $uuid)
    {
        try {
            $funcionario    = User::where('uuid', $uuid)->first();
            $recarga        = Recarga::where('codigo', $codigo)->first();

            $viaticos         = Viatico::where('user_id', $funcionario->id)->where('recarga_id', $recarga->id)->get();

            return response()->json(
                array(
                    'status'    => 'Success',
                    'title'     => null,
                    'message'   => null,
                    'viaticos'  => FuncionarioViaticosResource::collection($viaticos),
                    'recarga'   => RecargaInUserResource::make($recarga)
                )
            );
        } catch (\Exception $error) {
            return response()->json($error->getMessage());
        }
    }

    public function returnReajustesFuncionario($codigo, $uuid)
    {
        try {
            $funcionario          = User::where('uuid', $uuid)->firstOrFail();
            $recarga              = Recarga::where('codigo', $codigo)->firstOrFail();
            $reajustes            = Reajuste::where('user_id', $funcionario->id)->where('recarga_id', $recarga->id)->get();

            return response()->json(
                array(
                    'status'    => 'Success',
                    'title'     => null,
                    'message'   => null,
                    'reajustes'    => FuncionarioReajustesResource::collection($reajustes),
                    'recarga'   => RecargaInUserResource::make($recarga)
                )
            );
        } catch (\Exception $error) {
            return response()->json($error->getMessage());
        }
    }
}
