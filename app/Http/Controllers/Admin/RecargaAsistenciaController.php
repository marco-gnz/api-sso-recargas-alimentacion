<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Asistencias\UpdateAsistenciaResumenRequest;
use App\Http\Resources\AsistenciaRecargaResource;
use App\Models\Asistencia;
use App\Models\Recarga;
use App\Models\User;
use Illuminate\Http\Request;

class RecargaAsistenciaController extends Controller
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

    private function withAsistencia()
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

    public function asistenciasRecarga($codigo)
    {
        try {
            $recarga = Recarga::where('codigo', $codigo)->first();

            if($recarga){
                $users_id = $recarga->asistencias()->pluck('user_id')->toArray();

                $users_id = array_unique($users_id);

                $users = User::whereIn('id', $users_id)->get();

                return $this->successResponse(AsistenciaRecargaResource::collection($users));
            }
        } catch (\Exception $error) {
            return response()->json($error->getMessage());
        }
    }

    public function updateAsistencia($id, UpdateAsistenciaResumenRequest $request)
    {
        try {
            $asistencia = Asistencia::find($id);

            $form = ['tipo_asistencia_turno_id'];

            $update = $asistencia->update($request->only($form));

            $user = $asistencia->funcionario;

            if($update){
                return $this->successResponse(AsistenciaRecargaResource::make($user), "Asistencia modificada con éxito.", "Modificado a {$asistencia->tipoAsistenciaTurno->descripcion}");
            }else{

            }
        } catch (\Exception $error) {
            return response()->json($error->getMessage());
        }
    }
}
