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

class RecargaResumenController extends Controller
{
    /* public function __construct()
    {
        $this->middleware(['auth:sanctum']);
    } */

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
            $recarga = Recarga::where('codigo', $codigo)->withCount('users')->withCount('ausentismos')->withCount('asignaciones')->withCount('reajustes')->withCount('contratos')->withCount('viaticos')->with($with)->first();

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

    public function withFnRecargas($recarga)
    {
        $function = ['recargas' => function ($query) use ($recarga) {
            $query->where('recarga_user.recarga_id', $recarga->id);
        }];
        return $function;
    }

    public function returnFuncionariosToRecarga($codigo, Request $request)
    {
        try {
            $funcionarios   = [];
            $input_query    = $request->input;
            $with           = $this->withRecarga();
            $recarga        = Recarga::with($with)->where('codigo', $codigo)->withCount('users')->withCount('ausentismos')->withCount('asignaciones')->withCount('reajustes')->withCount('contratos')->withCount('viaticos')->first();

            $withFnAusentismos      = $this->withFnAusentismos($recarga);
            $withFnContratos        = $this->withFnContratos($recarga);
            $withFnAsistencias      = $this->withFnAsistencias($recarga);
            $withFnAjustes          = $this->withFnAjustes($recarga);
            $withFnTurnos           = $this->withFnTurnos($recarga);
            $withFnViaticos         = $this->withFnViaticos($recarga);
            $withFnRecargas         = $this->withFnRecargas($recarga);

            $users                  = $recarga->users()
                ->with($withFnAusentismos)
                ->with($withFnContratos)
                ->with($withFnAsistencias)
                ->with($withFnAjustes)
                ->with($withFnTurnos)
                ->with($withFnViaticos)
                ->with($withFnRecargas)
                ->input($input_query)
                ->paginate(40);

            return response()->json(
                array(
                    'status'    => 'Success',
                    'title'     => null,
                    'message'   => null,
                    'pagination' => [
                        'total'         => $users->total(),
                        'current_page'  => $users->currentPage(),
                        'per_page'      => $users->perPage(),
                        'last_page'     => $users->lastPage(),
                        'from'          => $users->firstItem(),
                        'to'            => $users->lastPage()
                    ],
                    'users'     => TablaResumenResource::collection($users),
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
            $recarga        = Recarga::where('codigo', $request->codigo_recarga)->withCount('users')->withCount('ausentismos')->withCount('asignaciones')->withCount('reajustes')->withCount('contratos')->withCount('viaticos')->first();

            $withFnAusentismos      = $this->withFnAusentismos($recarga);
            $withFnContratos        = $this->withFnContratos($recarga);
            $withFnAsistencias      = $this->withFnAsistencias($recarga);
            $withFnAjustes          = $this->withFnAjustes($recarga);
            $withFnTurnos           = $this->withFnTurnos($recarga);
            $withFnViaticos         = $this->withFnViaticos($recarga);
            $withFnRecargas         = $this->withFnRecargas($recarga);


            $funcionario = User::with($withFnAusentismos)
                ->with($withFnContratos)
                ->with($withFnAsistencias)
                ->with($withFnAjustes)
                ->with($withFnTurnos)
                ->with($withFnViaticos)
                ->with($withFnRecargas)
                ->find($request->user_id);

            if ($funcionario) {
                $value = $funcionario->recargas->where('id', $recarga->id)->first()->pivot->beneficio;
                $sync  = $funcionario->recargas()->sync([$recarga->id => array(
                    'recarga_id'    => $recarga->id,
                    'user_id'       => $funcionario->id,
                    'beneficio'     => $value ? false : true
                )]);

                $funcionario = $funcionario->fresh();
                $status         = $value ? 'deshabilitado' : 'habilitado';
                $status_count   = $value ? 'No será ' : 'Será';
                $title          = 'Funcionario modificado';
                $message        =  "Se ha {$status} el beneficio a {$funcionario->nombre_completo}. {$status_count} contabilizado en el monto total.";
                return response()->json(
                    array(
                        'status'    => 'Success',
                        'title'     => $title,
                        'message'   => $message,
                        'user'      => TablaResumenResource::make($funcionario),
                        'recarga'   => RecargaResumenResource::make($recarga)
                    )
                );
                /* return $this->successResponse(FuncionariosResumenResource::make($funcionario),$title , $message, 200); */
                /* return $this->successResponse(array(FuncionariosResumenResource::make($funcionario), RecargaResumenResource::make($recarga)), $title, $message, 200); */
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
