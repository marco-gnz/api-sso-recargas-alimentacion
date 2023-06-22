<?php

namespace App\Http\Controllers\Funcionario;

use App\Http\Controllers\Controller;
use App\Http\Resources\FuncionarioRecargaResource;
use App\Models\Recarga;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Dompdf\Dompdf;
use stdClass;
use App\Http\Controllers\Admin\Calculos\PagoBeneficioController;
use App\Models\Esquema;

class PdfController extends Controller
{
    public function __construct(PagoBeneficioController $PagoBeneficioController)
    {
        $this->PagoBeneficioController = $PagoBeneficioController;
    }

    public function withFnAusentismos($recarga)
    {
        $function = ['ausentismos' => function ($query) use ($recarga) {
            $query->where('recarga_id', $recarga->id)->orderBy('fecha_inicio_periodo', 'asc')->get();
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
            $query->where('recarga_id', $recarga->id)->where('last_status', true)->get();
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
            $query->where('recarga_id', $recarga->id)->orderBy('fecha_inicio_periodo', 'asc')->get();
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

    private function esTurnante($funcionario)
    {
        $es_turnante = false;

        $total_turnos                   = $funcionario->turnos()->where(function ($q) {
            $q->where('asignacion_tercer_turno', '>', 0)
                ->orWhere('asignacion_cuarto_turno', '>', 0);
        })->count();
        $total_asistencias              = $funcionario->asistencias()->count();
        $total_dias_contrato_periodo    = $funcionario->contratos()->count();

        if (($total_turnos > 0 && $total_asistencias > 0 && $total_dias_contrato_periodo > 0) || ($total_asistencias > 0 && $total_dias_contrato_periodo > 0)) {
            $es_turnante = true;
        } else if ($total_turnos <= 0 && $total_asistencias > 0 && $total_dias_contrato_periodo > 0) {
            $es_turnante = null;
        } else if ($total_asistencias <= 0 && $total_turnos > 0 && $total_dias_contrato_periodo > 0) {
            $es_turnante = null;
        } else if ($total_dias_contrato_periodo <= 0 && $total_turnos > 0 && $total_asistencias > 0) {
            $es_turnante = null;
        }

        return $es_turnante;
    }

    private function totalDiasAsistencia($funcionario)
    {
        $total_l = 0;
        $total_n = 0;
        $total_x = 0;

        if (count($funcionario->asistencias)) {
            foreach ($funcionario->asistencias as $asistencia) {
                $fecha      = $asistencia->fecha;
                if (($asistencia->tipoAsistenciaTurno) && ($asistencia->tipoAsistenciaTurno->nombre === 'L')) {
                    $total_l += $funcionario->contratos()
                        ->where(function ($query) use ($fecha) {
                            $query->where('fecha_inicio_periodo', '<=', $fecha)
                                ->where('fecha_termino_periodo', '>=', $fecha);
                        })
                        ->count();
                } else if (($asistencia->tipoAsistenciaTurno) && ($asistencia->tipoAsistenciaTurno->nombre === 'N')) {
                    $total_n += $funcionario->contratos()
                        ->where(function ($query) use ($fecha) {
                            $query->where('fecha_inicio_periodo', '<=', $fecha)
                                ->where('fecha_termino_periodo', '>=', $fecha);
                        })
                        ->count();
                } else if (($asistencia->tipoAsistenciaTurno) && ($asistencia->tipoAsistenciaTurno->nombre === 'X')) {
                    $total_x += $funcionario->contratos()
                        ->where(function ($query) use ($fecha) {
                            $query->where('fecha_inicio_periodo', '<=', $fecha)
                                ->where('fecha_termino_periodo', '>=', $fecha);
                        })
                        ->count();
                }
            }
        }

        $data = (object) [
            'total_dx'      => $total_x,
            'total_dl'      => $total_l,
            'total_dn'      => $total_n,
            'total_turno'   => ($total_l + $total_n)
        ];

        return $data;
    }

    private function totalDiasCancelar($recarga, $es_turnante, $total_dias_contrato, $total_contrato_condition, $total_ausentismos, $dias_asistencia)
    {
        $total_dias = 0;

        if ($total_dias_contrato < $recarga->total_dias_mes_beneficio) {
            if ($es_turnante) {
                $total_dias = ($dias_asistencia->total_turno - $total_ausentismos->total_dias_ausentismos);
            } else {
                $total_dias = ($total_contrato_condition - $total_ausentismos->total_dias_ausentismos);
            }
        } else {
            if ($es_turnante) {
                $total_dias = ($dias_asistencia->total_turno - $total_ausentismos->total_dias_ausentismos);
            } else {
                $total_dias = ($total_contrato_condition - $total_ausentismos->total_dias_ausentismos);
            }
        }

        $total_monto = ($total_dias * $recarga->monto_dia);
        $total_monto = (int)$total_monto;

        $data = (object) [
            'total_dias_pagar'      => $total_dias,
            'total_monto'           => $total_monto,
            'total_monto_format'    => "$" . number_format($total_monto, 0, ",", ".")
        ];

        return $data;
    }

    private function totalDiasContratoDeCorrido($funcionario)
    {
        $total = 0;
        $total = $funcionario->contratos()->sum('total_dias_contrato_periodo');
        return $total;
    }

    private function totalDiasContratoHabiles($funcionario, $recarga)
    {
        $total          = 0;
        $contratos      = $funcionario->contratos()->get();
        $feriados_count = 0;

        if (count($contratos) > 0) {
            foreach ($contratos as $contrato) {
                $feriados_count  += $recarga->feriados()->where('active', true)->whereBetween('fecha', [$contrato->fecha_inicio_periodo, $contrato->fecha_termino_periodo])->count();
            }
        }

        $total = ($contratos->sum('total_dias_habiles_contrato_periodo') - $feriados_count);

        return $total;
    }

    private function totalDiasContratoCondition($es_turnante, $funcionario, $recarga)
    {
        $total = 0;
        if ($es_turnante) {
            $total = $this->totalDiasContratoDeCorrido($funcionario);
        } else {
            $total = $this->totalDiasContratoHabiles($funcionario, $recarga);
        }
        return $total;
    }

    private function ausentismosGrupo($funcionario, $id_grupo)
    {
        $total = $funcionario->ausentismos()->where('grupo_id', $id_grupo)->get();
        return $total;
    }

    public function totalGrupo($es_turnante, $funcionario, $id_grupo, $recarga)
    {
        $total          = 0;
        $feriados_count = 0;
        $ausentismos    = $this->ausentismosGrupo($funcionario, $id_grupo);

        if (count($ausentismos) > 0) {
            foreach ($ausentismos as $ausentismo) {
                $total_dias_habiles_ausentismo_periodo = 0;
                switch ($ausentismo->grupo_id) {
                    case 2:
                        $reglas = $ausentismo->regla->whereHas('meridianos', function ($query) use ($ausentismo) {
                            $query->where('meridiano_regla.meridiano_id', $ausentismo->meridiano_id)->where('meridiano_regla.active', true);
                        })->count();

                        if ($reglas > 0) {
                            if ($es_turnante) {
                                $total_dias_habiles_ausentismo_periodo = $ausentismo->total_dias_ausentismo_periodo;
                            } else {
                                $total_dias_habiles_ausentismo_periodo = $ausentismo->total_dias_habiles_ausentismo_periodo;
                            }
                        }
                        break;
                    case 3:
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
                            if ($es_turnante) {
                                $total_dias_habiles_ausentismo_periodo = $ausentismo->total_dias_ausentismo_periodo;
                            } else {
                                $total_dias_habiles_ausentismo_periodo = $ausentismo->total_dias_habiles_ausentismo_periodo;
                            }
                        }
                        break;
                    default:
                        $total_dias_habiles_ausentismo_periodo = $ausentismo->total_dias_habiles_ausentismo_periodo;
                        break;
                }
                if (!$es_turnante) {
                    $feriados_count += $recarga->feriados()->where('active', true)->whereBetween('fecha', [$ausentismo->fecha_inicio_periodo, $ausentismo->fecha_termino_periodo])->count();
                    $total          += $total_dias_habiles_ausentismo_periodo - $feriados_count;
                } else {
                    $total          += $total_dias_habiles_ausentismo_periodo;
                }
            }
        }

        $data = (object) [
            'n_registros'      => count($ausentismos),
            'total_dias'       => $total,
        ];
        return $data;
    }

    private function totalDiasViaticos($es_turnante, $funcionario, $recarga)
    {
        $n_registros    = 0;
        $total_dias     = 0;
        $viaticos       = $funcionario->viaticos()->get();
        $n_registros    = count($viaticos);
        if ($es_turnante) {
            $total_dias     = $viaticos->where('valor_viatico', '>', 0)->sum('total_dias_periodo');
        } else {
            $total_dias     = $viaticos->where('valor_viatico', '>', 0)->sum('total_dias_habiles_periodo');
        }
        $total_dias         = (int)$total_dias;
        $total_pago         = ($total_dias * $recarga->monto_dia);
        $total_pago         = (int)$total_pago;
        $data = (object) [
            'n_registros'               => $n_registros,
            'total_dias'                => $total_dias,
            'total_descuento'           => $total_pago,
            'total_descuento_format'    => "$" . number_format($total_pago, 0, ",", ".")
        ];
        return $data;
    }

    private function totalAjustesDias($funcionario, $recarga)
    {
        $ajustes        = $funcionario->reajustes()->where('tipo_reajuste', 0)->get();

        $n_registros    = count($ajustes);
        $total_dias     = $ajustes->where('last_status', 1)->sum('dias');

        $total_monto    = ($total_dias * $recarga->monto_dia);
        $total_monto    = (int)$total_monto;
        $data           = (object) [
            'n_registros'           => $n_registros,
            'total_dias'            => $total_dias,
            'total_monto'           => $total_monto,
            'total_monto_format'    => "$" . number_format($total_monto, 0, ",", ".")
        ];

        return $data;
    }

    private function totalAjustesMontos($funcionario)
    {
        $total_monto    = 0;
        $ajustes        = $funcionario->reajustes()->where('tipo_reajuste', 1)->get();

        $n_registros    = count($ajustes);
        $total_monto    = $ajustes->where('last_status', 1)->sum('monto_ajuste');

        $data = (object) [
            'n_registros'           => $n_registros,
            'total_monto'           => $total_monto,
            'total_monto_format'    => "$" . number_format($total_monto, 0, ",", ".")
        ];

        return $data;
    }

    private function funcionarioObject($funcionario, $recarga)
    {
        $es_turnante                                = $this->esTurnante($funcionario);
        $total_dias_contrato                        = $this->totalDiasContratoDeCorrido($funcionario);
        $total_contrato_condition                   = $this->totalDiasContratoCondition($es_turnante, $funcionario, $recarga);
        $contratos                                  = (object) ['total_dias_contrato' => (int)$total_dias_contrato, 'total_dias_habiles_contrato' => (int)$total_contrato_condition];
        $dias_asistencia                            = $this->totalDiasAsistencia($funcionario);

        $total_grupo_uno                            = $this->totalGrupo($es_turnante, $funcionario, 1, $recarga);
        $total_grupo_dos                            = $this->totalGrupo($es_turnante, $funcionario, 2, $recarga);
        $total_grupo_tres                           = $this->totalGrupo($es_turnante, $funcionario, 3, $recarga);

        $data_g_1 = (object) [
            'n_registros'      => $total_grupo_uno->n_registros,
            'total_dias'       => $total_grupo_uno->total_dias,
        ];
        $data_g_2 = (object) [
            'n_registros'      => $total_grupo_dos->n_registros,
            'total_dias'       => $total_grupo_dos->total_dias,
        ];
        $data_g_3 = (object) [
            'n_registros'      => $total_grupo_tres->n_registros,
            'total_dias'       => $total_grupo_tres->total_dias,
        ];

        $total_ausentismos                          = (object) [
            'total_dias_grupo_uno'                  => $data_g_1,
            'total_dias_grupo_dos'                  => $data_g_2,
            'total_dias_grupo_tres'                 => $data_g_3,
            'total_dias_ausentismos'                => ($data_g_1->total_dias + $data_g_2->total_dias + $data_g_3->total_dias)
        ];

        $total_pago_normal                          = $this->totalDiasCancelar($recarga, $es_turnante, $total_dias_contrato, $total_contrato_condition, $total_ausentismos, $dias_asistencia);
        $total_viaticos                             = $this->totalDiasViaticos($es_turnante, $funcionario, $recarga);
        $total_ajuste_de_dias                       = $this->totalAjustesDias($funcionario, $recarga);
        $total_ajustes_de_montos                    = $this->totalAjustesMontos($funcionario);

        $total_dias_cancelar                        = $total_pago_normal->total_dias_pagar - $total_viaticos->total_dias + $total_ajuste_de_dias->total_dias;
        $monto_total_cancelar_dias                  = $total_dias_cancelar * $recarga->monto_dia;

        $monto_total_cancelar                       = ($monto_total_cancelar_dias + $total_ajustes_de_montos->total_monto);
        $monto_total_cancelar_format                = "$" . number_format($monto_total_cancelar, 0, ",", ".");

        $total                                      = (object) [
            'total_dias_cancelar'                   => $total_dias_cancelar,
            'monto_total_cancelar'                  => $monto_total_cancelar,
            'monto_total_cancelar_format'           => $monto_total_cancelar_format
        ];

        $funcionario->{'es_turnante'}               = $es_turnante;
        $funcionario->{'dias_asistencia'}           = $dias_asistencia;
        $funcionario->{'total_pago_normal'}         = $total_pago_normal;
        $funcionario->{'total_viaticos'}            = $total_viaticos;
        $funcionario->{'total_ajuste_de_dias'}      = $total_ajuste_de_dias;
        $funcionario->{'total_ajustes_de_montos'}   = $total_ajustes_de_montos;
        $funcionario->{'total'}                     = $total;

        return $funcionario;
    }

    private function existFuncionarioInRecarga($funcionario, $recarga)
    {
        $existe         = false;
        if ($funcionario) {
            $query_results = $recarga->whereHas('users', function ($query) use ($funcionario) {
                $query->where('recarga_user.user_id', $funcionario->id);
            })->whereHas('contratos', function ($query) use ($funcionario) {
                $query->where('user_id', $funcionario->id);
            })->count();

            if ($query_results > 0) {
                $existe = true;
            }
        }
        return $existe;
    }

    public function showCartolaRecarga($uuid_esquema)
    {
        try {
            $esquema = Esquema::where('uuid', $uuid_esquema)
                ->with(['reajustes' => function ($query) {
                    $query->where('last_status', 1);
                }])
                ->with('recarga.estados')
                ->first();

            if (($esquema) && ($esquema->active) && ($esquema->recarga->last_status === 2)) {
                setlocale(LC_ALL, "es_ES");
                Carbon::setLocale('es');
                $tz              = 'America/Santiago';

                $fecha_emision = $esquema->recarga->estados()->orderBy('created_at', 'desc')->first();

                $titulo_cartola = (object) [
                    'anio_beneficio'        => $esquema->recarga->anio_beneficio,
                    'mes_beneficio'         => strtoupper(Carbon::createFromDate($esquema->recarga->anio_beneficio, $esquema->recarga->mes_beneficio, '01', $tz)->formatLocalized('%B')),
                    'anio_calculo'          => $esquema->recarga->anio_calculo,
                    'mes_calculo'           => strtoupper(Carbon::createFromDate($esquema->recarga->anio_calculo, $esquema->recarga->mes_calculo, '01', $tz)->formatLocalized('%B')),
                    'monto_dia'             => "$" . number_format($esquema->recarga->monto_dia, 0, ",", "."),
                    'fecha_emision'         => $fecha_emision ? Carbon::parse($fecha_emision->created_at)->format('d-m-Y') : null,
                    'establecimiento'       => $esquema->recarga->establecimiento ? $esquema->recarga->establecimiento->nombre : null
                ];

                $pdf = \PDF::loadView(
                    'pdf.funcionario.cartola',
                    [
                        'titulo_cartola'            => $titulo_cartola,
                        'esquema'                   => $esquema
                    ]
                );

                $pdf->setOptions([
                    'chroot'  => public_path('/img/')
                ]);

                $password_funcionario   = $esquema->funcionario->rut;
                $password_admin         = "1234";
                $pdf->setEncryption($password_funcionario, $password_admin, ['copy', 'print', 'modify']);
                return $pdf->stream("CARTOLA_BENEFICIO_ALIMENTACION_{$esquema->recarga->mes_beneficio}/{$esquema->recarga->anio_beneficio}.pdf");
            } else {
                abort(403);
            }
        } catch (\Error $error) {
            return $error->getMessage();
        }
    }
}
