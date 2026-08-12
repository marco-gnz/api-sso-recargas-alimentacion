<?php

namespace App\Http\Controllers\ApiPublic;

use App\Http\Controllers\Controller;
use App\Models\Esquema;
use App\Models\Recarga;
use App\Models\Viatico;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class GecomController extends Controller
{
    public function returnViatico(Request $request)
    {
        try {
            $validator = Validator::make($request->input('filter', []), [
                'n_resolucion'      => ['required', 'string', 'max:100'],
                'n_resolucion_sirh' => ['nullable', 'string', 'max:100'],
                'rut_completo'      => ['required', 'string', 'max:20'],
                'fecha_inicio'      => ['required', 'date_format:Y-m-d'],
                'fecha_termino'     => ['required', 'date_format:Y-m-d'],
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'errors' => collect($validator->errors()->toArray())
                        ->flatMap(function ($messages, $field) {
                            return collect($messages)->map(function ($message) use ($field) {
                                return [
                                    'status' => '422',
                                    'code'   => 'VALIDATION_ERROR',
                                    'title'  => 'Parámetro inválido',
                                    'detail' => $message,
                                    'source' => [
                                        'parameter' => "filter[{$field}]",
                                    ],
                                ];
                            });
                        })
                        ->values()
                        ->all(),
                ], 422, [
                    'Content-Type' => 'application/vnd.api+json',
                ]);
            }

            $filters = $validator->validated();

            $fechaInicio  = Carbon::createFromFormat('Y-m-d', $filters['fecha_inicio']);
            $fechaTermino = Carbon::createFromFormat('Y-m-d', $filters['fecha_termino']);

            $anioCalculo = $fechaInicio->year;
            $mesCalculo  = $fechaInicio->month;

            $esquema = Esquema::query()
                ->with([
                    'funcionario',
                    'recarga.establecimiento',
                ])
                ->whereHas('funcionario', function ($query) use ($filters) {
                    $query->where('rut_completo', $filters['rut_completo']);
                })
                ->whereHas('recarga', function ($query) use ($anioCalculo, $mesCalculo) {
                    $query->where('active', true)
                        ->where('anio_calculo', $anioCalculo)
                        ->where('mes_calculo', $mesCalculo);
                })
                ->first();

            if (!$esquema) {
                return response()->json([
                    'errors' => [
                        [
                            'status' => '404',
                            'code'   => 'ESQUEMA_NOT_FOUND',
                            'title'  => 'Esquema no encontrado',
                            'detail' => 'No se encontró un esquema de recarga para los filtros enviados.',
                        ],
                    ],
                ], 404, [
                    'Content-Type' => 'application/vnd.api+json',
                ]);
            }

            $viatico = $esquema->viaticos()
                ->where(function ($query) use ($filters, $fechaInicio, $fechaTermino) {
                    $query->where(function ($query) use ($filters) {
                        $query->where('n_resolucion', $filters['n_resolucion']);

                        if (!empty($filters['n_resolucion_sirh'])) {
                            $query->orWhere(
                                'n_resolucion',
                                $filters['n_resolucion_sirh']
                            );
                        }
                    })->orWhere(function ($query) use ($fechaInicio, $fechaTermino) {
                        $query->whereDate(
                            'fecha_inicio_periodo',
                            $fechaInicio->toDateString()
                        )->whereDate(
                            'fecha_termino_periodo',
                            $fechaTermino->toDateString()
                        );
                    });
                })
                ->first();

            $recarga = $esquema->recarga;
            $timezone = 'America/Santiago';

            $mesBeneficio = Carbon::createFromDate(
                $recarga->anio_beneficio,
                $recarga->mes_beneficio,
                1,
                $timezone
            )->locale('es')->monthName;

            $mesCalculoNombre = Carbon::createFromDate(
                $recarga->anio_calculo,
                $recarga->mes_calculo,
                1,
                $timezone
            )->locale('es')->monthName;

            $attributes = [
                'rut_completo' => $esquema->funcionario->rut_completo,

                'nombre_completo' => $esquema->funcionario->nombre_completo,

                'recarga_codigo' => $recarga->codigo,

                'periodo_beneficio' => sprintf(
                    '%s / %s',
                    $recarga->anio_beneficio,
                    Str::ucfirst($mesBeneficio)
                ),

                'periodo_calculo' => sprintf(
                    '%s / %s',
                    $recarga->anio_calculo,
                    Str::ucfirst($mesCalculoNombre)
                ),

                'status_recarga' => Recarga::NOM_STATUS[$recarga->last_status] ?? null,

                'establecimiento' => optional($recarga->establecimiento)->sigla,

                'viatico' => $viatico
                    ? [
                        'valor' => "$".number_format($viatico->valor_viatico, 0, ",", "."),

                        'moneda' => 'CLP',

                        'fecha_inicio_periodo' => Carbon::parse(
                            $viatico->fecha_inicio_periodo
                        )->format('d-m-Y'),

                        'fecha_termino_periodo' => Carbon::parse(
                            $viatico->fecha_termino_periodo
                        )->format('d-m-Y'),

                        'n_resolucion' => $viatico->n_resolucion,
                    ]
                    : null,
            ];

            return response()->json([
                'jsonapi' => [
                    'version' => '1.1',
                ],

                'links' => [
                    'self' => $request->fullUrl(),
                ],

                'data' => [
                    'type'       => 'esquemas-viatico',
                    'id'         => (string) $esquema->getKey(),
                    'attributes' => $attributes,
                ],
            ], 200, [
                'Content-Type' => 'application/vnd.api+json',
            ]);
        } catch (\Throwable $error) {
            Log::error('Error al obtener viático desde la API.', [
                'message' => $error->getMessage(),
                'file'    => $error->getFile(),
                'line'    => $error->getLine(),
                'filters' => $request->input('filter', []),
            ]);

            return response()->json([
                'errors' => [
                    [
                        'status' => '500',
                        'code'   => 'INTERNAL_SERVER_ERROR',
                        'title'  => 'Error interno',
                        'detail' => 'Ocurrió un error al obtener la información del viático.',
                    ],
                ],
            ], 500, [
                'Content-Type' => 'application/vnd.api+json',
            ]);
        }
    }
}
