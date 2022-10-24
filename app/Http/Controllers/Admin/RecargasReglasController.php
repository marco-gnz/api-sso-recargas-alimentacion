<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Reglas\StoreReglaController;
use App\Models\GrupoAusentismo;
use App\Models\Recarga;
use App\Models\Regla;
use App\Models\SeguimientoRecarga;
use App\Models\TipoAusentismo;
use Illuminate\Http\Request;

class RecargasReglasController extends Controller
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

    public function returnReglasToGrupo(Request $request)
    {
        try {
            $grupo      = GrupoAusentismo::find($request->grupo_id);
            $recarga    = Recarga::where('codigo', $request->recarga_codigo)->first();

            if($grupo && $recarga){
                $reglas = Regla::with('tipoAusentismo', 'meridianos')->where('recarga_id', $recarga->id)->where('grupo_id', $grupo->id)->get();

                return $this->successResponse($reglas, null, null, 200);
            }
        } catch (\Exception $error) {
            return $error->getMessage();
        }
    }

    public function returnTiposAusentismos($codigo)
    {
        try {
            $reglas             = [];
            $tipos_ausentismos  = TipoAusentismo::orderBy('nombre', 'asc')->where('estado', true)->get();
            $recarga            = Recarga::where('codigo', $codigo)->first();

            if ($recarga) {
                foreach ($tipos_ausentismos as $tipo_ausentismo) {
                    $tipo_ausentismo->{'recarga_id'}            = $recarga->id;
                    $tipo_ausentismo->{'tipo_ausentismo_id'}    = $tipo_ausentismo->id;
                    $tipo_ausentismo->{'nombre'}                = $tipo_ausentismo->nombre;
                    $tipo_ausentismo->{'grupo_id'}              = 1;
                    $tipo_ausentismo->{'active'}                = false;
                    $tipo_ausentismo->{'meridiano'}             = [];
                    $tipo_ausentismo->{'hora_inicio'}           = NULL;
                    $tipo_ausentismo->{'hora_termino'}          = NULL;
                    array_push($reglas, $tipo_ausentismo);
                }
            }
            return response()->json($reglas, 200);
        } catch (\Exception $error) {
            return $error->getMessage();
        }
    }

    public function storeReglas(StoreReglaController $request)
    {
        try {
            $reglas = $request['reglas'];

            $recarga = Recarga::find($reglas[0]['recarga_id']);

            foreach ($reglas as $regla) {
                $data = [
                    'recarga_id'                => $regla['recarga_id'],
                    'tipo_ausentismo_id'        => $regla['tipo_ausentismo_id'],
                    'grupo_id'                  => $regla['grupo_id'],
                    'active'                    => $regla['active'],
                    'hora_inicio'               => $regla['hora_inicio'],
                    'hora_termino'              => $regla['hora_termino']
                ];

                $new_regla = Regla::create($data);

                if ($regla['grupo_id'] === 2 && $regla['active'] === true && $regla['meridiano']) {
                    if ($new_regla) {
                        $new_regla->meridianos()->attach($regla['meridiano']);
                    }
                }
            }

            $estado = SeguimientoRecarga::create([
                'recarga_id'    => $recarga->id,
                'estado_id'     => 2
            ]);
            return $this->successResponse($reglas, count($reglas) . ' reglas ingresadas correctamente.', null, 200);
        } catch (\Exception $error) {
            return response()->json(array($error->getMessage(), $error->failures()));
        }
    }
}
