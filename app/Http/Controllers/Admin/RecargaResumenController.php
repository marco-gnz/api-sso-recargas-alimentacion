<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Recargas\BeneficioUserRequest;
use App\Http\Resources\FuncionariosResumenResource;
use App\Http\Resources\RecargaResumenResource;
use App\Models\Ausentismo;
use App\Models\Recarga;
use App\Models\User;
use Illuminate\Http\Request;

class RecargaResumenController extends Controller
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

    private function withRecarga()
    {
        $with = [
            'seguimiento',
            'reglas.grupoAusentismo',
            'reglas.tipoAusentismo',
            'reglas.meridianos',
            'establecimiento',
            'ausentismos.funcionario',
            'userCreatedBy',
            'userUpdateBy'
        ];

        return $with;
    }

    public function returnFindRecarga($codigo)
    {
        try {
            $with = $this->withRecarga();
            $recarga = Recarga::where('codigo', $codigo)->withCount('users')->with($with)->first();

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

    public function returnFuncionariosToRecarga($codigo)
    {
        try {
            $funcionarios   = [];
            $recarga        = Recarga::where('codigo', $codigo)->withCount('users')->first();
            $users          = $recarga->users;

            foreach ($users as $user) {
                $user->{'recarga'} = $recarga;
                array_push($funcionarios, $user);
            }

            return $this->successResponse(FuncionariosResumenResource::collection($funcionarios), null, null, 200);
        } catch (\Exception $error) {
            return $error->getMessage();
        }
    }

    public function changeBeneficioToUser(BeneficioUserRequest $request)
    {
        try {

            $funcionario = User::find($request->user_id);

            $recarga = Recarga::where('codigo', $request->codigo_recarga)->withCount('users')->first();

            if($funcionario){
                $value = $funcionario->recargas->where('id', $recarga->id)->first()->pivot->beneficio;

                $sync = $funcionario->recargas()->sync([$recarga->id => array(
                    'recarga_id' => $recarga->id,
                    'user_id' => $funcionario->id,
                    'beneficio' => $value ? false : true
                )]);

                $funcionario->{'recarga'} = $recarga;

                /* $with = $this->withRecarga();
                $recarga = $recarga->refresh($with); */

                $status  = $value ? 'deshabilitado' : 'habilitado';
                $status_count = $value ? 'No será ' : 'Será';
                $title   = 'Funcionario modificado';
                $message =  "Se ha {$status} el beneficio a {$funcionario->nombre_completo}. {$status_count} contabilizado en el monto total.";
                /* return $this->successResponse(FuncionariosResumenResource::make($funcionario),$title , $message, 200); */
                return $this->successResponse(array(FuncionariosResumenResource::make($funcionario), RecargaResumenResource::make($recarga)),$title , $message, 200);

            }
        } catch (\Exception $error) {
            return $error->getMessage();
        }
    }

    private function mapIngredients($recargas)
    {
        return collect($recargas)->map(function ($i) {
            return ['beneficio' => $i];
        });
    }
}
