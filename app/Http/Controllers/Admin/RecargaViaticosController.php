<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\RecargaResource;
use App\Http\Resources\RecargaViaticosResource;
use App\Models\Recarga;
use Illuminate\Http\Request;

class RecargaViaticosController extends Controller
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

    public function returnViaticosRecarga($codigo, Request $request)
    {
        try {
            $input_query = $request->input;
            $recarga     = Recarga::where('codigo', $codigo)
                ->withCount('users')
                ->withCount('ausentismos')
                ->withCount('asignaciones')
                ->withCount('reajustes')
                ->withCount('contratos')
                ->withCount('viaticos')
                ->withCount('esquemas')
                ->firstOrFail();

            $viaticos = $recarga->viaticos()->input($input_query)
            ->descuento($request->descuento)
            ->descuentoTurnoLibre($request->descuento_turno_libre)
            ->orderBy('valor_viatico', 'asc')->paginate(50);

            return response()->json(
                array(
                    'status'    => 'Success',
                    'title'     => null,
                    'message'   => null,
                    'pagination' => [
                        'total'         => $viaticos->total(),
                        'current_page'  => $viaticos->currentPage(),
                        'per_page'      => $viaticos->perPage(),
                        'last_page'     => $viaticos->lastPage(),
                        'from'          => $viaticos->firstItem(),
                        'to'            => $viaticos->lastPage()
                    ],
                    'recarga'   => RecargaResource::make($recarga),
                    'viaticos'  => RecargaViaticosResource::collection($viaticos)
                )
            );
        } catch (\Exception $error) {
            return response()->json($error->getMessage());
        }
    }
}
