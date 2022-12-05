<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\FuncionarioRecargaResource;
use App\Http\Resources\FuncionarioTurnosResource;
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
            'data'      => null
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

    public function returnFuncionario($codigo, $uuid)
    {
        try {
            $funcionario    = User::where('uuid', $uuid)->first();
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
}
