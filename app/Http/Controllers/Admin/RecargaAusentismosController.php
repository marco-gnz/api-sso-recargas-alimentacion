<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\AusentismosResource;
use App\Http\Resources\FuncionarioAusentismosResource;
use App\Http\Resources\GrupoAusentismoResource;
use App\Http\Resources\RecargaResource;
use App\Models\GrupoAusentismo;
use App\Models\Recarga;
use App\Models\TipoAusentismo;
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
            $recarga            = Recarga::where('codigo', $codigo)
                ->withCount('users')
                ->withCount('ausentismos')
                ->withCount('asignaciones')
                ->withCount('reajustes')
                ->withCount('contratos')
                ->withCount('viaticos')
                ->withCount('esquemas')
                ->firstOrFail();

            $withFnAusentismos  = $this->withFnAusentismos($recarga);
            $grupos             = GrupoAusentismo::with($withFnAusentismos)->get();

            $ausentismos                = $recarga->ausentismos()->where('grupo_id', $request->grupo)->input($input_query)->tipoAusentismo($request->tipo_ausentismo_id)->descuentoTurnoLibre($request->descuento_turno_libre)->descuento($request->descuento)->orderBy('id', 'asc')->paginate(50);
            $tipo_ausentismo_id_pluck   = $recarga->reglas()->where('grupo_id', $request->grupo)->pluck('tipo_ausentismo_id')->unique();
            $tipo_ausentismos           = TipoAusentismo::whereIn('id', $tipo_ausentismo_id_pluck)->get();

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
                    'recarga'       => RecargaResource::make($recarga),
                    'ausentismos'   => FuncionarioAusentismosResource::collection($ausentismos),
                    'grupos'        => GrupoAusentismoResource::collection($grupos),
                    'tipo_ausentismos'  => $tipo_ausentismos
                )
            );
        } catch (\Exception $error) {
            return response()->json($error->getMessage());
        }
    }
}
