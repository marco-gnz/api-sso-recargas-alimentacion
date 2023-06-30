<?php

namespace App\Http\Controllers\Admin\Modulos;

use App\Http\Controllers\Controller;
use App\Http\Resources\FeriadosResource;
use App\Models\Establecimiento;
use App\Models\Feriado;
use App\Models\GrupoAusentismo;
use App\Models\Hora;
use App\Models\Ley;
use App\Models\Meridiano;
use App\Models\Recarga;
use App\Models\TipoAsistenciaTurno;
use App\Models\TipoAusentismo;
use App\Models\TipoIncremento;
use App\Models\Unidad;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Spatie\Permission\Models\Role;

class ModulosResponseController extends Controller
{
    public function getRoles()
    {
        $roles = Role::all();

        return response()->json($roles, 200);
    }

    public function getHoras()
    {
        $horas = Hora::orderBy('nombre', 'asc')->get();

        return response()->json($horas, 200);
    }

    public function returnEstablecimientos()
    {
        try {
            $establecimientos = Establecimiento::orderBy('nombre', 'asc')->get();

            return response()->json($establecimientos, 200);
        } catch (\Exception $error) {
            return $error->getMessage();
        }
    }

    public function returnLeyes()
    {
        try {
            $leyes = Ley::orderBy('nombre', 'asc')->get();

            return response()->json($leyes, 200);
        } catch (\Exception $error) {
            return $error->getMessage();
        }
    }

    public function returnUnidades()
    {
        try {
            $unidades = Unidad::orderBy('nombre', 'asc')->get();

            return response()->json($unidades, 200);
        } catch (\Exception $error) {
            return $error->getMessage();
        }
    }

    public function returnUnidadesRecarga($codigo_recarga)
    {
        try {
            $recarga    = Recarga::where('codigo', $codigo_recarga)->firstOrFail();
            $unidades = Unidad::whereHas('contratos', function($query) use($recarga){
                $query->where('recarga_id', $recarga->id);
            })->orderBy('nombre', 'asc')
            ->get()->unique('id');

            return response()->json($unidades, 200);
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

    public function returnGruposAusentismosRecarga($codigo_recarga)
    {
        try {
            $new_grupos = [];
            $recarga    = Recarga::where('codigo', $codigo_recarga)->firstOrFail();
            $grupos     = GrupoAusentismo::all();

            foreach ($grupos as $grupo) {
                $n_grupo = (int)$grupo->n_grupo;
                if ($n_grupo === 1) {
                    $total = $recarga->ausentismos()->where('grupo_id', 3)->count();
                    if ($total > 0) {
                        array_push($new_grupos, $grupo);
                    }
                } else {
                    array_push($new_grupos, $grupo);
                }
            }
            return response()->json($new_grupos, 200);
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
        $days               = 0;
        $monto_ajuste       = 0;
        $calculo_dias       = $request->calculo_dias === 'true' ? true : false;
        $fecha_inicio       = Carbon::parse($request->fecha_inicio);
        $fecha_termino      = Carbon::parse($request->fecha_termino);
        $days               = $fecha_inicio->diffInDays($fecha_termino) + 1;
        $valor_dia          = (int)$request->valor_dia;
        $diff_days          = $days;

        if ($calculo_dias) {
            $monto_ajuste   = $valor_dia * $days;
        } else {
            $fds        = 0;
            $period     = CarbonPeriod::create($request->fecha_inicio, $request->fecha_termino);
            $periodos   = $period->toArray();

            foreach ($periodos as $periodo) {
                $i_format = Carbon::parse($periodo)->isWeekend();
                if ($i_format) {
                    $fds++;
                }
            }

            $feriados       = Feriado::whereIn('fecha', [$request->fecha_inicio, $request->fecha_termino])->count();
            $days           = $days - $fds - $feriados;
            $monto_ajuste   = $valor_dia * $days;
        }

        $totales = (object) [
            'diff_days'             => $diff_days,
            'total_dias'            => $days,
            'monto_ajuste'          => $monto_ajuste,
            'monto_ajuste_format'   => number_format($monto_ajuste, 0, ",", "."),
        ];

        return response()->json(
            array(
                'status'        => 'Success',
                'title'         => null,
                'message'       => null,
                'totales'       => $totales,
            )
        );
    }

    public function getMontoInDays(Request $request)
    {
        $valor_dia          = (int)$request->valor_dia;
        $total_dias         = (int)$request->total_dias;

        $monto_ajuste       = $valor_dia * $total_dias;

        $totales = (object) [
            'monto_ajuste'          => $monto_ajuste,
            'monto_ajuste_format'   => number_format($monto_ajuste, 0, ",", "."),
        ];

        return response()->json(
            array(
                'status'        => 'Success',
                'title'         => null,
                'message'       => null,
                'totales'       => $totales,
            )
        );
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
            if ((is_array($feriados_1)) && (count($feriados_1) > 0)) {
                foreach ($feriados_1 as $f) {
                    array_push($feriados, $f);
                }
            }
            if ((is_array($feriados_2)) && (count($feriados_2) > 0)) {
                foreach ($feriados_2 as $f) {
                    array_push($feriados, $f);
                }
            }

            if (count($feriados) > 0) {
                $fechas                     = collect($feriados)->pluck('fecha');
                $feriados_recarga_query     = $recarga->feriados()->whereIn('fecha', $fechas)->get();

                if (count($feriados_recarga_query) > 0) {
                    $feriados_recarga_query = $feriados_recarga_query->pluck('fecha')->toArray();
                    $feriados_recarga       = $feriados_recarga_query;
                } else {
                    $feriados_recarga_query = [];
                    $feriados_recarga       = [];
                }


                if (count($feriados) > 0) {
                    foreach ($feriados as $feriado) {
                        if (isset($feriado['fecha']) && !in_array($feriado['fecha'], $feriados_recarga)) {
                            array_push($new_feriados, $feriado);
                        }
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
            $feriados       = [];
            $url            = "https://apis.digital.gob.cl/fl/feriados/{$recarga->anio_calculo}/{$recarga->mes_calculo}";
            $api            = Http::get($url);
            $apiResponse    = $api->body();

            $feriados = json_decode($apiResponse, true, 512, JSON_UNESCAPED_UNICODE);
            if (is_array($feriados)) {
                return $feriados;
            } else {
                return [];
            }
        } catch (\Error $error) {
            return $error->getMessage();
        }
    }

    private function feriadosFechaBeneficio($recarga)
    {
        try {
            $feriados       = [];
            $url            = "https://apis.digital.gob.cl/fl/feriados/{$recarga->anio_beneficio}/{$recarga->mes_beneficio}";
            $api            = Http::get($url);
            $apiResponse    = $api->body();

            $feriados = json_decode($apiResponse, true, 512, JSON_UNESCAPED_UNICODE);
            if (is_array($feriados)) {
                return $feriados;
            } else {
                return [];
            }
        } catch (\Error $error) {
            return $error->getMessage();
        }
    }
}
