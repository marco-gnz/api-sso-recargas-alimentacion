<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\AsistenciaRecargaResource;
use App\Http\Resources\FuncionarioAusentismosResource;
use App\Http\Resources\FuncionarioRecargaResource;
use App\Http\Resources\FuncionarioTurnosResource;
use App\Models\Asistencia;
use App\Models\Ausentismo;
use App\Models\GrupoAusentismo;
use App\Models\Recarga;
use App\Models\User;
use App\Models\UserTurno;
use Illuminate\Http\Request;

class RecargaFuncionarioController extends Controller
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
            'establecimiento'
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

            return $this->successResponse(FuncionarioTurnosResource::collection($turnos));
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



            if(!$funcionario || !$recarga || !$grupo){
                return $this->errorResponse('No existe recurso por buscar. Recargar pagina.', 400);
            }else{
                $ausentismos = Ausentismo::with($with)->where('user_id', $funcionario->id)->where('recarga_id', $recarga->id)->where('grupo_id', $grupo->id)->get();
                return $this->successResponse(FuncionarioAusentismosResource::collection($ausentismos));
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

            return $this->successResponse(AsistenciaRecargaResource::collection($funcionario));
        } catch (\Exception $error) {
            return response()->json($error->getMessage());
        }
    }
}
