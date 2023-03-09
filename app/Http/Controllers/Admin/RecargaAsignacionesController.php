<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\FuncionarioTurnosResource;
use App\Models\Recarga;
use Illuminate\Http\Request;

class RecargaAsignacionesController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth:sanctum']);
    }

    public function returnAsignacionesRecarga($codigo, Request $request)
    {
        $input_query = $request->input;
        $recarga     = Recarga::where('codigo', $codigo)->firstOrFail();

        $asignaciones = $recarga->asignaciones()->input($input_query)->valueAsignaciones($request->is_asignaciones)->paginate(100);

        return response()->json(
            array(
                'status'    => 'Success',
                'title'     => null,
                'message'   => null,
                'pagination' => [
                    'total'         => $asignaciones->total(),
                    'current_page'  => $asignaciones->currentPage(),
                    'per_page'      => $asignaciones->perPage(),
                    'last_page'     => $asignaciones->lastPage(),
                    'from'          => $asignaciones->firstItem(),
                    'to'            => $asignaciones->lastPage()
                ],
                'asignaciones'   => FuncionarioTurnosResource::collection($asignaciones),
            )
        );
    }
}
