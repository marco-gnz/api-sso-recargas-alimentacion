<?php

namespace App\Http\Controllers\Admin\Reajustes;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Reajustes\StoreReajusteRequest;
use App\Http\Requests\Admin\Reajustes\ValidarReajusteRequest;
use App\Http\Resources\Esquema\EsquemaResource;
use App\Http\Resources\FuncionarioReajustesResource;
use App\Http\Resources\RecargaResumenResource;
use App\Http\Resources\TablaResumenResource;
use App\Models\Esquema;
use App\Models\Reajuste;
use App\Models\ReajusteEstado;
use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Http\Controllers\Admin\Calculos\ActualizarEsquemaController;
use App\Http\Resources\RecargaReajustesResource;
use App\Http\Resources\RecargaResource;
use App\Models\ReajusteAlerta;
use App\Models\Recarga;

class ReajustesController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth:sanctum']);
    }

    public function withFnReglas($load_grupo)
    {
        $load_grupo = (int)$load_grupo;
        $function   = ['reglas' => function ($query) use ($load_grupo) {
            $query->where('grupo_id', $load_grupo)->get();
        }];
        return $function;
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
            'contratos',
            'viaticos',
            'reajustes'
        ];

        return $with;
    }

    public function storeReajusteResumen(StoreReajusteRequest $request)
    {
        try {
            $form = [
                'esquema_id',
                'user_id',
                'fecha_inicio',
                'fecha_termino',
                'total_dias',
                'calculo_dias',
                'incremento',
                'tipo_ausentismo_id',
                'tipo_incremento_id',
                'tipo_reajuste',
                'valor_dia',
                'monto_ajuste',
                'observacion'
            ];

            $esquema = Esquema::where('id', $request->esquema_id)
                ->firstOrFail();

            if ($esquema) {
                $reajuste = Reajuste::create($request->only($form));

                if ($reajuste) {
                    $status = ReajusteEstado::create([
                        'status'        => ReajusteEstado::STATUS_PENDIENTE,
                        'reajuste_id'   => $reajuste->id
                    ]);
                    if ($request->advertencias) {
                        foreach ($request->advertencias as $advertencia) {
                            $reajusteAlerta = ReajusteAlerta::create([
                                'reajuste_id'   => $reajuste->id,
                                'tipo'          => 0,
                                'observacion'   => Esquema::ADVERTENCIA_NOM[$advertencia]
                            ]);
                        }
                    }
                    if ($request->errores) {
                        foreach ($request->errores as $error) {
                            $reajusteAlerta = ReajusteAlerta::create([
                                'reajuste_id'   => $reajuste->id,
                                'tipo'          => 1,
                                'observacion'   => Esquema::ERROR_NOM[$error]
                            ]);
                        }
                    }

                    $cartola_controller = new ActualizarEsquemaController;
                    $cartola_controller->updateEsquemaAjustes($esquema);

                    $esquema = $esquema->fresh()->loadCount('contratos')
                        ->loadCount('turnos')
                        ->loadCount('asistencias')
                        ->loadCount('ausentismos')
                        ->loadCount('viaticos')
                        ->loadCount('reajustes');

                    $with           = $this->withRecarga();
                    $withFnReglas   = $this->withFnReglas($request->load_grupo);
                    $recarga        = Recarga::with($with)->with($withFnReglas)->where('id', $esquema->recarga->id)->withCount('users')->withCount('ausentismos')->withCount('asignaciones')->withCount('reajustes')->withCount('contratos')->withCount('viaticos')->firstOrFail();

                    return response()->json(
                        array(
                            'status'    => 'Success',
                            'title'     => 'Reajuste ingresado con éxito.',
                            'message'   => null,
                            'recarga'   => RecargaResumenResource::make($recarga),
                            'esquema'   => TablaResumenResource::make($esquema),
                            'esquema_unique'    => EsquemaResource::make($esquema),
                            'ajuste'    => FuncionarioReajustesResource::make($reajuste)
                        )
                    );
                }
            }
        } catch (\Exception $error) {
            return response()->json($error->getMessage());
        }
    }

    public function validateReajuste($uuid, ValidarReajusteRequest $request)
    {
        try {
            $reajuste = Reajuste::where('uuid', $uuid)->firstOrFail();
            if ($reajuste) {
                $reajuste_update    = $this->updateReajusteAndUpdateEsquema($reajuste, $request);
                $status             = ReajusteEstado::STATUS_NOM[$reajuste_update->last_status];

                $esquema            = $reajuste_update->esquema;
                $cartola_controller = new ActualizarEsquemaController;
                $cartola_controller->updateEsquemaAjustes($esquema);

                $esquema = $esquema->fresh()->loadCount('contratos')
                    ->loadCount('turnos')
                    ->loadCount('asistencias')
                    ->loadCount('ausentismos')
                    ->loadCount('viaticos')
                    ->loadCount('reajustes');

                $reajuste = $reajuste->fresh();

                return response()->json(
                    array(
                        'status'    => 'Success',
                        'title'     => "Reajuste {$status} con éxito.",
                        'message'   => null,
                        'esquema'   => EsquemaResource::make($esquema),
                        'ajuste'    => FuncionarioReajustesResource::make($reajuste)
                    )
                );
            }
        } catch (\Exception $error) {
            return response()->json($error->getMessage());
        }
    }

    public function validateReajusteResumen($uuid, ValidarReajusteRequest $request)
    {
        try {
            $reajuste = Reajuste::where('uuid', $uuid)->firstOrFail();
            if ($reajuste) {
                $reajuste_update    = $this->updateReajusteAndUpdateEsquema($reajuste, $request);
                $status             = ReajusteEstado::STATUS_NOM[$reajuste_update->last_status];

                $esquema            = $reajuste_update->esquema;
                $recarga            = $reajuste_update->recarga;
                $cartola_controller = new ActualizarEsquemaController;
                $cartola_controller->updateEsquemaAjustes($esquema);

                $reajuste           = $reajuste->fresh();

                $recarga = $recarga->fresh()
                    ->loadCount('users')
                    ->loadCount('ausentismos')
                    ->loadCount('asignaciones')
                    ->loadCount('reajustes')
                    ->loadCount('contratos')
                    ->loadCount('viaticos');

                return response()->json(
                    array(
                        'status'    => 'Success',
                        'title'     => "Reajuste {$status} con éxito.",
                        'message'   => null,
                        'recarga'   => RecargaResource::make($recarga),
                        'ajuste'    => RecargaReajustesResource::make($reajuste)
                    )
                );
            }
        } catch (\Exception $error) {
            return response()->json($error->getMessage());
        }
    }

    private function updateReajusteAndUpdateEsquema($reajuste, $request)
    {
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
        return $reajuste;
    }
}
