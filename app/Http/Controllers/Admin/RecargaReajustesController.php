<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Reajustes\StoreReajusteRequest;
use App\Http\Requests\Admin\Reajustes\ValidarReajusteRequest;
use App\Http\Resources\FuncionarioReajustesAllResource;
use App\Http\Resources\FuncionarioReajustesResource;
use App\Http\Resources\FuncionariosResumenResource;
use App\Http\Resources\RecargaReajustesResource;
use App\Http\Resources\RecargaResumenResource;
use App\Http\Resources\TablaResumenResource;
use App\Models\Reajuste;
use App\Models\ReajusteEstado;
use App\Models\Recarga;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;

class RecargaReajustesController extends Controller
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

    protected function errorResponse($message = null, $code)
    {
        return response()->json([
            'status'    => 'Error',
            'message'   => $message,
            'data'      => null,
            'code'      => $code
        ], $code);
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

    public function returnFindReajuste($uuid)
    {
        $reajuste = Reajuste::where('uuid', $uuid)->firstOrFail();

        return $this->successResponse(FuncionarioReajustesAllResource::make($reajuste), null, null, 200);
    }

    public function returnReajustesRecarga($codigo, Request $request)
    {
        try {
            $estados              = $request->estados;
            $recarga              = Recarga::where('codigo', $codigo)->firstOrFail();
            $reajustes            = $recarga->reajustes()->input($request->input)->tipos($request->tipos)->estados($request->estados)->get();
            /* return $this->successResponse(RecargaReajustesResource::collection($reajustes)); */
            return RecargaReajustesResource::collection($reajustes)->additional(
                [
                    'estados'       => ReajusteEstado::STATUS_IDS,
                    'tipo_ajustes'  => Reajuste::TYPE_IDS
                ]
            );
        } catch (\Exception $error) {
            return response()->json($error->getMessage());
        }
    }

    public function withFnAusentismos($recarga)
    {
        $function = ['ausentismos' => function ($query) use ($recarga) {
            $query->where('recarga_id', $recarga->id)->get();
        }];
        return $function;
    }

    public function withFnContratos($recarga)
    {
        $function = ['contratos' => function ($query) use ($recarga) {
            $query->where('recarga_id', $recarga->id)->get();
        }];
        return $function;
    }

    public function withFnAsistencias($recarga)
    {
        $function = ['asistencias' => function ($query) use ($recarga) {
            $query->where('recarga_id', $recarga->id)->get();
        }];
        return $function;
    }

    public function withFnAjustes($recarga)
    {
        $function = ['reajustes' => function ($query) use ($recarga) {
            $query->where('recarga_id', $recarga->id)->get();
        }];
        return $function;
    }

    public function withFnTurnos($recarga)
    {
        $function = ['turnos' => function ($query) use ($recarga) {
            $query->where('recarga_id', $recarga->id)->get();
        }];
        return $function;
    }

    public function withFnViaticos($recarga)
    {
        $function = ['viaticos' => function ($query) use ($recarga) {
            $query->where('recarga_id', $recarga->id)->get();
        }];
        return $function;
    }

    public function withFnRecargas($recarga)
    {
        $function = ['recargas' => function ($query) use ($recarga) {
            $query->where('recarga_user.recarga_id', $recarga->id);
        }];
        return $function;
    }

    public function storeReajusteFuncionario(StoreReajusteRequest $request)
    {
        try {
            $with           = $this->withRecarga();
            $recarga        = Recarga::with($with)->where('codigo', $request->recarga_codigo)->withCount('users')->withCount('reajustes')->withCount('contratos')->withCount('viaticos')->first();

            $withFnAusentismos      = $this->withFnAusentismos($recarga);
            $withFnContratos        = $this->withFnContratos($recarga);
            $withFnAsistencias      = $this->withFnAsistencias($recarga);
            $withFnAjustes          = $this->withFnAjustes($recarga);
            $withFnTurnos           = $this->withFnTurnos($recarga);
            $withFnViaticos         = $this->withFnViaticos($recarga);
            $withFnRecargas         = $this->withFnRecargas($recarga);

            $funcionario    = User::with($withFnAusentismos)
            ->with($withFnContratos)
            ->with($withFnAsistencias)
            ->with($withFnAjustes)
            ->with($withFnTurnos)
            ->with($withFnViaticos)
            ->with($withFnRecargas)
            ->where('id', $request->user_id)
            ->firstOrFail();
            $form           = ['recarga_codigo', 'user_id', 'fecha_inicio', 'fecha_termino', 'incremento', 'tipo_ausentismo_id', 'tipo_incremento_id', 'dias', 'tipo_reajuste', 'valor_dia', 'monto_ajuste', 'observacion'];

            $reajuste       = Reajuste::create($request->only($form));

            if ($reajuste) {
                $estatus = ReajusteEstado::create([
                    'status'        => ReajusteEstado::STATUS_PENDIENTE,
                    'reajuste_id'   => $reajuste->id
                ]);

                $reajuste->recarga_id       = $recarga->id;
                $reajuste->save();

                $funcionario = $funcionario->fresh();

                return response()->json(
                    array(
                        'status'    => 'Success',
                        'title'     => 'Reajuste ingresado con éxito.',
                        'message'   => null,
                        'user'      => TablaResumenResource::make($funcionario),
                        'recarga'   => RecargaResumenResource::make($recarga)
                    )
                );

               /*  return $this->successResponse(array(TablaResumenResource::make($funcionario), RecargaResumenResource::make($recarga)), 'Reajuste ingresado con éxito.', null, 200); */
            }
        } catch (\Exception $error) {
            return response()->json($error->getMessage());
        }
    }

    public function validateReajuste($uuid, ValidarReajusteRequest $request)
    {
        $reajuste = Reajuste::where('uuid', $uuid)->firstOrFail();

        if ($reajuste) {
            $last_status = $reajuste->estados()->orderBy('created_at', 'desc')->first();

            if ($request->aprobar === true) {
                $estatus = ReajusteEstado::create([
                    'reajuste_id'   => $reajuste->id,
                    'status'        => ReajusteEstado::STATUS_APROBADO
                ]);
            } else {
                $estatus = ReajusteEstado::create([
                    'reajuste_id'   => $reajuste->id,
                    'status'        => ReajusteEstado::STATUS_RECHAZADO,
                    'observacion'   => $request->observacion
                ]);
            }
            $reajuste   = $reajuste->fresh();
            $status     = ReajusteEstado::STATUS_NOM[$estatus->status];

            return $this->successResponse(FuncionarioReajustesResource::make($reajuste), "Reajuste {$status} con éxito.");
        }
    }

    public function storeReajuste(StoreReajusteRequest $request)
    {
        try {
            $form = ['recarga_codigo', 'user_id', 'fecha_inicio', 'fecha_termino', 'incremento', 'tipo_ausentismo_id', 'tipo_incremento_id', 'dias', 'tipo_reajuste', 'valor_dia', 'monto_ajuste', 'observacion'];

            $recarga  = Recarga::where('codigo', $request->recarga_codigo)->firstOrFail();

            $validateExistReajuste = $this->validateExistReajuste($recarga->id, $request->user_id, $request->fecha_inicio, $request->fecha_termino);
            $validateDaysInDate    = $this->validateDaysInDate($request->incremento, $request->fecha_inicio, $request->fecha_termino, $request->dias);

            if ($validateExistReajuste) {
                return response()->json([
                    'errors' => ['data' => ['No es posible ingresar reajuste, existe un registro en el rango de fechas.']]
                ], 422);
            } else if ($validateDaysInDate) {
                return response()->json([
                    'errors' => ['dias' => ['Días no coinciden con la fecha de inicio/término ingresado.']]
                ], 422);
            } else {
                $reajuste = Reajuste::create($request->only($form));

                if ($reajuste) {
                    $estatus = ReajusteEstado::create([
                        'status'        => ReajusteEstado::STATUS_PENDIENTE,
                        'reajuste_id'   => $reajuste->id
                    ]);

                    $reajuste->recarga_id       = $recarga->id;
                    $reajuste->save();

                    return $this->successResponse(FuncionarioReajustesResource::make($reajuste), "Reajuste ingresado con éxito.");
                }
            }
        } catch (\Exception $error) {
            return response()->json($error->getMessage());
        }
    }

    private function validateExistReajuste($recarga_id, $user_id, $fecha_inicio, $fecha_termino)
    {
        $existe                 = false;
        $newformat_fecha_ini    = Carbon::parse($fecha_inicio)->format('Y-m-d');
        $newformat_fecha_fin    = Carbon::parse($fecha_termino)->format('Y-m-d');

        $validacion_1 = Reajuste::where('recarga_id', $recarga_id)
            ->where('user_id', $user_id)
            ->where('fecha_inicio', '<=', $newformat_fecha_ini)
            ->where('fecha_termino', '>=', $newformat_fecha_ini)
            ->where(function ($query) {
                $query->whereHas('recarga', function ($query) {
                    $query->where('active', true);
                });
            })
            ->has('latestStatus')->with('latestStatus')->get()->filter(function (Reajuste $reajuste) {
                return $reajuste->latestStatus->status != 2;
            })
            ->count();

        if ($validacion_1 > 0) {
            $existe = true;
        }

        $validacion_2 = Reajuste::where('recarga_id', $recarga_id)
            ->where('user_id', $user_id)
            ->where('fecha_inicio', '<=', $newformat_fecha_fin)
            ->where('fecha_termino', '>=', $newformat_fecha_fin)
            ->where(function ($query) {
                $query->whereHas('recarga', function ($query) {
                    $query->where('active', true);
                });
            })
            ->has('latestStatus')->with('latestStatus')->get()->filter(function (Reajuste $reajuste) {
                return $reajuste->latestStatus->status != 2;
            })
            ->count();

        if ($validacion_2 > 0) {
            $existe = true;
        }

        $validacion_3 = Reajuste::where('recarga_id', $recarga_id)
            ->where('user_id', $user_id)
            ->where('fecha_inicio', '>=', $newformat_fecha_ini)
            ->where('fecha_termino', '<=', $newformat_fecha_fin)
            ->where(function ($query) {
                $query->whereHas('recarga', function ($query) {
                    $query->where('active', true);
                });
            })
            ->has('latestStatus')->with('latestStatus')->get()->filter(function (Reajuste $reajuste) {
                return $reajuste->latestStatus->status != 2;
            })
            ->count();

        if ($validacion_3 > 0) {
            $existe = true;
        }

        return $existe;
    }

    private function validateDaysInDate($incremento, $fecha_inicio, $fecha_termino, $dias_ingresados)
    {
        $error                  = false;
        $newformat_fecha_ini    = Carbon::parse($fecha_inicio);
        $newformat_fecha_fin    = Carbon::parse($fecha_termino);
        $diff_days              = $newformat_fecha_ini->diffInDays($newformat_fecha_fin) + 1;

        if (!$incremento) {
            $dias_ingresados = abs($dias_ingresados);
        }

        if ($dias_ingresados > $diff_days) {
            $error = true;
        }
        return $error;
    }
}
