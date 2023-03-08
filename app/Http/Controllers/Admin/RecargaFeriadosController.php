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
            $new_feriados = [];
            $feriados_asociadas = [];
            $recarga = Recarga::where('codigo', $request->codigo_recarga)->firstOrFail();


            if ($request->feriados) {
                $feriados       = $request->feriados;
                $fechas_request = collect($request->feriados)->pluck('fecha');
                $fechas_db      = Feriado::whereIn('fecha', $fechas_request)->get();
                $fechas_date_pluck = $fechas_db->pluck('fecha')->toArray();

                foreach ($feriados as $feriado) {
                    if (!in_array($feriado['fecha'], $fechas_date_pluck)) {
                        $fecha = Carbon::parse($feriado['fecha']);
                        /* $data = [
                            'nombre'            => $feriado['nombre'],
                            'observacion'       => $feriado['observacion'] ? $feriado['observacion'] : NULL,
                            'anio'              => $fecha ? (int)$fecha->format('Y') : NULL,
                            'mes'               => $fecha ? $fecha->format('m') : NULL,
                            'fecha'             => $fecha ? $fecha->format('Y-m-d') : NULL,
                            'irrenunciable'     => $feriado['irrenunciable'] ? true : false,
                            'tipo'              => $feriado['tipo']
                        ]; */

                        $new_feriado = new Feriado();
                        $new_feriado->nombre            = $feriado['nombre'];
                        $new_feriado->observacion       = $feriado['observacion'] ? $feriado['observacion'] : NULL;
                        $new_feriado->anio              = $fecha ? (int)$fecha->format('Y') : NULL;
                        $new_feriado->mes               = $fecha ? $fecha->format('m') : NULL;
                        $new_feriado->fecha             = $fecha ? $fecha->format('Y-m-d') : NULL;
                        $new_feriado->irrenunciable     = $feriado['irrenunciable'] ? true : false;
                        $new_feriado->tipo              = $feriado['tipo'];
                        $new_feriado->save();
                        array_push($new_feriados, $new_feriado);

                        $feriado_recarga = $recarga->feriados()->where('fecha', $new_feriado->fecha)->first();

                        if (!$feriado_recarga) {
                            $date_feriado = Carbon::parse($new_feriado->fecha);
                            $is_sunday = $date_feriado->dayOfWeek === Carbon::SUNDAY ? true : false;

                            $asociados++;
                            $recarga->feriados()->attach([
                                'feriado_id'    => $new_feriado->id,
                                'active'     => $is_sunday ? false : true
                            ]);
                        }
                    }else{
                        $feriado_search      = Feriado::where('fecha', $feriado['fecha'])->first();

                        if($feriado_search){
                            $date_feriado = Carbon::parse($feriado_search->fecha);

                            $is_sunday = $date_feriado->dayOfWeek === Carbon::SUNDAY ? true : false;
                            $feriado_exist_in_recarga = $recarga->whereHas('feriados', function ($query)  use ($feriado_search) {
                                $query->where('feriado_recarga.feriado_id', $feriado_search->id);
                            })->first();

                            if(!$feriado_exist_in_recarga){
                                $asociados++;
                                $recarga->feriados()->attach([
                                    'feriado_id' => $feriado_search->id,
                                    'active'     => $is_sunday ? false : true
                                ]);
                            }

                        }

                    }
                }
                $feriados_recarga   = $recarga->feriados()->whereIn('fecha', $fechas_request)->pluck('fecha')->toArray();
                foreach ($feriados as $feriado) {
                    if (!in_array($feriado['fecha'], $feriados_recarga)) {
                        array_push($feriados_asociadas, $feriado);
                    }
                }

                $count_feriados = count($new_feriados);
                $me             = $count_feriados > 1 ? 'feriados' : 'feriado';
                $message        = "Se han ingresado {$count_feriados} {$me}.";

                $me_asociadas             = $asociados > 1 ? 'feriados' : 'feriado';
                $message_asociadas        = "Se asociaron {$asociados} {$me_asociadas} a la recarga.";

                return $this->successResponse($new_feriados, $message, $message_asociadas);
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

            if($delete){
                $message = 'Feriado eliminado de recarga con éxito';
                return $this->successResponse(null, $message);
            }
        } catch (\Exception $error) {
            return $error->getMessage();
        }
    }
}
