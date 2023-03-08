<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\RecargaContratosResource;
use App\Models\Recarga;
use Illuminate\Http\Request;

class RecargaContratosController extends Controller
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
            'users'
        ];

        return $with;
    }
    public function returnContratosRecarga($codigo, Request $request)
    {
        try {
            $input_query = $request->input;
            $recarga     = Recarga::where('codigo', $codigo)->firstOrFail();

            $contratos = $recarga->contratos()->input($input_query)->paginate(100);

            return response()->json(
                array(
                    'status'    => 'Success',
                    'title'     => null,
                    'message'   => null,
                    'pagination' => [
                        'total'         => $contratos->total(),
                        'current_page'  => $contratos->currentPage(),
                        'per_page'      => $contratos->perPage(),
                        'last_page'     => $contratos->lastPage(),
                        'from'          => $contratos->firstItem(),
                        'to'            => $contratos->lastPage()
                    ],
                    'contratos' => RecargaContratosResource::collection($contratos)
                )
            );
        } catch (\Exception $error) {
            return response()->json($error->getMessage());
        }
    }
}
