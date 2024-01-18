<?php

namespace App\Http\Controllers\Admin\Mantenedores;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Mantenedores\Variaciones\TipoAusentismo\TipoAusentismoStoreRequest;
use App\Http\Requests\Admin\Mantenedores\Variaciones\TipoAusentismo\TipoAusentismoUpdateRequest;
use App\Http\Requests\Admin\Mantenedores\Variaciones\TipoIncremento\TipoIncrementoStoreRequest;
use App\Http\Requests\Admin\Mantenedores\Variaciones\TipoIncremento\TipoIncrementoUpdateRequest;
use App\Http\Resources\Mantenedores\VariacionesResource;
use App\Models\TipoAusentismo;
use App\Models\TipoIncremento;
use Illuminate\Http\Request;

class VariacionesController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth:sanctum']);
    }

    public function getVariacion(Request $request)
    {
        try {
            $tipo       = $request->tipo;
            switch ($tipo) {
                case 'ausentismo':
                    $data = TipoAusentismo::find($request->id);
                    break;

                case 'incremento':
                    $data = TipoIncremento::find($request->id);
                    break;
            }
            return response()->json(
                array(
                    'status'        => 'Success',
                    'title'         => null,
                    'message'       => null,
                    'data'          => VariacionesResource::make($data)
                )
            );
        } catch (\Exception $error) {
            return response()->json($error->getMessage());
        }
    }

    public function getVariaciones(Request $request)
    {
        try {
            $tipo       = $request->tipo;
            $data       = [];
            $paginate   = 50;
            $order      = 'ASC';
            switch ($tipo) {
                case 'ausentismo':
                    $data = TipoAusentismo::input($request->input)->orderBy('nombre', $order)->paginate($paginate);
                    break;

                case 'incremento':
                    $data = TipoIncremento::input($request->input)->orderBy('nombre', $order)->paginate($paginate);
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
                    'data'              => VariacionesResource::collection($data)
                )
            );
        } catch (\Exception $error) {
            return response()->json($error->getMessage());
        }
    }

    public function storeAusentismo(TipoAusentismoStoreRequest $request)
    {
        try {

            $form = ['codigo_sirh', 'nombre', 'sigla'];
            $tipo_ausentismo = TipoAusentismo::create($request->only($form));

            if ($tipo_ausentismo) {
                return response()->json(
                    array(
                        'status'        => 'Success',
                        'title'         => "Tipo de ausentismo ingresado con éxito.",
                        'message'       => null,
                        'data'          => VariacionesResource::make($tipo_ausentismo)
                    )
                );
            }
        } catch (\Exception $error) {
            return response()->json($error->getMessage());
        }
    }

    public function updateAusentismo($id, TipoAusentismoUpdateRequest $request)
    {
        try {
            $tipo_ausentismo = TipoAusentismo::find($id);
            $form = ['codigo_sirh', 'nombre', 'sigla'];

            if($tipo_ausentismo){
                $update = $tipo_ausentismo->update($request->only($form));

                if ($update) {
                    return response()->json(
                        array(
                            'status'        => 'Success',
                            'title'         => "Tipo de ausentismo modificado con éxito.",
                            'message'       => null,
                            'data'          => VariacionesResource::make($tipo_ausentismo)
                        )
                    );
                }
            }
        } catch (\Exception $error) {
            return response()->json($error->getMessage());
        }
    }

    public function storeIncremento(TipoIncrementoStoreRequest $request)
    {
        try {

            $form = ['codigo_sirh', 'nombre', 'sigla'];
            $tipo_incremento = TipoIncremento::create($request->only($form));

            if ($tipo_incremento) {
                return response()->json(
                    array(
                        'status'        => 'Success',
                        'title'         => "Tipo de incremento ingresado con éxito.",
                        'message'       => null,
                        'data'          => VariacionesResource::make($tipo_incremento)
                    )
                );
            }
        } catch (\Exception $error) {
            return response()->json($error->getMessage());
        }
    }

    public function updateIncremento($id, TipoIncrementoUpdateRequest $request)
    {
        try {
            $tipo_incremento = TipoIncremento::find($id);
            $form = ['codigo_sirh', 'nombre', 'sigla'];

            if($tipo_incremento){
                $update = $tipo_incremento->update($request->only($form));

                if ($update) {
                    return response()->json(
                        array(
                            'status'        => 'Success',
                            'title'         => "Tipo de incremento modificado con éxito.",
                            'message'       => null,
                            'data'          => VariacionesResource::make($tipo_incremento)
                        )
                    );
                }
            }
        } catch (\Exception $error) {
            return response()->json($error->getMessage());
        }
    }
}
