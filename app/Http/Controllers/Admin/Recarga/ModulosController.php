<?php

namespace App\Http\Controllers\Admin\Recarga;

use App\Http\Controllers\Controller;
use App\Http\Resources\RecargaReajustesResource;
use App\Http\Resources\RecargaResource;
use App\Models\Reajuste;
use App\Models\ReajusteEstado;
use App\Models\Recarga;
use Illuminate\Http\Request;

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
            $ajustes    = $recarga->reajustes()->input($request->input)->tipos($request->tipos)->estados($request->estados)->rebajaIncremento($request->rebaja_incremento)->paginate(40);

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
            return response()->json($error->getMessage());
        }
    }
}
