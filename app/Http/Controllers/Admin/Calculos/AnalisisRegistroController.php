<?php

namespace App\Http\Controllers\Admin\Calculos;

use App\Http\Controllers\Controller;
use App\Models\Regla;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class AnalisisRegistroController extends Controller
{
    public function analisisAusentismoGrupoUno($es_turnante, $recarga, $funcionario, $fecha_inicio_real, $fecha_termino_real)
    {
        try {
            $analisis_periodo_recarga                       = $this->analisisPeriodoRecarga($recarga, $fecha_inicio_real, $fecha_termino_real);
            $total_dias_ausentismo_periodo                  = 0;
            $total_dias_habiles_ausentismo_periodo          = 0;
            $total_dias_ausentismo_periodo_turno            = 0;
            $total_dias_habiles_ausentismo_periodo_turno    = 0;
            $total_dias_turnante                            = null;

            $total_fds                                      = $this->totalFdsEnPeriodo($analisis_periodo_recarga->fecha_inicio_periodo, $analisis_periodo_recarga->fecha_termino_periodo);
            $total_feriados                                 = $this->totalFeriadosEnPeriodo($recarga, $analisis_periodo_recarga->fecha_inicio_periodo, $analisis_periodo_recarga->fecha_termino_periodo);
            $total_dias_ausentismo_periodo                  = $analisis_periodo_recarga->total_dias_periodo;
            $total_dias_habiles_ausentismo_periodo          = $analisis_periodo_recarga->total_dias_periodo - ($total_feriados + $total_fds);
            $asistencia_total                               = $funcionario->asistencias()->where('recarga_id', $recarga->id)->count();


            if ($asistencia_total > 0) {
                $fechas_feriado                                 = $this->fechasFeriadosEnPeriodo($recarga, $analisis_periodo_recarga->fecha_inicio_periodo, $analisis_periodo_recarga->fecha_termino_periodo);
                $total_dias_turnante                            = $this->totalDiasTurnante($recarga, $funcionario, $analisis_periodo_recarga->fecha_inicio_periodo, $analisis_periodo_recarga->fecha_termino_periodo, $fechas_feriado);
                $total_dias_ausentismo_periodo_turno            = $total_dias_turnante->total_dias_periodo;
                $total_dias_habiles_ausentismo_periodo_turno    = $total_dias_turnante->total_dias_periodo_habiles;
            }

            $response = (object) [
                'fecha_inicio'                                  => $analisis_periodo_recarga->fecha_inicio,
                'fecha_termino'                                 => $analisis_periodo_recarga->fecha_termino,
                'total_dias_ausentismo'                         => $analisis_periodo_recarga->total_dias,
                'fecha_inicio_periodo'                          => $analisis_periodo_recarga->fecha_inicio_periodo,
                'fecha_termino_periodo'                         => $analisis_periodo_recarga->fecha_termino_periodo,

                'total_dias_ausentismo_periodo'                 => $total_dias_ausentismo_periodo,
                'total_dias_habiles_ausentismo_periodo'         => $total_dias_habiles_ausentismo_periodo,
                'total_dias_ausentismo_periodo_turno'           => $total_dias_ausentismo_periodo_turno,
                'total_dias_habiles_ausentismo_periodo_turno'   => $total_dias_habiles_ausentismo_periodo_turno,

                'total_dias_ausentismo_periodo_calculo'         => $es_turnante ? $total_dias_ausentismo_periodo_turno : $total_dias_ausentismo_periodo,
                'total_dias_habiles_ausentismo_periodo_calculo' => $es_turnante ? $total_dias_habiles_ausentismo_periodo_turno : $total_dias_habiles_ausentismo_periodo,

                'descuento_en_turnos'                           => $total_dias_turnante ? $total_dias_turnante->descuento_en_turnos : false
            ];

            return $response;
        } catch (\Exception $error) {
            Log::info($error->getMessage());
        }
    }

    public function analisisAusentismoGrupoDos($turnante, $recarga, $funcionario, $fecha_inicio_real, $fecha_termino_real, $meridiano, $tipo_ausentismo)
    {
        try {
            $analisis_periodo_recarga                       = $this->analisisPeriodoRecarga($recarga, $fecha_inicio_real, $fecha_termino_real);
            $total_dias_ausentismo_periodo                  = 0;
            $total_dias_habiles_ausentismo_periodo          = 0;
            $total_dias_ausentismo_periodo_turno            = 0;
            $total_dias_habiles_ausentismo_periodo_turno    = 0;
            $total_dias_turnante                            = null;

            $total_fds                                      = $this->totalFdsEnPeriodo($analisis_periodo_recarga->fecha_inicio_periodo, $analisis_periodo_recarga->fecha_termino_periodo);
            $total_feriados                                 = $this->totalFeriadosEnPeriodo($recarga, $analisis_periodo_recarga->fecha_inicio_periodo, $analisis_periodo_recarga->fecha_termino_periodo);
            $total_dias_ausentismo_periodo                  = $analisis_periodo_recarga->total_dias_periodo;
            $total_dias_habiles_ausentismo_periodo          = $analisis_periodo_recarga->total_dias_periodo - ($total_feriados + $total_fds);
            $asistencia_total                               = $funcionario->asistencias()->where('recarga_id', $recarga->id)->count();


            if ($asistencia_total > 0) {
                $fechas_feriado                                 = $this->fechasFeriadosEnPeriodo($recarga, $analisis_periodo_recarga->fecha_inicio_periodo, $analisis_periodo_recarga->fecha_termino_periodo);
                $total_dias_turnante                            = $this->totalDiasTurnante($recarga, $funcionario, $analisis_periodo_recarga->fecha_inicio_periodo, $analisis_periodo_recarga->fecha_termino_periodo, $fechas_feriado);
                $total_dias_ausentismo_periodo_turno            = $total_dias_turnante->total_dias_periodo;
                $total_dias_habiles_ausentismo_periodo_turno    = $total_dias_turnante->total_dias_periodo_habiles;
            }

            $regla = $recarga->reglas()
                ->where('turno_funcionario', $turnante)
                ->where('grupo_id', 2)
                ->where('tipo_ausentismo_id', $tipo_ausentismo->id)
                ->whereHas('meridianos', function ($query) use ($meridiano) {
                    $query->where('meridiano_regla.meridiano_id', $meridiano->id)
                        ->where('meridiano_regla.active', true);
                })
                ->first();

            $response = (object) [
                'fecha_inicio'                                  => $analisis_periodo_recarga->fecha_inicio,
                'fecha_termino'                                 => $analisis_periodo_recarga->fecha_termino,
                'total_dias_ausentismo'                         => $analisis_periodo_recarga->total_dias,
                'fecha_inicio_periodo'                          => $analisis_periodo_recarga->fecha_inicio_periodo,
                'fecha_termino_periodo'                         => $analisis_periodo_recarga->fecha_termino_periodo,

                'total_dias_ausentismo_periodo'                 => $total_dias_ausentismo_periodo,
                'total_dias_habiles_ausentismo_periodo'         => $total_dias_habiles_ausentismo_periodo,
                'total_dias_ausentismo_periodo_turno'           => $total_dias_ausentismo_periodo_turno,
                'total_dias_habiles_ausentismo_periodo_turno'   => $total_dias_habiles_ausentismo_periodo_turno,

                'total_dias_ausentismo_periodo_calculo'         => $turnante ? $total_dias_ausentismo_periodo_turno : $total_dias_ausentismo_periodo,
                'total_dias_habiles_ausentismo_periodo_calculo' => $turnante ? $total_dias_habiles_ausentismo_periodo_turno : $total_dias_habiles_ausentismo_periodo,

                'descuento_en_turnos'                           => $total_dias_turnante ? $total_dias_turnante->descuento_en_turnos : false,
                'descuento_value'                               => $regla ? true : false,
                'regla'                                         => $regla
            ];

            return $response;
        } catch (\Exception $error) {
            Log::info($error->getMessage());
        }
    }

    public function analisisAusentismoGrupoTres($es_turnante, $recarga, $funcionario, $fecha_inicio_real, $fecha_termino_real, $hora_inicio, $hora_termino)
    {
        try {
            $analisis_periodo_recarga   = $this->analisisPeriodoRecarga($recarga, $fecha_inicio_real, $fecha_termino_real);
            $calculo_grupo_tres         = $this->calculoGrupoTres($analisis_periodo_recarga, $hora_inicio, $hora_termino, $recarga, $es_turnante, $funcionario);

            $hora_inicio                = Carbon::parse($hora_inicio)->format('H:i:s');
            $hora_termino               = Carbon::parse($hora_termino)->format('H:i:s');

            $response = (object) [
                'fecha_inicio'                                  => $analisis_periodo_recarga->fecha_inicio,
                'fecha_termino'                                 => $analisis_periodo_recarga->fecha_termino,
                'total_dias_ausentismo'                         => $analisis_periodo_recarga->total_dias,
                'fecha_inicio_periodo'                          => $analisis_periodo_recarga->fecha_inicio_periodo,
                'fecha_termino_periodo'                         => $analisis_periodo_recarga->fecha_termino_periodo,

                'total_dias_ausentismo_periodo'                 => $calculo_grupo_tres->total_dias_ausentismo_periodo,
                'total_dias_habiles_ausentismo_periodo'         => $calculo_grupo_tres->total_dias_habiles_ausentismo_periodo,

                'total_dias_ausentismo_periodo_turno'           => $calculo_grupo_tres->total_dias_ausentismo_periodo_turno,
                'total_dias_habiles_ausentismo_periodo_turno'   => $calculo_grupo_tres->total_dias_habiles_ausentismo_periodo_turno,

                'total_dias_ausentismo_periodo_calculo'         => $es_turnante ? $calculo_grupo_tres->total_dias_ausentismo_periodo_turno : $calculo_grupo_tres->total_dias_ausentismo_periodo,
                'total_dias_habiles_ausentismo_periodo_calculo' => $es_turnante ? $calculo_grupo_tres->total_dias_habiles_ausentismo_periodo_turno : $calculo_grupo_tres->total_dias_habiles_ausentismo_periodo,

                'descuento'                                     => $calculo_grupo_tres->descuento ? 'Si' : 'No',
                'descuento_value'                               => $calculo_grupo_tres->descuento,
                'descuento_en_turnos'                           => $calculo_grupo_tres->descuento_en_turnos ? 'Si' : 'No',
                'descuento_turno_libre_value'                   => $calculo_grupo_tres->descuento_en_turnos,

                'hora_inicio'                                   => $hora_inicio,
                'hora_termino'                                  => $hora_termino,

                'regla'                                         => $calculo_grupo_tres->regla,

                'diferencia_en_horas'                           => 0
            ];

            return $response;
        } catch (\Exception $error) {
            Log::info($error->getMessage());
        }
    }

    public function analisisViaticos($es_turnante, $recarga, $funcionario, $fecha_inicio_real, $fecha_termino_real)
    {
        try {
            $analisis_periodo_recarga                       = $this->analisisPeriodoRecarga($recarga, $fecha_inicio_real, $fecha_termino_real);
            $total_dias_ausentismo_periodo                  = 0;
            $total_dias_habiles_ausentismo_periodo          = 0;
            $total_dias_ausentismo_periodo_turno            = 0;
            $total_dias_habiles_ausentismo_periodo_turno    = 0;
            $total_dias_turnante                            = null;

            $total_fds                                      = $this->totalFdsEnPeriodo($analisis_periodo_recarga->fecha_inicio_periodo, $analisis_periodo_recarga->fecha_termino_periodo);
            $total_feriados                                 = $this->totalFeriadosEnPeriodo($recarga, $analisis_periodo_recarga->fecha_inicio_periodo, $analisis_periodo_recarga->fecha_termino_periodo);
            $total_dias_ausentismo_periodo                  = $analisis_periodo_recarga->total_dias_periodo;
            $total_dias_habiles_ausentismo_periodo          = $analisis_periodo_recarga->total_dias_periodo - ($total_feriados + $total_fds);
            $asistencia_total                               = $funcionario->asistencias()->where('recarga_id', $recarga->id)->count();


            if ($asistencia_total > 0) {
                $fechas_feriado                                 = $this->fechasFeriadosEnPeriodo($recarga, $analisis_periodo_recarga->fecha_inicio_periodo, $analisis_periodo_recarga->fecha_termino_periodo);
                $total_dias_turnante                            = $this->totalDiasTurnante($recarga, $funcionario, $analisis_periodo_recarga->fecha_inicio_periodo, $analisis_periodo_recarga->fecha_termino_periodo, $fechas_feriado);
                $total_dias_ausentismo_periodo_turno            = $total_dias_turnante->total_dias_periodo;
                $total_dias_habiles_ausentismo_periodo_turno    = $total_dias_turnante->total_dias_periodo_habiles;
            }

            $response = (object) [
                'fecha_inicio'                                  => $analisis_periodo_recarga->fecha_inicio,
                'fecha_termino'                                 => $analisis_periodo_recarga->fecha_termino,
                'total_dias_ausentismo'                         => $analisis_periodo_recarga->total_dias,
                'fecha_inicio_periodo'                          => $analisis_periodo_recarga->fecha_inicio_periodo,
                'fecha_termino_periodo'                         => $analisis_periodo_recarga->fecha_termino_periodo,

                'total_dias_ausentismo_periodo'                 => $total_dias_ausentismo_periodo,
                'total_dias_habiles_ausentismo_periodo'         => $total_dias_habiles_ausentismo_periodo,
                'total_dias_ausentismo_periodo_turno'           => $total_dias_ausentismo_periodo_turno,
                'total_dias_habiles_ausentismo_periodo_turno'   => $total_dias_habiles_ausentismo_periodo_turno,

                'total_dias_ausentismo_periodo_calculo'         => $es_turnante ? $total_dias_ausentismo_periodo_turno : $total_dias_ausentismo_periodo,
                'total_dias_habiles_ausentismo_periodo_calculo' => $es_turnante ? $total_dias_habiles_ausentismo_periodo_turno : $total_dias_habiles_ausentismo_periodo,

                'descuento_en_turnos'                           => $total_dias_turnante ? $total_dias_turnante->descuento_en_turnos : false
            ];

            return $response;
        } catch (\Exception $error) {
            Log::info($error->getMessage());
        }
    }

    private function calculoGrupoTres($analisis_periodo_recarga, $hora_inicio, $hora_termino, $recarga, $es_turnante, $funcionario)
    {
        $tz                         = 'America/Santiago';
        $date_recarga               = Carbon::createFromDate($recarga->anio_calculo, $recarga->mes_calculo, '01', $tz);
        $mont_last                  = $date_recarga->format('m');
        $year_last                  = $date_recarga->format('Y');
        $descuento                  = false;

        $diff_dias_periodo          = $analisis_periodo_recarga->total_dias_periodo;
        $total_descuento            = 0;
        $total_feriados             = 0;
        $fds                        = 0;
        $feriados_count             = 0;
        $feriados_count_turno       = 0;
        $regla                      = null;

        $descuento_en_turnos = false;
        $total_dias_ausentismo_periodo                  = 0;
        $total_dias_habiles_ausentismo_periodo          = 0;
        $total_dias_ausentismo_periodo_turno            = 0;
        $total_dias_habiles_ausentismo_periodo_turno    = 0;
        $corresponde_count = 0;



        $hora_inicio                = Carbon::parse($hora_inicio)->format('H:i:s');
        $hora_termino               = Carbon::parse($hora_termino)->format('H:i:s');
        $fecha_inicio_periodo   = $analisis_periodo_recarga->fecha_inicio_periodo->format('Y-m-d');
        $fecha_termino_periodo  = $analisis_periodo_recarga->fecha_termino_periodo->format('Y-m-d');

        if ($diff_dias_periodo > 1) {
            $asistencia_total       = $funcionario->asistencias()->where('recarga_id', $recarga->id)->count();
            for ($i = $fecha_inicio_periodo; $i <= $fecha_termino_periodo; $i++) {
                if ($i === $fecha_inicio_periodo) {
                    $ini_new                = $hora_inicio;
                    $ter_new                = '23:59:59';
                    $ini_new                = Carbon::parse($ini_new)->format('H:i:s');
                    $ter_new                = Carbon::parse($ter_new)->format('H:i:s');
                    $corresponde_descuento  = $this->correspondeDescuento($recarga, $ini_new, $ter_new, $es_turnante);

                    if ($corresponde_descuento->corresponde) {
                        $regla = $corresponde_descuento->regla;

                        $corresponde_count++;
                        $total_dias_ausentismo_periodo++;
                        $i_format                       = Carbon::parse($i)->isWeekend();
                        $i_feriado                      = $recarga->feriados()->where('active', true)->where('fecha', $i)->count();

                        if ($i_format || $i_feriado > 0) {
                            $feriados_count++;
                        }

                        if ($asistencia_total > 0) {
                            //turnante
                            $fecha_en_turno_l_n  = $funcionario->asistencias()
                                ->whereHas('recarga', function ($q) use ($mont_last, $year_last) {
                                    $q->where('mes_beneficio', $mont_last)
                                        ->where('anio_beneficio', $year_last)
                                        ->where('active', true);
                                })
                                ->where('fecha', $i)
                                ->whereIn('tipo_asistencia_turno_id', [1, 2])
                                ->first();

                            if ($fecha_en_turno_l_n) {
                                $descuento_en_turnos = true;
                                $total_dias_ausentismo_periodo_turno++;

                                $i_format   = Carbon::parse($i)->isWeekend();
                                $i_feriado  = $recarga->feriados()->where('active', true)->where('fecha', $i)->count();

                                if ($i_format || $i_feriado > 0) {
                                    $feriados_count_turno++;
                                }
                            }
                        }
                    }
                } else if ($i > $fecha_inicio_periodo && $i < $fecha_termino_periodo) {
                    $ini_new = '00:00:00';
                    $ter_new = '23:59:59';

                    $ini_new = Carbon::parse($ini_new)->format('H:i:s');
                    $ter_new = Carbon::parse($ter_new)->format('H:i:s');

                    $corresponde_descuento = $this->correspondeDescuento($recarga, $ini_new, $ter_new, $es_turnante);

                    if ($corresponde_descuento->corresponde) {
                        $corresponde_count++;
                        $total_dias_ausentismo_periodo++;
                        $i_format                       = Carbon::parse($i)->isWeekend();
                        $i_feriado                      = $recarga->feriados()->where('active', true)->where('fecha', $i)->count();
                        if ($i_format || $i_feriado > 0) {
                            $feriados_count++;
                        }

                        if ($asistencia_total > 0) {
                            //turnante
                            $fecha_en_turno_l_n  = $funcionario->asistencias()
                                ->whereHas('recarga', function ($q) use ($mont_last, $year_last) {
                                    $q->where('mes_beneficio', $mont_last)
                                        ->where('anio_beneficio', $year_last)
                                        ->where('active', true);
                                })
                                ->where('fecha', $i)
                                ->whereIn('tipo_asistencia_turno_id', [1, 2])
                                ->first();

                            if ($fecha_en_turno_l_n) {
                                $descuento_en_turnos = true;
                                $total_dias_ausentismo_periodo_turno++;

                                $i_format   = Carbon::parse($i)->isWeekend();
                                $i_feriado  = $recarga->feriados()->where('active', true)->where('fecha', $i)->count();

                                if ($i_format || $i_feriado > 0) {
                                    $feriados_count_turno++;
                                }
                            }
                        }
                    }
                } else if ($i === $fecha_termino_periodo) {
                    $ini_new = '00:00:00';
                    $ter_new = $hora_termino;

                    $ini_new = Carbon::parse($ini_new)->format('H:i:s');
                    $ter_new = Carbon::parse($ter_new)->format('H:i:s');

                    $corresponde_descuento = $this->correspondeDescuento($recarga, $ini_new, $ter_new, $es_turnante);
                    if ($corresponde_descuento->corresponde) {
                        $corresponde_count++;
                        $total_dias_ausentismo_periodo++;
                        $i_format                       = Carbon::parse($i)->isWeekend();
                        $i_feriado                      = $recarga->feriados()->where('active', true)->where('fecha', $i)->count();
                        if ($i_format || $i_feriado > 0) {
                            $feriados_count++;
                        }

                        if ($asistencia_total > 0) {
                            //turnante
                            $fecha_en_turno_l_n  = $funcionario->asistencias()
                                ->whereHas('recarga', function ($q) use ($mont_last, $year_last) {
                                    $q->where('mes_beneficio', $mont_last)
                                        ->where('anio_beneficio', $year_last)
                                        ->where('active', true);
                                })
                                ->where('fecha', $i)
                                ->whereIn('tipo_asistencia_turno_id', [1, 2])
                                ->first();

                            if ($fecha_en_turno_l_n) {
                                $descuento_en_turnos = true;
                                $total_dias_ausentismo_periodo_turno++;

                                $i_format   = Carbon::parse($i)->isWeekend();
                                $i_feriado  = $recarga->feriados()->where('active', true)->where('fecha', $i)->count();

                                if ($i_format || $i_feriado > 0) {
                                    $feriados_count_turno++;
                                }
                            }
                        }
                    }
                }
            }

            $total_dias_ausentismo_periodo                  = $total_dias_ausentismo_periodo;
            $total_dias_habiles_ausentismo_periodo          = $total_dias_ausentismo_periodo - $feriados_count;

            $total_dias_ausentismo_periodo_turno            = $total_dias_ausentismo_periodo_turno;
            $total_dias_habiles_ausentismo_periodo_turno    = $total_dias_ausentismo_periodo_turno - $feriados_count_turno;
        } else {
            $asistencia_total       = $funcionario->asistencias()->where('recarga_id', $recarga->id)->count();
            $corresponde_descuento  = $this->correspondeDescuento($recarga, $hora_inicio, $hora_termino, $es_turnante);

            $total_dias_ausentismo_periodo          = 1;
            $total_dias_habiles_ausentismo_periodo  = 1;
            $i_format                       = Carbon::parse($fecha_inicio_periodo)->isWeekend();
            $i_feriado                      = $recarga->feriados()->where('active', true)->where('fecha', $fecha_inicio_periodo)->count();

            if ($i_format || $i_feriado > 0) {
                $total_dias_habiles_ausentismo_periodo = 0;
            }

            if ($asistencia_total > 0) {
                //turnante
                $fecha_en_turno_l_n  = $funcionario->asistencias()
                    ->whereHas('recarga', function ($q) use ($mont_last, $year_last) {
                        $q->where('mes_beneficio', $mont_last)
                            ->where('anio_beneficio', $year_last)
                            ->where('active', true);
                    })
                    ->where('fecha', $fecha_inicio_periodo)
                    ->whereIn('tipo_asistencia_turno_id', [1, 2])
                    ->first();

                if ($fecha_en_turno_l_n) {
                    $descuento_en_turnos                            = true;
                    $total_dias_ausentismo_periodo_turno            = 1;
                    $total_dias_habiles_ausentismo_periodo_turno    = 1;

                    $i_format   = Carbon::parse($fecha_inicio_periodo)->isWeekend();
                    $i_feriado  = $recarga->feriados()->where('active', true)->where('fecha', $fecha_inicio_periodo)->count();

                    if ($i_format || $i_feriado > 0) {
                        $total_dias_habiles_ausentismo_periodo_turno = 0;
                    }
                }
            }
            $corresponde_count = $corresponde_descuento->corresponde ? 1 : 0;
            $regla = $corresponde_descuento->regla;
        }

        $response = (object) [
            'total_dias_ausentismo_periodo'                 => $total_dias_ausentismo_periodo,
            'total_dias_habiles_ausentismo_periodo'         => $total_dias_habiles_ausentismo_periodo,
            'total_dias_ausentismo_periodo_turno'           => $total_dias_ausentismo_periodo_turno,
            'total_dias_habiles_ausentismo_periodo_turno'   => $total_dias_habiles_ausentismo_periodo_turno,
            'descuento'                                     => $corresponde_count > 0 ? true : false,
            'descuento_en_turnos'                           => $descuento_en_turnos,
            'regla'                                         => $regla
        ];

        return $response;
    }

    private function correspondeDescuento($recarga, $inicio, $termino, $es_turnante)
    {
        $corresponde = false;

        $hora_inicio_request    = Carbon::parse($inicio);
        $hora_termino_request   = Carbon::parse($termino);
        $hora_inicio_request    = $hora_inicio_request->format('H:i:s');
        $hora_termino_request   = $hora_termino_request->format('H:i:s');

        $regla = Regla::where('turno_funcionario', $es_turnante)
            ->where('recarga_id', $recarga->id)
            ->whereHas('horarios', function ($query) use ($hora_inicio_request, $hora_termino_request) {
                $query->where(function ($subQuery) use ($hora_inicio_request, $hora_termino_request) {
                    $subQuery->where([
                        ['hora_inicio', '>', $hora_inicio_request],
                        ['hora_inicio', '<', $hora_termino_request],
                    ])->orWhere([
                        ['hora_termino', '>', $hora_inicio_request],
                        ['hora_termino', '<', $hora_termino_request],
                    ])->orWhere([
                        ['hora_inicio', '>=', $hora_inicio_request],
                        ['hora_termino', '<=', $hora_termino_request],
                    ]);
                });
            })
            ->first();

        if ($regla) {
            $corresponde = true;
        }

        $response = (object) [
            'corresponde'   => $corresponde,
            'regla'         => $regla
        ];

        return $response;
    }

    private function analisisPeriodoRecarga($recarga, $fecha_inicio, $fecha_termino)
    {
        try {
            $new_fecha_inicio   = Carbon::parse($fecha_inicio);
            $new_fecha_termino  = Carbon::parse($fecha_termino);
            $new_fecha_inicio   = $new_fecha_inicio->format('Y-m-d');
            $new_fecha_termino  = $new_fecha_termino->format('Y-m-d');

            $tz                     = 'America/Santiago';
            $fecha_recarga_inicio   = Carbon::createFromDate($recarga->anio_calculo, $recarga->mes_calculo, '01', $tz);
            $fecha_recarga_termino  = Carbon::createFromDate($recarga->anio_calculo, $recarga->mes_calculo, '01', $tz);
            $fecha_recarga_termino  = $fecha_recarga_termino->endOfMonth();
            $fecha_recarga_inicio   = $fecha_recarga_inicio->format('Y-m-d');
            $fecha_recarga_termino  = $fecha_recarga_termino->format('Y-m-d');
            $total_dias_periodo     = 0;

            switch ($recarga) {
                case (($new_fecha_inicio >= $fecha_recarga_inicio) && ($new_fecha_termino <= $fecha_recarga_termino)):
                    $inicio_periodo             = Carbon::parse($new_fecha_inicio);
                    $termino_periodo            = Carbon::parse($new_fecha_termino);
                    $total_dias_periodo         = $inicio_periodo->diffInDays($termino_periodo) + 1;
                    break;

                case (($new_fecha_inicio >= $fecha_recarga_inicio) && ($new_fecha_termino > $fecha_recarga_termino)):
                    $inicio_periodo             = Carbon::parse($new_fecha_inicio);
                    $termino_periodo            = Carbon::parse($fecha_recarga_termino);
                    $total_dias_periodo         = $inicio_periodo->diffInDays($termino_periodo) + 1;
                    break;

                case (($new_fecha_inicio < $fecha_recarga_inicio) && ($new_fecha_termino <= $fecha_recarga_termino)):
                    $inicio_periodo             = Carbon::parse($fecha_recarga_inicio);
                    $termino_periodo            = Carbon::parse($new_fecha_termino);
                    $total_dias_periodo         = $inicio_periodo->diffInDays($termino_periodo) + 1;
                    break;

                case (($new_fecha_inicio < $fecha_recarga_inicio) && ($new_fecha_termino > $fecha_recarga_termino)):
                    $inicio_periodo             = Carbon::parse($fecha_recarga_inicio);
                    $termino_periodo            = Carbon::parse($fecha_recarga_termino);
                    $total_dias_periodo         = $inicio_periodo->diffInDays($termino_periodo) + 1;
                    break;

                default:
                    $total_dias_periodo = 'error';
                    break;
            }
            $ini            = Carbon::parse($fecha_inicio);
            $ter            = Carbon::parse($fecha_termino);
            $total_dias     = $ini->diffInDays($ter) + 1;

            $response = (object) [
                'fecha_inicio'                  => $fecha_inicio,
                'fecha_termino'                 => $fecha_termino,
                'total_dias'                    => $total_dias,
                'fecha_inicio_periodo'          => $inicio_periodo,
                'fecha_termino_periodo'         => $termino_periodo,
                'total_dias_periodo'            => $total_dias_periodo
            ];

            return $response;
        } catch (\Exception $error) {
            return $error->getMessage();
        }
    }

    private function totalDiasTurnante($recarga, $funcionario, $inicio_periodo, $termino_periodo,  $fechas_feriado)
    {
        try {
            $inicio_periodo             = Carbon::parse($inicio_periodo);
            $termino_periodo            = Carbon::parse($termino_periodo);

            $descuento_en_turnos        = false;
            $tz                         = 'America/Santiago';
            $date_recarga               = Carbon::createFromDate($recarga->anio_calculo, $recarga->mes_calculo, '01', $tz);
            $mont_last                  = $date_recarga->format('m');
            $year_last                  = $date_recarga->format('Y');
            $total_dias_periodo         = 0;
            $total_dias_periodo_habiles  = 0;

            $total_dias_periodo_habiles = $funcionario->asistencias()
                ->whereHas('recarga', function ($q) use ($mont_last, $year_last) {
                    $q->where('mes_beneficio', $mont_last)
                        ->where('anio_beneficio', $year_last)
                        ->where('active', true);
                })
                ->whereIn('tipo_asistencia_turno_id', [1, 2])
                ->whereBetween('fecha', [$inicio_periodo->format('Y-m-d'), $termino_periodo->format('Y-m-d')])
                ->where(function ($q) use ($fechas_feriado) {
                    $q->whereNotIn('fecha', $fechas_feriado);
                })
                ->count();

            $total_dias_periodo = $funcionario->asistencias()
                ->whereHas('recarga', function ($q) use ($mont_last, $year_last) {
                    $q->where('mes_beneficio', $mont_last)
                        ->where('anio_beneficio', $year_last)
                        ->where('active', true);
                })
                ->whereIn('tipo_asistencia_turno_id', [1, 2])
                ->whereBetween('fecha', [$inicio_periodo->format('Y-m-d'), $termino_periodo->format('Y-m-d')])
                ->count();

            if ($total_dias_periodo_habiles > 0 || $total_dias_periodo > 0) {
                $descuento_en_turnos = true;
            }

            $response = (object) [
                'total_dias_periodo'            => $total_dias_periodo,
                'total_dias_periodo_habiles'    => $total_dias_periodo_habiles,
                'descuento_en_turnos'           => $descuento_en_turnos
            ];

            return $response;
        } catch (\Exception $error) {
            Log::info($error->getMessage());
            return $error->getMessage();
        }
    }

    private function fechasFeriadosEnPeriodo($recarga, $inicioPeriodo, $terminoPeriodo)
    {
        try {
            $fechas                 = [];
            $inicio                 = Carbon::parse($inicioPeriodo)->format('Y-m-d');
            $termino                = Carbon::parse($terminoPeriodo)->format('Y-m-d');
            $feriados_in_recarga    = $recarga->feriados()->where('active', true)->whereBetween('fecha', [$inicio, $termino])->pluck('fecha')->toArray();

            for ($i = $inicio; $i <= $termino; $i++) {
                $i_format       = Carbon::parse($i)->isWeekend();
                if ($i_format) {
                    array_push($fechas, $i);
                }
            }

            $total_fechas = array_merge($fechas, $feriados_in_recarga);

            return $fechas;
        } catch (\Exception $error) {
            Log::info($error->getMessage());
            return $error->getMessage();
        }
    }

    private function totalFdsEnPeriodo($inicio_periodo, $termino_periodo)
    {
        $fds        = 0;
        $inicio     = Carbon::parse($inicio_periodo)->format('Y-m-d');
        $termino    = Carbon::parse($termino_periodo)->format('Y-m-d');
        for ($i = $inicio; $i <= $termino; $i++) {
            $i_format       = Carbon::parse($i)->isWeekend();
            if ($i_format) {
                $fds++;
            }
        }
        return $fds;
    }

    private function totalFeriadosEnPeriodo($recarga, $inicio_periodo, $termino_periodo)
    {
        $total_feriados = $recarga->feriados()->where('active', true)->whereBetween('fecha', [$inicio_periodo->format('Y-m-d'), $termino_periodo->format('Y-m-d')])->count();

        return $total_feriados;
    }
}
