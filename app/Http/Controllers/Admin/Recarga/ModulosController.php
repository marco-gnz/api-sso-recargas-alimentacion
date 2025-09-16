<?php

namespace App\Http\Controllers\Admin\Recarga;

use App\Exports\ReajustesExport;
use App\Http\Controllers\Controller;
use App\Http\Resources\RecargaReajustesResource;
use App\Http\Resources\RecargaResource;
use App\Models\Reajuste;
use App\Models\ReajusteEstado;
use App\Models\Recarga;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Log;

class ModulosController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth:sanctum']);
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
            'reajustes'
        ];

        return $with;
    }

    private function findRecarga($codigo)
    {
        $with       = $this->withRecarga();
        $recarga    = Recarga::where('codigo', $codigo)
            ->with($with)
            ->withCount('users')
            ->withCount('ausentismos')
            ->withCount('asignaciones')
            ->withCount('reajustes')
            ->withCount('contratos')
            ->withCount('viaticos')
            ->withCount('esquemas')
            ->first();

        return $recarga;
    }

    public function getAjustes($codigo, Request $request)
    {
        try {
            $recarga    = $this->findRecarga($codigo);

            $estados    = $request->estados;
            $query      = $this->consultarAjustes($recarga, $request);
            $ajustes    = $query->paginate(25);
            return response()->json(
                array(
                    'status'        => 'Success',
                    'title'         => null,
                    'message'       => null,
                    'pagination' => [
                        'total'         => $ajustes->total(),
                        'current_page'  => $ajustes->currentPage(),
                        'per_page'      => $ajustes->perPage(),
                        'last_page'     => $ajustes->lastPage(),
                        'from'          => $ajustes->firstItem(),
                        'to'            => $ajustes->lastPage()
                    ],
                    'recarga'       => RecargaResource::make($recarga),
                    'ajustes'       => RecargaReajustesResource::collection($ajustes),
                    'estados'       => ReajusteEstado::STATUS_IDS,
                    'tipo_ajustes'  => Reajuste::TYPE_IDS
                )
            );
        } catch (\Exception $error) {
            Log::info($error->getMessage());
            return response()->json($error->getMessage());
        }
    }

    private function consultarAjustes($recarga, Request $request)
    {
        $query = $recarga->reajustes();
        $this->aplicarFiltrosGenerales($query, $request);
        return $query;
    }

    private function aplicarFiltrosGenerales($query, Request $request)
    {
        $query->input($request->input)
            ->tipos($request->tipos)
            ->estados($request->estados)
            ->rebajaIncremento($request->rebaja_incremento)
            ->tipoCarga($request->tipo_carga)
            ->causalRebaja($request->causal_rebaja)
            ->causalIncremento($request->causal_incremento);
    }

    public function reajustesExport($codigo, Request $request)
    {
        try {
            $recarga = $this->findRecarga($codigo);
            $query = $this->consultarAjustes($recarga, $request);
            $registros_id = $query->pluck('id'); // no hace falta ->get()

            if ($registros_id->isEmpty()) {
                return response()->json([
                    'message' => 'No se encontraron resultados.'
                ], 404);
            }

            $name_field = "{$recarga->codigo}_ajustes.xlsx";
            return Excel::download(new ReajustesExport($registros_id), $name_field);
        } catch (\Exception $e) {
            \Log::error('Error al exportar data: ' . $e->getMessage());
            return response()->json([
                'error' => 'Error inesperado al exportar los datos.'
            ], 500);
        }
    }
}
