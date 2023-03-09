<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\AusentismosResource;
use App\Http\Resources\FuncionarioAusentismosResource;
use App\Http\Resources\GrupoAusentismoResource;
use App\Models\GrupoAusentismo;
use App\Models\Recarga;
use Illuminate\Http\Request;

class RecargaAusentismosController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth:sanctum']);
    }

    public function withFnAusentismos($recarga)
    {
        $function = ['ausentismos' => function ($query) use ($recarga) {
            $query->where('recarga_id', $recarga->id)->get();
        }];
        return $function;
    }

    public function returnAusentismosRecarga($codigo, Request $request)
    {
        try {
            $input_query        = $request->input;
            $recarga            = Recarga::where('codigo', $codigo)->firstOrFail();
            $withFnAusentismos  = $this->withFnAusentismos($recarga);

            $grupos             = GrupoAusentismo::with($withFnAusentismos)->get();
            $ausentismos        = $recarga->ausentismos()->where('grupo_id', $request->grupo)->input($input_query)->orderBy('id', 'asc')->paginate(50);

            return response()->json(
                array(
                    'status'    => 'Success',
                    'title'     => null,
                    'message'   => null,
                    'pagination' => [
                        'total'         => $ausentismos->total(),
                        'current_page'  => $ausentismos->currentPage(),
                        'per_page'      => $ausentismos->perPage(),
                        'last_page'     => $ausentismos->lastPage(),
                        'from'          => $ausentismos->firstItem(),
                        'to'            => $ausentismos->lastPage()
                    ],
                    'ausentismos'   => FuncionarioAusentismosResource::collection($ausentismos),
                    'grupos'        => GrupoAusentismoResource::collection($grupos)
                )
            );
        } catch (\Exception $error) {
            return response()->json($error->getMessage());
        }
    }
}
