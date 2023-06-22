<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Feriados\StoreFeriadosRequest;
use App\Http\Resources\RecargaFeriadosResource;
use App\Models\Feriado;
use App\Models\Recarga;
use Carbon\Carbon;
use Illuminate\Http\Request;

class RecargaFeriadosController extends Controller
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

    public function storeFeriados(StoreFeriadosRequest $request)
    {
        try {
            $asociados = 0;
            $feriados_db_fecha  = array();
            $recarga            = Recarga::where('codigo', $request->codigo_recarga)->firstOrFail();
            $request_feriados   = $request->feriados;

            if ($request_feriados) {
                foreach ($request_feriados as $feriado) {
                    $fecha_feriado_format   = Carbon::parse($feriado['fecha']);
                    $is_sunday              = $fecha_feriado_format->dayOfWeek === Carbon::SUNDAY ? true : false;
                    $feriado_db             = Feriado::where('fecha', $fecha_feriado_format->format('Y-m-d'))->first();

                    if ($feriado_db) {
                        $existe_feriado_en_recarga = $recarga->whereHas('feriados', function ($query) use ($feriado_db, $recarga) {
                            $query->where('feriado_recarga.feriado_id', $feriado_db->id)
                                ->where('feriado_recarga.recarga_id', $recarga->id);
                        })->count();

                        if ($existe_feriado_en_recarga <= 0) {
                            $recarga->feriados()->attach($feriado_db->id, [
                                'active'     => $is_sunday ? false : true
                            ]);

                            $asociados++;

                        }
                    } else {
                        $fecha  = Carbon::parse($feriado['fecha']);
                        $data   = [
                            'nombre'            => $feriado['nombre'],
                            'observacion'       => $feriado['observacion'] ? $feriado['observacion'] : NULL,
                            'anio'              => $fecha ? (int)$fecha->format('Y') : NULL,
                            'mes'               => $fecha ? (int)$fecha->format('m') : NULL,
                            'fecha'             => $fecha ? (int)$fecha->format('Y-m-d') : NULL,
                            'irrenunciable'     => $feriado['irrenunciable'] ? true : false,
                            'tipo'              => $feriado['tipo'] ? $feriado['tipo'] : NULL,
                        ];

                        $new_feriado = Feriado::create($data);

                        if ($new_feriado) {
                            $is_sunday  = $fecha->dayOfWeek === Carbon::SUNDAY ? true : false;

                            $recarga->feriados()->attach($new_feriado->id, [
                                'active'     => $is_sunday ? false : true
                            ]);
                            $asociados++;
                        }
                    }
                }

                if ($asociados > 0) {
                    return response()->json(
                        array(
                            'status'    => 'Success',
                            'title'     => null,
                            'message'   => "{$asociados} feriados ingresado."
                        )
                    );
                } else {
                    return response()->json(
                        array(
                            'status'    => 'Error',
                            'title'     => null,
                            'message'   => "{$asociados} feriados imgresado."
                        )
                    );
                }
            }
        } catch (\Exception $error) {
            return $error->getMessage();
        }
    }

    public function deleteFeriadoInRecarga($feriado_id, $recarga_codigo)
    {
        try {
            $recarga = Recarga::where('codigo', $recarga_codigo)->firstOrFail();
            $feriado = Feriado::find($feriado_id);

            $delete = $recarga->feriados()->detach($feriado->id);

            if ($delete) {
                $message = 'Feriado eliminado de recarga con éxito';
                return $this->successResponse(null, $message);
            }
        } catch (\Exception $error) {
            return $error->getMessage();
        }
    }
}
