<?php

namespace App\Http\Controllers\Admin\Mantenedores;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Mantenedores\Cargo\StoreAsignacionRequest;
use App\Http\Requests\Admin\Mantenedores\Cargo\StoreCargoRequest;
use App\Http\Requests\Admin\Mantenedores\Cargo\StoreUnidadRequest;
use App\Http\Requests\Admin\Mantenedores\Cargo\UpdateAsignacionRequest;
use App\Http\Requests\Admin\Mantenedores\Cargo\UpdateCargoRequest;
use App\Http\Requests\Admin\Mantenedores\Cargo\UpdateUnidadRequest;
use App\Http\Resources\Mantenedores\ContractualesResource;
use App\Models\Cargo;
use App\Models\ProcesoTurno;
use App\Models\Unidad;
use Illuminate\Http\Request;

class ContractualesController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth:sanctum']);
    }

    public function getDatoContractual(Request $request)
    {
        try {
            $tipo       = $request->tipo;
            switch ($tipo) {
                case 'cargo':
                    $data = Cargo::find($request->id);
                    break;

                case 'unidad':
                    $data = Unidad::find($request->id);
                    break;

                case 'asignacion':
                    $data = ProcesoTurno::find($request->id);
                    break;
            }
            return response()->json(
                array(
                    'status'        => 'Success',
                    'title'         => null,
                    'message'       => null,
                    'data'          => ContractualesResource::make($data)
                )
            );
        } catch (\Exception $error) {
            return response()->json($error->getMessage());
        }
    }

    public function getDatosContractuales(Request $request)
    {
        try {
            $tipo       = $request->tipo;
            $data       = [];
            $paginate   = 50;
            $order      = 'ASC';
            switch ($tipo) {
                case 'cargo':
                    $data = Cargo::input($request->input)->orderBy('nombre', $order)->paginate($paginate);
                    break;

                case 'unidad':
                    $data = Unidad::input($request->input)->orderBy('nombre', $order)->paginate($paginate);
                    break;

                case 'asignacion':
                    $data = ProcesoTurno::input($request->input)->orderBy('nombre', $order)->paginate($paginate);
                    break;
            }

            return response()->json(
                array(
                    'status'        => 'Success',
                    'title'         => null,
                    'message'       => null,
                    'pagination' => [
                        'total'         => $data->total(),
                        'current_page'  => $data->currentPage(),
                        'per_page'      => $data->perPage(),
                        'last_page'     => $data->lastPage(),
                        'from'          => $data->firstItem(),
                        'to'            => $data->lastPage()
                    ],
                    'data'              => ContractualesResource::collection($data)
                )
            );
        } catch (\Exception $error) {
            return response()->json($error->getMessage());
        }
    }

    public function storeCargo(StoreCargoRequest $request)
    {
        try {

            $form = ['cod_sirh', 'nombre'];
            $cargo = Cargo::create($request->only($form));

            if ($cargo) {
                return response()->json(
                    array(
                        'status'        => 'Success',
                        'title'         => "Nuevo cargo ingresado con éxito.",
                        'message'       => null,
                        'data'          => ContractualesResource::make($cargo)
                    )
                );
            }
        } catch (\Exception $error) {
            return response()->json($error->getMessage());
        }
    }

    public function updateCargo($id, UpdateCargoRequest $request)
    {
        try {
            $cargo = Cargo::find($id);
            $form = ['cod_sirh', 'nombre'];

            if($cargo){
                $update = $cargo->update($request->only($form));

                if ($update) {
                    return response()->json(
                        array(
                            'status'        => 'Success',
                            'title'         => "Cargo modificado con éxito.",
                            'message'       => null,
                            'data'          => ContractualesResource::make($cargo)
                        )
                    );
                }
            }
        } catch (\Exception $error) {
            return response()->json($error->getMessage());
        }
    }

    public function storeUnidad(StoreUnidadRequest $request)
    {
        try {

            $form = ['cod_sirh', 'nombre'];
            $unidad = Unidad::create($request->only($form));

            if ($unidad) {
                return response()->json(
                    array(
                        'status'        => 'Success',
                        'title'         => "Nuevo unidad ingresada con éxito.",
                        'message'       => null,
                        'data'          => ContractualesResource::make($unidad)
                    )
                );
            }
        } catch (\Exception $error) {
            return response()->json($error->getMessage());
        }
    }

    public function updateUnidad($id, UpdateUnidadRequest $request)
    {
        try {
            $unidad = Unidad::find($id);
            $form = ['cod_sirh', 'nombre'];

            if($unidad){
                $update = $unidad->update($request->only($form));

                if ($update) {
                    return response()->json(
                        array(
                            'status'        => 'Success',
                            'title'         => "Unidad modificada con éxito.",
                            'message'       => null,
                            'data'          => ContractualesResource::make($unidad)
                        )
                    );
                }
            }
        } catch (\Exception $error) {
            return response()->json($error->getMessage());
        }
    }

    public function storeAsignacion(StoreAsignacionRequest $request)
    {
        try {

            $form = ['cod_sirh', 'nombre'];
            $proceso = ProcesoTurno::create($request->only($form));

            if ($proceso) {
                return response()->json(
                    array(
                        'status'        => 'Success',
                        'title'         => "Nuevo proceso ingresado con éxito.",
                        'message'       => null,
                        'data'          => ContractualesResource::make($proceso)
                    )
                );
            }
        } catch (\Exception $error) {
            return response()->json($error->getMessage());
        }
    }

    public function updateAsignacion($id, UpdateAsignacionRequest $request)
    {
        try {
            $asignacion = ProcesoTurno::find($id);
            $form = ['cod_sirh', 'nombre'];

            if($asignacion){
                $update = $asignacion->update($request->only($form));

                if ($update) {
                    return response()->json(
                        array(
                            'status'        => 'Success',
                            'title'         => "Asignación modificada con éxito.",
                            'message'       => null,
                            'data'          => ContractualesResource::make($asignacion)
                        )
                    );
                }
            }
        } catch (\Exception $error) {
            return response()->json($error->getMessage());
        }
    }
}
