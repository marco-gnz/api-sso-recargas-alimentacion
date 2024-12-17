<?php

namespace App\Http\Controllers\Admin\Modulos;

use App\Http\Controllers\Controller;
use App\Http\Resources\FeriadosResource;
use App\Models\Esquema;
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
use Illuminate\Support\Facades\Log;
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
            $unidades = Unidad::whereHas('contratos', function ($query) use ($recarga) {
                $query->where('recarga_id', $recarga->id);
            })->orderBy('nombre', 'asc')
                ->get()->unique('id');

            return response()->json($unidades, 200);
        } catch (\Exception $error) {
            return $error->getMessage();
        }
    }

    public function returnCentroCostosRecarga($codigo_recarga)
    {
        try {
            $recarga    = Recarga::where('codigo', $codigo_recarga)->firstOrFail();
            $centros_costos = $recarga->contratos()->pluck('centro_costo')->unique()->values()->toArray();

            return response()->json($centros_costos, 200);
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
        try {
            $modificar_dias     = (int)$request->modificar_dias;
            $modificar_dias     = $modificar_dias ? true : false;
            $esquema            = Esquema::find($request->esquema_id);
            $valor_dia          = $esquema->recarga->monto_dia;
            $rebaja_dias        = (int)$request->rebaja_dias;
            $rebaja_dias        = $rebaja_dias ? false : true;
            $days               = (int)$request->total_dias;
            $monto_ajuste       = 0;
            $fecha_inicio       = Carbon::parse($request->fecha_inicio);
            $fecha_termino      = Carbon::parse($request->fecha_termino);
            $diff_days          = $fecha_inicio->diffInDays($fecha_termino) + 1;
            $calculo_dias       = $request->calculo_dias === 'true' ? true : false;

            if (!$modificar_dias) {
                $days       = $diff_days;
                $fds        = 0;
                $inicio     = Carbon::parse($request->fecha_inicio)->format('Y-m-d');
                $termino    = Carbon::parse($request->fecha_termino)->format('Y-m-d');
                if (!$calculo_dias) {
                    for ($i = $inicio; $i <= $termino; $i++) {
                        $i_format       = Carbon::parse($i)->isWeekend();
                        if ($i_format) {
                            $fds++;
                        }
                    }
                    $feriados_count  = $esquema->recarga->feriados()->where('active', true)->whereBetween('fecha', [$inicio, $termino])->count();
                    $days            = $days - $fds - $feriados_count;
                }
            }
            $days_form          = $days;
            $days_form          = $rebaja_dias ? $days_form * -1 : $days_form;

            $total_dias_ajustes_pendiente       = $esquema->reajustes()->where('tipo_reajuste', 0)->where('last_status', 0)->sum('total_dias');
            $total_dias_ajustes_calculo         = $total_dias_ajustes_pendiente + $days_form;
            $total_dias_cancelar_calculo        = $esquema->total_dias_cancelar + $total_dias_ajustes_calculo;
            $monto_cancelar                     = $total_dias_cancelar_calculo * $valor_dia;
            $monto_cancelar                     = "$" . number_format($monto_cancelar, 0, ",", ".");
            $monto_ajuste                       = $valor_dia * $total_dias_ajustes_calculo;

            $new_esquema = (object) [
                'total_dias_ajustes_pendiente'          => (int)$total_dias_ajustes_pendiente,
                'dias_reajuste'                         => $days_form,
                'total_dias_ajustes_calculo'            => $total_dias_ajustes_calculo,
                'monto_ajuste_format'                   => "$" . number_format($monto_ajuste, 0, ",", "."),
                'dias_cancelar'                         => $total_dias_cancelar_calculo,
                'monto_cancelar'                        => $monto_cancelar
            ];

            $totales = (object) [
                'diff_days'             => $diff_days,
                'total_dias'            => $days,
                'monto_ajuste'          => $monto_ajuste,
                'monto_ajuste_format'   => number_format($monto_ajuste, 0, ",", "."),
                'new_esquema'           => $new_esquema
            ];

            return response()->json(
                array(
                    'status'        => 'Success',
                    'title'         => null,
                    'message'       => null,
                    'totales'       => $totales,
                )
            );
        } catch (\Exception $error) {
            return response()->json($error->getMessage());
        }
    }

    public function getMontoInDays(Request $request)
    {
        $modificar_dias     = (int)$request->modificar_dias;
        $modificar_dias     = $modificar_dias ? true : false;
        $esquema            = Esquema::find($request->esquema_id);
        $valor_dia          = (int)$request->valor_dia;
        $rebaja_dias        = (int)$request->rebaja_dias;
        $rebaja_dias        = $rebaja_dias ? false : true;
        $days               = (int)$request->total_dias;
        $monto_ajuste       = 0;
        $fecha_inicio       = Carbon::parse($request->fecha_inicio);
        $fecha_termino      = Carbon::parse($request->fecha_termino);
        $diff_days          = $fecha_inicio->diffInDays($fecha_termino) + 1;
        $calculo_dias       = $request->calculo_dias === 'true' ? true : false;



        if (!$modificar_dias) {
            $days       = $diff_days;
            $fds        = 0;
            $inicio     = Carbon::parse($request->fecha_inicio)->format('Y-m-d');
            $termino    = Carbon::parse($request->fecha_termino)->format('Y-m-d');
            if (!$calculo_dias) {
                for ($i = $inicio; $i <= $termino; $i++) {
                    $i_format       = Carbon::parse($i)->isWeekend();
                    if ($i_format) {
                        $fds++;
                    }
                }
                $feriados_count  = $esquema->recarga->feriados()->where('active', true)->whereBetween('fecha', [$inicio, $termino])->count();
                $days            = $days - $fds - $feriados_count;
            }
        }
        $monto_ajuste = $valor_dia * $days;
        $monto_ajuste = $rebaja_dias ? $monto_ajuste * -1 : $monto_ajuste;
        $monto_ajuste_format        = "$" . number_format($monto_ajuste, 0, ",", ".");

        $total_monto_ajustes_pendiente                      = $esquema->reajustes()->where('tipo_reajuste', 1)->where('last_status', 0)->sum('monto_ajuste');
        $total_monto_ajustes_pendiente_format               = "$" . number_format($total_monto_ajustes_pendiente, 0, ",", ".");

        $total_monto_ajustes_pendiente_nuevo_ajuste         = $total_monto_ajustes_pendiente + $monto_ajuste;
        $total_monto_ajustes_pendiente_nuevo_ajuste_format  = "$" . number_format($total_monto_ajustes_pendiente_nuevo_ajuste, 0, ",", ".");

        $total_monto_cancelar                               = $total_monto_ajustes_pendiente_nuevo_ajuste + $esquema->monto_total_cancelar;
        $total_monto_cancelar_format                        = "$" . number_format($total_monto_cancelar, 0, ",", ".");

        $new_esquema = (object) [
            'total_monto_ajustes_pendiente'          => $total_monto_ajustes_pendiente_format,
            'monto_ajuste_format'                    => $monto_ajuste_format,
            'total_monto_calculo'                    => $total_monto_ajustes_pendiente_nuevo_ajuste_format,
            'total_monto_cancelar'                   => $total_monto_cancelar_format
        ];

        $totales = (object) [
            'diff_days'             => $diff_days,
            'total_dias'            => $days,
            'monto_ajuste'          => $monto_ajuste,
            'monto_ajuste_format'   => number_format($monto_ajuste, 0, ",", "."),
            'new_esquema'           => $new_esquema
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

                $feriados_adicionales = $this->feriadosEspeciales($recarga, $fechas);

                $new_feriados = array_map(function ($feriado) {
                    return is_object($feriado) ? (array) $feriado : $feriado;
                }, $new_feriados);

                $feriados_adicionales = array_map(function ($feriado) {
                    return is_object($feriado) ? (array) $feriado : $feriado;
                }, $feriados_adicionales);

                $feriados_ok = array_merge($new_feriados, $feriados_adicionales);
            }
            return FeriadosResource::collection($feriados_ok);
        } catch (\Exception $error) {
            Log::info($error->getMessage());
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

    private function feriadosEspeciales($recarga, $fechas)
    {
        try {
            $feriados_recarga_query = $recarga->feriados()->get()->pluck('fecha')->toArray();
            $feriados_sistema = Feriado::where(function ($q) use ($recarga) {
                $q->where(function ($q) use ($recarga) {
                    $q->where('anio', $recarga->anio_beneficio)
                        ->where('mes', $recarga->mes_beneficio);
                })->orWhere(function ($q) use ($recarga) {
                    $q->where('anio', $recarga->anio_calculo)
                        ->where('mes', $recarga->mes_calculo);
                });
            })
                ->where('tipo', 'Especial');

            if (count($fechas) > 0) {
                $collectionSinNull = $fechas->filter(function ($value) {
                    return !is_null($value);
                });

                $collectionSinNull = $collectionSinNull->values();
                $feriados_sistema = $feriados_sistema->whereNotIn('fecha', $collectionSinNull);
            }

            if ($feriados_recarga_query) {
                $feriados_sistema = $feriados_sistema->whereNotIn('fecha', $feriados_recarga_query);
            }

            $feriados_sistema = $feriados_sistema->get();

            $feriados_adicionales = $feriados_sistema->map(function ($feriado) {
                return (object) [
                    'nombre'        => $feriado->nombre, // Notación de flecha
                    'comentarios'   => null,
                    'fecha'         => $feriado->fecha,
                    'irrenunciable' => "0",
                    'tipo'          => 'Especial',
                ];
            })->toArray();

            return $feriados_adicionales;
        } catch (\Error $error) {
            Log::info($error->getMessage());
        }
    }
}
