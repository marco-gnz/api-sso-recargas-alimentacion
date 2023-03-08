<?php

namespace App\Http\Controllers\Admin\Modulos;

use App\Http\Controllers\Controller;
use App\Http\Resources\FeriadosResource;
use App\Models\Establecimiento;
use App\Models\GrupoAusentismo;
use App\Models\Meridiano;
use App\Models\Recarga;
use App\Models\TipoAsistenciaTurno;
use App\Models\TipoAusentismo;
use App\Models\TipoIncremento;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class ModulosResponseController extends Controller
{
    public function returnEstablecimientos()
    {
        try {
            $establecimientos = Establecimiento::orderBy('nombre', 'asc')->get();

            return response()->json($establecimientos, 200);
        } catch (\Exception $error) {
            return $error->getMessage();
        }
    }

    public function returnTiposAusentismos()
    {
        try {
            $tipos_ausentismos = TipoAusentismo::orderBy('nombre', 'asc')->get();

            return response()->json($tipos_ausentismos, 200);
        } catch (\Exception $error) {
            return $error->getMessage();
        }
    }

    public function returnTiposIncrementos()
    {
        try {
            $tipo_incrementos = TipoIncremento::orderBy('nombre', 'asc')->get();

            return response()->json($tipo_incrementos, 200);
        } catch (\Exception $error) {
            return $error->getMessage();
        }
    }

    public function returnMeridianos()
    {
        try {
            $meridianos = Meridiano::orderBy('id', 'asc')->get();

            return response()->json($meridianos, 200);
        } catch (\Exception $error) {
            return $error->getMessage();
        }
    }

    public function returnGruposAusentismos()
    {
        try {
            $grupos_ausentismos = GrupoAusentismo::where('estado', true)->orderBy('nombre', 'asc')->get();

            return response()->json($grupos_ausentismos, 200);
        } catch (\Exception $error) {
            return $error->getMessage();
        }
    }

    public function returnTipoAsistenciaTurno()
    {
        try {
            $tipos_asistencia_turnos = TipoAsistenciaTurno::orderBy('nombre', 'asc')->get();

            return response()->json($tipos_asistencia_turnos, 200);
        } catch (\Exception $error) {
            return $error->getMessage();
        }
    }

    public function returnDaysInDate(Request $request)
    {
        $incremento  = $request->incremento ? true : false;
        $fecha_inicio   = Carbon::parse($request->fecha_inicio);
        $fecha_termino  = Carbon::parse($request->fecha_termino);
        $days           = $fecha_inicio->diffInDays($fecha_termino) + 1;
        $days           = (float)$days;

        if (!$incremento) {
            $days =  $days * -1;
        }

        return response()->json($days, 200);
    }

    public function returnFeriados($codigo)
    {
        try {
            $new_feriados           = [];
            $feriados_recarga       = [];
            $feriados               = [];
            $feriados_recarga_query = [];
            $recarga                = Recarga::where('codigo', $codigo)->firstOrFail();
            $url                    = "https://apis.digital.gob.cl/fl/feriados/{$recarga->anio_calculo}/{$recarga->mes_calculo}";

            $feriados_1 = $this->feriadosFechaCalculo($recarga);
            $feriados_2 = $this->feriadosFechaBeneficio($recarga);

            $feriados = array_merge($feriados_1, $feriados_2);
            if (count($feriados) > 0) {
                $fechas                     = collect($feriados)->pluck('fecha');
                $feriados_recarga_query     = $recarga->feriados()->whereIn('fecha', $fechas)->pluck('fecha')->toArray();

                if (count($feriados_recarga_query) > 0) {
                    $feriados_recarga = $feriados_recarga_query;
                }

                foreach ($feriados as $feriado) {
                    if (!in_array($feriado->fecha, $feriados_recarga)) {
                        array_push($new_feriados, $feriado);
                    }
                }
            }
            return FeriadosResource::collection($new_feriados);
        } catch (\Exception $error) {
            return $error->getMessage();
        }
    }

    private function feriadosFechaCalculo($recarga)
    {
        try {
            $feriados = [];
            $url      = "https://apis.digital.gob.cl/fl/feriados/{$recarga->anio_calculo}/{$recarga->mes_calculo}";
            $api      = Http::get($url);
            define('API_1', $api);

            $feriados        = $api->object();
            if (($feriados) && (count($feriados) > 0)) {
                $feriados        = collect($feriados);
                $feriados        = $feriados->toArray();
                define('FERIADOS_1', json_encode($feriados));
            }
            return $feriados;
        } catch (\Error $error) {
            return $error->getMessage();
        }
    }

    private function feriadosFechaBeneficio($recarga)
    {
        try {
            $feriados  = [];
            $url       = "https://apis.digital.gob.cl/fl/feriados/{$recarga->anio_beneficio}/{$recarga->mes_beneficio}";
            $api       = Http::get($url);
            define('API_2', $api);

            $feriados        = $api->object();
            if (($feriados) && (count($feriados) > 0)) {
                $feriados        = collect($feriados);
                $feriados        = $feriados->toArray();
                define('FERIADOS_2', json_encode($feriados));
            }
            return $feriados;
        } catch (\Error $error) {
            return $error->getMessage();
        }
    }
}
