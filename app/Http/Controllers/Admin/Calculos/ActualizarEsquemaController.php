<?php

namespace App\Http\Controllers\Admin\Calculos;

use App\Http\Controllers\Controller;
use App\Models\Esquema;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ActualizarEsquemaController extends Controller
{
    public function storeEsquema($funcionario, $recarga, $contrato)
    {
        try {
            $existe_esquema = $this->funcionarioTieneEsquema($funcionario, $recarga);
            $feriados_count = 0;

            if ($existe_esquema) {
                $esquema        = $this->funcionarioEsquema($funcionario, $recarga);

                $contratos      = $funcionario->contratos()->where('recarga_id', $recarga->id)->get();
                $alejamiento    = $contratos->where('alejamiento', true)->first();

                $total_dias_contrato_periodo            = $contratos->sum('total_dias_contrato_periodo');
                $total_dias_habiles_contrato_periodo    = $contratos->sum('total_dias_habiles_contrato_periodo');

                $total_contrato = $this->totalContrato($esquema, $total_dias_contrato_periodo, $total_dias_habiles_contrato_periodo, $feriados_count);
                $data = [
                    'total_dias_contrato'           => $total_dias_contrato_periodo,
                    'total_dias_habiles_contrato'   => $total_dias_habiles_contrato_periodo,
                    'calculo_contrato'              => $total_contrato,
                    'fecha_alejamiento'             => $alejamiento ? true : false,
                    'contrato_n_registros'          => count($contratos),
                ];
                $update = $esquema->update($data);
            }
        } catch (\Exception $error) {
            return $error->getMessage();
        }
    }

    public function updateEsquemaAsignaciones($funcionario, $recarga)
    {
        try {
            $esquema = $this->funcionarioEsquema($funcionario, $recarga);

            if ($esquema) {
                $turno_asignacion   = $funcionario->turnos()->where('recarga_id', $recarga->id)->where('es_turnante', true)->count();

                $update = $esquema->update([
                    'turno_asignacion' => $turno_asignacion > 0 ? true : false,
                ]);
            }
        } catch (\Exception $error) {
            return $error->getMessage();
        }
    }

    public function updateEsquemaTurnos($funcionario, $recarga, $totales)
    {
        try {
            $esquema = Esquema::where('user_id', $funcionario->id)->where('recarga_id', $recarga->id)->first();
            if ($esquema) {
                $update = $esquema->update([
                    'total_dias_turno_largo'                                => $totales->turno_largo,
                    'total_dias_turno_nocturno'                             => $totales->turno_nocturno,
                    'total_dias_libres'                                     => $totales->dias_libres,
                    'total_dias_feriados_turno'                             => $totales->total_dias_feriados_turno,
                    'total_dias_turno_largo_en_periodo_contrato'            => $totales->turno_largo_en_contrato,
                    'total_dias_turno_nocturno_en_periodo_contrato'         => $totales->turno_nocturno_en_contrato,
                    'total_dias_libres_en_periodo_contrato'                 => $totales->dias_libres_en_contrato,
                    'total_dias_feriados_turno_en_periodo_contrato'         => $totales->total_dias_feriados_turno_en_periodo_contrato,
                    'calculo_turno'                                         => $totales->calculo_turno,
                    'total_turno'                                           => $totales->total_turno,
                ]);
            }
        } catch (\Exception $error) {
            return $error->getMessage();
        }
    }

    public function updateAusentismosGrupoUno($funcionario, $recarga, $id_grupo)
    {
        try {
            $esquema = Esquema::where('user_id', $funcionario->id)->where('recarga_id', $recarga->id)->first();
            if ($esquema) {
                $total_dias_grupo_uno           = 0;
                $total_dias_habiles_grupo_uno   = 0;

                $ausentismos_not_tipo_dias = $funcionario->ausentismos()
                    ->where('recarga_id', $recarga->id)
                    ->where('grupo_id', $id_grupo)
                    ->where('tiene_descuento', true)
                    ->whereHas('regla', function ($query) {
                        $query->whereNull('tipo_dias');
                    });

                $ausentismos_naturales = $funcionario->ausentismos()
                    ->where('recarga_id', $recarga->id)
                    ->where('grupo_id', $id_grupo)
                    ->where('tiene_descuento', true)
                    ->whereHas('regla', function ($query) {
                        $query->where('active_tipo_dias', true)
                            ->where('tipo_dias', false);
                    })
                    ->sum('total_dias_ausentismo_periodo');

                $ausentismos_habiles = $funcionario->ausentismos()
                    ->where('recarga_id', $recarga->id)
                    ->where('grupo_id', $id_grupo)
                    ->where('tiene_descuento', true)
                    ->whereHas('regla', function ($query) {
                        $query->where('active_tipo_dias', true)
                            ->where('tipo_dias', true);
                    })
                    ->sum('total_dias_habiles_ausentismo_periodo');

                $total_tipo_dias                = $ausentismos_naturales + $ausentismos_habiles;
                $total_dias_grupo_uno           = $total_tipo_dias + $ausentismos_not_tipo_dias->sum('total_dias_ausentismo_periodo');
                $total_dias_habiles_grupo_uno   = $total_tipo_dias + $ausentismos_not_tipo_dias->sum('total_dias_habiles_ausentismo_periodo');

                $data = [
                    'total_dias_grupo_uno'           => $total_dias_grupo_uno,
                    'total_dias_habiles_grupo_uno'   => $total_dias_habiles_grupo_uno,
                    /* 'total_dias_feriados_grupo_uno'  => $this->contarFeriadosEnAusentismos($funcionario, $recarga, 1), */
                    'grupo_uno_n_registros'          => $funcionario->ausentismos()
                        ->where('recarga_id', $recarga->id)
                        ->where('grupo_id', $id_grupo)
                        ->where('tiene_descuento', true)->count()
                ];


                $update  = $esquema->update($data);
                $esquema = $esquema->fresh();

                $total_grupo = $this->totalDiasAusentismoGrupo($esquema, 1);
                $esquema->update([
                    'calculo_grupo_uno'              => $total_grupo
                ]);
            }
        } catch (\Exception $error) {
            Log::info($error->getMessage());
            return $error->getMessage();
        }
    }

    public function updateAusentismosGrupoDos($funcionario, $recarga, $id_grupo)
    {
        try {
            $esquema = $this->funcionarioEsquema($funcionario, $recarga);
            if ($esquema) {
                $total_dias_grupo_dos           = 0;
                $total_dias_habiles_grupo_dos   = 0;
                $ausentismos = $funcionario->ausentismos()
                    ->where('recarga_id', $recarga->id)
                    ->where('grupo_id', $id_grupo)
                    ->where('tiene_descuento', true)
                    ->get();

                $es_turnante = $esquema ? ($esquema->es_turnante != 2 ? true : false) : false;

                foreach ($ausentismos as $ausentismo) {
                    if ($ausentismo->regla) {
                        $total_dias_grupo_dos           += $ausentismo->total_dias_ausentismo_periodo;
                        $total_dias_habiles_grupo_dos   += $ausentismo->total_dias_habiles_ausentismo_periodo;
                    }
                }
                $data = [
                    'total_dias_grupo_dos'           => $total_dias_grupo_dos,
                    'total_dias_habiles_grupo_dos'   => $total_dias_habiles_grupo_dos,
                    /* 'total_dias_feriados_grupo_dos'  => $this->contarFeriadosEnAusentismos($funcionario, $recarga, 2), */
                    'grupo_dos_n_registros'          => count($ausentismos)
                ];

                $update  = $esquema->update($data);
                $esquema = $esquema->fresh();

                $total_grupo = $this->totalDiasAusentismoGrupo($esquema, 2);

                $esquema->update([
                    'calculo_grupo_dos' => $total_grupo
                ]);
            }
        } catch (\Exception $error) {
            return $error->getMessage();
        }
    }

    public function updateAusentismosGrupoTres($funcionario, $recarga, $id_grupo)
    {
        try {
            $esquema = $this->funcionarioEsquema($funcionario, $recarga);
            if ($esquema) {
                $total_dias_grupo_tres           = 0;
                $total_dias_habiles_grupo_tres   = 0;
                $ausentismos = $funcionario->ausentismos()
                    ->where('recarga_id', $recarga->id)
                    ->where('grupo_id', $id_grupo)
                    ->where('tiene_descuento', true)
                    ->get();

                foreach ($ausentismos as $ausentismo) {
                    $total_dias_grupo_tres           += $ausentismo->total_dias_ausentismo_periodo;
                    $total_dias_habiles_grupo_tres   += $ausentismo->total_dias_habiles_ausentismo_periodo;
                    /* if ($ausentismo->regla) {
                        $hora_inicio    = Carbon::parse($ausentismo->hora_inicio);
                        $hora_termino   = Carbon::parse($ausentismo->hora_termino);
                        $fecha_inicio   = Carbon::parse($ausentismo->fecha_inicio_periodo);
                        $fecha_termino  = Carbon::parse($ausentismo->fecha_termino_periodo);


                        $hora_inicio_regla    = Carbon::parse($ausentismo->regla->hora_inicio);
                        $hora_termino_regla   = Carbon::parse($ausentismo->regla->hora_termino);

                        $concat_inicio        = "{$fecha_inicio->format('Y-m-d')} {$hora_inicio->format('H:i:s')}";
                        $concat_termino       = "{$fecha_termino->format('Y-m-d')} {$hora_termino->format('H:i:s')}";
                        $concat_inicio_regla  = "{$fecha_inicio->format('Y-m-d')} {$hora_inicio_regla->format('H:i:s')}";
                        $concat_termino_regla = "{$fecha_inicio->format('Y-m-d')} {$hora_termino_regla->format('H:i:s')}";

                        $hora_inicio_archivo   = Carbon::parse($concat_inicio)->timestamp;
                        $hora_termino_archivo  = Carbon::parse($concat_termino)->timestamp;
                        $fecha_inicio_regla    = Carbon::parse($concat_inicio_regla)->timestamp;
                        $fecha_termino_regla   = Carbon::parse($concat_termino_regla)->timestamp;

                        if (($hora_inicio_archivo < $fecha_inicio_regla && $hora_termino_archivo > $fecha_inicio_regla) || ($hora_inicio_archivo < $fecha_termino_regla && $hora_termino_archivo > $fecha_termino_regla) || ($hora_inicio_archivo >= $fecha_inicio_regla && $hora_termino_archivo <= $fecha_termino_regla)) {
                            $total_dias_grupo_tres           += $ausentismo->total_dias_ausentismo_periodo;
                            $total_dias_habiles_grupo_tres   += $ausentismo->total_dias_habiles_ausentismo_periodo;
                        }
                    } */
                }

                $data = [
                    'total_dias_grupo_tres'           => $total_dias_grupo_tres,
                    'total_dias_habiles_grupo_tres'   => $total_dias_habiles_grupo_tres,
                    /* 'total_dias_feriados_grupo_tres'  => $this->contarFeriadosEnAusentismos($funcionario, $recarga, 3), */
                    'grupo_tres_n_registros'          => count($ausentismos)

                ];
                $update  = $esquema->update($data);
                $esquema = $esquema->fresh();

                $total_grupo = $this->totalDiasAusentismoGrupo($esquema, 3);
                $esquema->update([
                    'calculo_grupo_tres' => $total_grupo
                ]);
            }
        } catch (\Exception $error) {
            return $error->getMessage();
        }
    }

    public function updateEsquemaViaticos($funcionario, $recarga)
    {
        try {
            $esquema = $this->funcionarioEsquema($funcionario, $recarga);
            if ($esquema) {
                $viaticos = $funcionario->viaticos()->where('recarga_id', $recarga->id)->get();

                $data = [
                    'total_dias_viaticos'           => $viaticos->where('valor_viatico', '>', 0)->sum('total_dias_periodo'),
                    'total_dias_habiles_viaticos'   => $viaticos->where('valor_viatico', '>', 0)->sum('total_dias_habiles_periodo'),
                    'viaticos_n_registros'          => count($viaticos),
                ];
                $update  = $esquema->update($data);
                $esquema = $esquema->fresh();

                $total_viaticos        = $this->totalDiasViaticos($esquema);
                $esquema->update([
                    'calculo_viaticos' => $total_viaticos
                ]);
            }
        } catch (\Exception $error) {
            return $error->getMessage();
        }
    }

    public function updateEsquemaAjustes($esquema)
    {
        try {
            if ($esquema) {
                $ajustes                = $esquema->reajustes()->get();

                $dias_periodo           = $ajustes->where('last_status', 1)->where('tipo_reajuste', 0)->sum('dias_periodo');
                $dias_periodo_habiles   = $ajustes->where('last_status', 1)->where('tipo_reajuste', 0)->sum('dias_periodo_habiles');
                $total_dias             = $ajustes->where('last_status', 1)->where('tipo_reajuste', 0)->sum('total_dias');
                $total_monto_ajuste     = $ajustes->where('last_status', 1)->where('tipo_reajuste', 1)->sum('monto_ajuste');
                $data = [
                    'total_dias_ajustes'        => $dias_periodo,
                    'dias_periodo_habiles'      => $dias_periodo_habiles,
                    'ajustes_dias_n_registros'  => $ajustes->where('tipo_reajuste', 0)->count(),
                    'calculo_dias_ajustes'      => $total_dias,

                    'total_monto_ajuste'        => $total_monto_ajuste,
                    'ajustes_monto_n_registros' => $ajustes->where('tipo_reajuste', 1)->count(),
                ];

                $update = $esquema->update($data);
            }
        } catch (\Exception $error) {
            return $error->getMessage();
        }
    }

    private function funcionarioTieneEsquema($funcionario, $recarga)
    {
        $existe = false;

        $total = Esquema::where('recarga_id', $recarga->id)->where('user_id', $funcionario->id)->count();

        if ($total > 0) {
            $existe = true;
        }

        return $existe;
    }

    private function totalContrato($esquema, $total_dias_contrato_periodo, $total_dias_habiles_contrato_periodo, $feriados_count)
    {
        $total_dias_contrato = $total_dias_habiles_contrato_periodo - $feriados_count;
        if ($esquema->es_turnante === 1) {
            $total_dias_contrato = $total_dias_contrato_periodo;
        }
        return $total_dias_contrato;
    }

    private function totalDiasAusentismoGrupo($esquema, $id_grupo)
    {
        $total_ausentismos  = 0;
        switch ($esquema->es_turnante) {
            case 1:
            case 3:
                switch ($id_grupo) {
                    case 1:
                        $total_ausentismos = $esquema->total_dias_grupo_uno;
                        break;
                    case 2:
                        $total_ausentismos = $esquema->total_dias_grupo_dos;
                        break;
                    case 3:
                        $total_ausentismos = $esquema->total_dias_grupo_tres;
                        break;
                }
                break;
            case 2:
                switch ($id_grupo) {
                    case 1:
                        $total              = $esquema->total_dias_habiles_grupo_uno - $esquema->total_dias_feriados_grupo_uno;
                        $total_ausentismos  = $total;
                        break;
                    case 2:
                        $total              = $esquema->total_dias_habiles_grupo_dos - $esquema->total_dias_feriados_grupo_dos;
                        $total_ausentismos  = $total;
                        break;
                    case 3:
                        $total              = $esquema->total_dias_habiles_grupo_tres - $esquema->total_dias_feriados_grupo_tres;
                        $total_ausentismos  = $total;
                        break;
                }
                break;
        }
        return $total_ausentismos;
    }

    private function totalDiasViaticos($esquema)
    {
        $total_viaticos  = 0;
        switch ($esquema->es_turnante) {
            case 1:
            case 3:
                $total_viaticos = $esquema->total_dias_viaticos;
                break;
            case 2:
                $total              = $esquema->total_dias_habiles_viaticos - $esquema->total_dias_feriados_viaticos;
                $total_viaticos     = $total;
                break;
        }
        return $total_viaticos;
    }

    private function funcionarioEsquema($funcionario, $recarga)
    {
        $esquema = Esquema::where('user_id', $funcionario->id)->where('recarga_id', $recarga->id)->first();

        return $esquema;
    }

    private function contarFeriadosEnContrato($recarga, $contrato)
    {
        $feriados_count = $recarga->feriados()->where('active', true)->whereBetween('fecha', [$contrato->fecha_inicio_periodo, $contrato->fecha_termino_periodo])->count();

        return $feriados_count;
    }

    private function contarFeriadosEnAusentismos($funcionario, $recarga, $id_grupo)
    {
        $feriados_count = 0;
        $ausentismos    = $funcionario->ausentismos()->where('recarga_id', $recarga->id)->where('grupo_id', $id_grupo)->where('tiene_descuento', true)->get();

        foreach ($ausentismos as $ausentismo) {
            $feriados_count += $recarga->feriados()->where('active', true)->whereBetween('fecha', [$ausentismo->fecha_inicio_periodo, $ausentismo->fecha_termino_periodo])->count();
        }

        return $feriados_count;
    }

    private function contarFeriadosEnViaticos($funcionario, $recarga)
    {
        $feriados_count = 0;
        $viaticos = $funcionario->viaticos()->where('valor_viatico', '>', 0)->where('recarga_id', $recarga->id)->get();

        foreach ($viaticos as $viatico) {
            $feriados_count += $recarga->feriados()->where('active', true)->whereBetween('fecha', [$viatico->fecha_inicio_periodo, $viatico->fecha_termino_periodo])->count();
        }

        return $feriados_count;
    }
}
