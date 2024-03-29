<?php

namespace App\Imports\Grupos;

use App\Models\Ausentismo;
use App\Models\Esquema;
use App\Models\Regla;
use App\Models\ReglaHorario;
use App\Models\TipoAusentismo;
use App\Models\User;
use App\Rules\FechaRecarga;
use Carbon\Carbon;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithValidation;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\ToCollection;
use Illuminate\Support\Collection;
use App\Http\Controllers\Admin\Calculos\AnalisisRegistroController;
use App\Http\Controllers\Admin\Esquema\EsquemaController;
use Illuminate\Support\Facades\Log;

class GrupoTresImport implements ToCollection, WithHeadingRow, WithValidation
{
    public function  __construct($recarga, $columnas, $row_columnas)
    {
        $this->recarga                  = $recarga;
        $this->columnas                 = $columnas;
        $this->row_columnas             = $row_columnas;

        $this->rut                      = $this->columnas[0];
        $this->dv                       = $this->columnas[1];
        $this->nombre_tipo_ausentismo   = $this->columnas[2];
        $this->fecha_inicio             = $this->columnas[3];
        $this->fecha_termino            = $this->columnas[4];
        $this->hora_inicio              = $this->columnas[5];
        $this->hora_termino             = $this->columnas[6];
    }

    public $data;

    public function headingRow(): int
    {
        return $this->row_columnas;
    }

    public function transformDate($value, $format = 'Y-m-d')
    {
        try {
            return Carbon::instance(\PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($value));
        } catch (\ErrorException $e) {
            return Carbon::createFromFormat($format, $value);
        }
    }

    public function transformTime($value, $format = 'H:i:s')
    {
        try {
            return Carbon::instance(\PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($value));
        } catch (\ErrorException $e) {
            return Carbon::createFromFormat($format, $value);
        }
    }

    public function collection(Collection $rows)
    {
        try {
            $ausentismos = [];

            if (count($rows) > 0) {
                foreach ($rows as $row) {
                    $rut                = "{$row[$this->rut]}-{$row[$this->dv]}";
                    $funcionario        = User::where('rut', $rut)->first();
                    $tipo_ausentismo    = TipoAusentismo::where('nombre', $row[$this->nombre_tipo_ausentismo])->first();

                    $fecha_inicio   = Carbon::parse($this->transformDate($row[$this->fecha_inicio]));
                    $fecha_termino  = Carbon::parse($this->transformDate($row[$this->fecha_termino]));
                    $hora_inicio    = Carbon::parse($this->transformTime($row[$this->hora_inicio]))->format('H:i:s');
                    $hora_termino   = Carbon::parse($this->transformTime($row[$this->hora_termino]))->format('H:i:s');

                    if ($funcionario && $tipo_ausentismo) {
                        $esquema_controller     = new EsquemaController;
                        $esquema                = $esquema_controller->returnEsquema($funcionario->id, $this->recarga->id);
                        $turnante               = $esquema ? ($esquema->es_turnante != 2 ? true : false) : false;

                        $analisis_registro_controller       = new AnalisisRegistroController;
                        $analisis_ausentismo_grupo_tres      = $analisis_registro_controller->analisisAusentismoGrupoTres($turnante, $this->recarga, $funcionario, $fecha_inicio, $fecha_termino, $hora_inicio, $hora_termino);


                        $data = [
                            'nombres'                                           => $funcionario->nombre_completo,
                            'turnante'                                          => $esquema ? Esquema::TURNANTE_NOM[$esquema->es_turnante] : '--',
                            'fecha_ausentismo'                                  => "{$analisis_ausentismo_grupo_tres->fecha_inicio->format('d-m-Y')} / {$analisis_ausentismo_grupo_tres->fecha_termino->format('d-m-Y')}",
                            'nombre_tipo_ausentismo'                            => $tipo_ausentismo ? $tipo_ausentismo->nombre : '--',
                            'fecha_ausentismo_periodo'                          => "{$analisis_ausentismo_grupo_tres->fecha_inicio_periodo->format('d-m-Y')} {$analisis_ausentismo_grupo_tres->hora_inicio} / {$analisis_ausentismo_grupo_tres->fecha_termino_periodo->format('d-m-Y')} {$analisis_ausentismo_grupo_tres->hora_termino} ",
                            'descuento'                                         => $analisis_ausentismo_grupo_tres->descuento,
                            'dias_naturales'                                    => $analisis_ausentismo_grupo_tres->total_dias_ausentismo_periodo_calculo,
                            'total_dias_habiles_ausentismo_periodo'             => $analisis_ausentismo_grupo_tres->total_dias_habiles_ausentismo_periodo_calculo,
                            'descuento_en_turnos'                               => $analisis_ausentismo_grupo_tres->descuento_en_turnos
                        ];
                        array_push($ausentismos, $data);
                    }
                }
                $this->data = $ausentismos;
            }
        } catch (\Exception $error) {
            Log::info($error->getMessage());
            return $error->getMessage();
        }
    }

    private function calculoDescuento($row, $esquema, $tipo_ausentismo)
    {
        try {
            $fecha_inicio   = Carbon::parse($this->transformDate($row[$this->fecha_inicio]));
            $fecha_termino  = Carbon::parse($this->transformDate($row[$this->fecha_termino]));
            $hora_inicio    = Carbon::parse($this->transformTime($row[$this->hora_inicio]))->format('H:i:s');
            $hora_termino   = Carbon::parse($this->transformTime($row[$this->hora_termino]))->format('H:i:s');

            $diff_days      = $fecha_inicio->diffInDays($fecha_termino) + 1;
            $turnante       = $esquema ? ($esquema->es_turnante != 2 ? true : false) : false;
            $total_descuento = 0;
            $total_feriados  = 0;
            $fds             = 0;
            $feriados_count  = 0;
            $regla           = null;

            if ($diff_days > 1) {
                //mas de un día
                $fecha_inicio_request   = Carbon::parse($this->transformDate($row[$this->fecha_inicio]))->format('Y-m-d');
                $fecha_termino_request  = Carbon::parse($this->transformDate($row[$this->fecha_termino]))->format('Y-m-d');

                for ($i = $fecha_inicio_request; $i <= $fecha_termino_request; $i++) {
                    $feriados_count = $this->recarga->feriados()->where('active', true)->where('fecha', $i)->count();
                    $i_format       = Carbon::parse($i)->isWeekend();

                    if ($i === $fecha_inicio_request) {
                        $ini_new = $hora_inicio;
                        $ter_new = '23:59:59';
                        $ini_new = Carbon::parse($ini_new)->format('H:i:s');
                        $ter_new = Carbon::parse($ter_new)->format('H:i:s');

                        $corresponde_descuento = $this->correspondeDescuento($ini_new, $ter_new, $turnante);

                        if ($corresponde_descuento->corresponde) {
                            $regla = $corresponde_descuento->regla;
                            $total_descuento++;
                            if ($feriados_count > 0) {
                                $total_feriados++;
                            }

                            if ($i_format) {
                                $fds++;
                            }
                        }
                    } else if ($i > $fecha_inicio_request && $i < $fecha_termino_request) {
                        $ini_new = '00:00:00';
                        $ter_new = '23:59:59';

                        $ini_new = Carbon::parse($ini_new)->format('H:i:s');
                        $ter_new = Carbon::parse($ter_new)->format('H:i:s');

                        $corresponde_descuento = $this->correspondeDescuento($ini_new, $ter_new, $turnante);

                        if ($corresponde_descuento->corresponde) {
                            $regla = $corresponde_descuento->regla;
                            $total_descuento++;
                            if ($feriados_count > 0) {
                                $total_feriados++;
                            }

                            if ($i_format) {
                                $fds++;
                            }
                        }
                    } else if ($i === $fecha_termino_request) {
                        $ini_new = '00:00:00';
                        $ter_new = $hora_termino;

                        $ini_new = Carbon::parse($ini_new)->format('H:i:s');
                        $ter_new = Carbon::parse($ter_new)->format('H:i:s');

                        $corresponde_descuento = $this->correspondeDescuento($ini_new, $ter_new, $turnante);

                        if ($corresponde_descuento->corresponde) {
                            $regla = $corresponde_descuento->regla;
                            $total_descuento++;
                            if ($feriados_count > 0) {
                                $total_feriados++;
                            }

                            if ($i_format) {
                                $fds++;
                            }
                        }
                    }
                }
            } else {
                //1 día
                $hora_inicio_request    = Carbon::parse($this->transformTime($row[$this->hora_inicio]));
                $hora_termino_request   = Carbon::parse($this->transformTime($row[$this->hora_termino]));
                $hora_inicio_request    = $hora_inicio_request->format('H:i:s');
                $hora_termino_request   = $hora_termino_request->format('H:i:s');

                $feriados_count         = $this->recarga->feriados()->where('active', true)->where('fecha', $fecha_inicio->format('Y-m-d'))->count();
                $i_format               = Carbon::parse($fecha_inicio->format('Y-m-d'))->isWeekend();

                $corresponde_descuento = $this->correspondeDescuento($hora_inicio_request, $hora_termino_request, $turnante);

                if ($corresponde_descuento->corresponde) {
                    $regla = $corresponde_descuento->regla;
                    $total_descuento = 1;
                    if ($feriados_count > 0) {
                        $total_feriados = $feriados_count;
                    }

                    if ($i_format) {
                        $fds = 1;
                    }
                }
            }

            $fecha_inicio   = Carbon::parse($this->transformDate($row[$this->fecha_inicio]));
            $fecha_termino  = Carbon::parse($this->transformDate($row[$this->fecha_termino]));

            $response = (object) [
                'inicio'                    => "{$fecha_inicio->format('d-m-Y')} {$hora_inicio}",
                'termino'                   => "{$fecha_termino->format('d-m-Y')} {$hora_termino}",
                'descuento'                 => $total_descuento > 0 ? 'Si' : 'No',
                'total_descuento_habiles'   => $total_descuento - $total_feriados - $fds,
                'total_descuento'           => $total_descuento,
                'regla'                     => $regla ? $regla : NULL
            ];

            return $response;
        } catch (\Exception $error) {
            return $error->getMessage();
        }
    }

    private function correspondeDescuento($inicio, $termino, $turnante)
    {
        $corresponde = false;

        $hora_inicio_request    = Carbon::parse($inicio);
        $hora_termino_request   = Carbon::parse($termino);
        $hora_inicio_request    = $hora_inicio_request->format('H:i:s');
        $hora_termino_request   = $hora_termino_request->format('H:i:s');

        $regla = Regla::where('turno_funcionario', $turnante)
            ->where('recarga_id', $this->recarga->id)
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

    public function tieneDescuento($regla, $hora_inicio_archivo_concat, $hora_termino_archivo_concat, $concat_inicio_regla, $concat_termino_regla)
    {
        $tiene_descuento = false;

        $hora_inicio_archivo   = Carbon::parse($hora_inicio_archivo_concat)->timestamp;
        $hora_termino_archivo  = Carbon::parse($hora_termino_archivo_concat)->timestamp;
        $fecha_inicio_regla    = Carbon::parse($concat_inicio_regla)->timestamp;
        $fecha_termino_regla   = Carbon::parse($concat_termino_regla)->timestamp;

        if ($hora_inicio_archivo < $fecha_inicio_regla && $hora_termino_archivo > $fecha_inicio_regla) {
            $tiene_descuento = true;
        } else if ($hora_inicio_archivo < $fecha_termino_regla && $hora_termino_archivo > $fecha_termino_regla) {
            $tiene_descuento = true;
        } else if ($hora_inicio_archivo >= $fecha_inicio_regla && $hora_termino_archivo <= $fecha_termino_regla) {
            $tiene_descuento = true;
        }

        return $tiene_descuento;
    }

    public function esTurnante($funcionario)
    {
        $es_turnante = false;

        $total_turnos                   = $funcionario->turnos()->where('recarga_id', $this->recarga->id)->where('es_turnante', true)->count();
        $total_asistencias              = $funcionario->asistencias()->where('recarga_id', $this->recarga->id)->count();
        $total_dias_contrato_periodo    = $funcionario->contratos()->where('recarga_id', $this->recarga->id)->count();

        if (($total_turnos > 0 && $total_asistencias > 0 && $total_dias_contrato_periodo > 0) || ($total_asistencias > 0 && $total_dias_contrato_periodo > 0)) {
            $es_turnante = true;
        }

        return $es_turnante;
    }

    public function validateRut($value)
    {
        $value  = preg_replace('/[^k0-9]/i', '', $value);
        $dv     = substr($value, -1);
        $numero = substr($value, 0, strlen($value) - 1);
        $i      = 2;
        $suma   = 0;
        foreach (array_reverse(str_split($numero)) as $v) {
            if ($i == 8)
                $i = 2;

            if (is_numeric($v)) {
                $suma += $v * $i;
                ++$i;
            }
        }

        $dvr = 11 - ($suma % 11);

        if ($dvr == 11)
            $dvr = 0;
        if ($dvr == 10)
            $dvr = 'K';

        if ((string)$dvr == strtoupper($dv))
            return true;
        else
            return false;
    }

    public function totalDiasEnPeriodo($fecha_inicio, $fecha_termino)
    {
        try {
            $new_fecha_inicio   = Carbon::parse($fecha_inicio);
            $new_fecha_termino  = Carbon::parse($fecha_termino);

            $new_fecha_inicio   = $new_fecha_inicio->format('Y-m-d');
            $new_fecha_termino  = $new_fecha_termino->format('Y-m-d');

            $tz                     = 'America/Santiago';
            $fecha_recarga_inicio   = Carbon::createFromDate($this->recarga->anio_calculo, $this->recarga->mes_calculo, '01', $tz);
            $fecha_recarga_termino  = Carbon::createFromDate($this->recarga->anio_calculo, $this->recarga->mes_calculo, '01', $tz);
            $fecha_recarga_termino  = $fecha_recarga_termino->endOfMonth();
            $fecha_recarga_inicio   = $fecha_recarga_inicio->format('Y-m-d');
            $fecha_recarga_termino  = $fecha_recarga_termino->format('Y-m-d');

            switch ($this->recarga) {
                case (($new_fecha_inicio >= $fecha_recarga_inicio) && ($new_fecha_termino <= $fecha_recarga_termino)):
                    $inicio             = Carbon::parse($new_fecha_inicio);
                    $termino            = Carbon::parse($new_fecha_termino);
                    break;

                case (($new_fecha_inicio >= $fecha_recarga_inicio) && ($new_fecha_termino > $fecha_recarga_termino)):
                    $inicio             = Carbon::parse($new_fecha_inicio);
                    $termino            = Carbon::parse($fecha_recarga_termino);
                    break;

                case (($new_fecha_inicio < $fecha_recarga_inicio) && ($new_fecha_termino <= $fecha_recarga_termino)):
                    $inicio             = Carbon::parse($fecha_recarga_inicio);
                    $termino            = Carbon::parse($new_fecha_termino);
                    break;

                case (($new_fecha_inicio < $fecha_recarga_inicio) && ($new_fecha_termino > $fecha_recarga_termino)):
                    $inicio             = Carbon::parse($fecha_recarga_inicio);
                    $termino            = Carbon::parse($fecha_recarga_termino);
                    break;

                default:
                    $dias_periodo = 'error';
                    break;
            }
            return array($inicio, $termino);
        } catch (\Exception $error) {
            return $error->getMessage();
        }
    }

    public function existTipoAusentismoInGrupo($nombre_tipo_ausentismo)
    {
        $exist = false;

        $tipo_ausentismo = TipoAusentismo::where('nombre', $nombre_tipo_ausentismo)->first();

        if ($tipo_ausentismo) {
            $regla = Regla::where('tipo_ausentismo_id', $tipo_ausentismo->id)->where('grupo_id', 3)->where('recarga_id', $this->recarga->id)->first();

            if ($regla) {
                $exist = true;
            }
        }
        return $exist;
    }

    public function validateFechasAusentismos($rut_completo, $tipo_ausentismo, $fecha_inicio, $fecha_termino, $hora_inicio, $hora_termino)
    {
        $tiene                  = false;
        $newformat_fecha_ini    = Carbon::parse($fecha_inicio)->format('Y-m-d');
        $newformat_fecha_fin    = Carbon::parse($fecha_termino)->format('Y-m-d');
        $newformat_hora_ini     = Carbon::parse($hora_inicio)->format('H:i:s');
        $newformat_hora_fin     = Carbon::parse($hora_termino)->format('H:i:s');

        $funcionario            = User::where('rut_completo', $rut_completo)->first();
        $tipo_ausentismo        = TipoAusentismo::where('nombre', $tipo_ausentismo)->first();

        if ($funcionario && $tipo_ausentismo) {
            $validacion_1 = Ausentismo::where('recarga_id', $this->recarga->id)
                ->where('user_id', $funcionario->id)
                ->where('tipo_ausentismo_id', $tipo_ausentismo->id)
                ->where('fecha_inicio', $newformat_fecha_ini)
                ->where('fecha_termino', $newformat_fecha_fin)
                ->where('hora_inicio', '<=', $newformat_hora_ini)
                ->where('hora_termino', '>=', $newformat_hora_ini)
                ->where(function ($query) {
                    $query->whereHas('recarga', function ($query) {
                        $query->where('active', true);
                    });
                })->count();

            if ($validacion_1 > 0) {
                $tiene = true;
            }

            $validacion_2 = Ausentismo::where('recarga_id', $this->recarga->id)
                ->where('user_id', $funcionario->id)
                ->where('tipo_ausentismo_id', $tipo_ausentismo->id)
                ->where('fecha_inicio', $newformat_fecha_ini)
                ->where('fecha_termino', $newformat_fecha_fin)
                ->where('hora_inicio', '<=', $newformat_hora_fin)
                ->where('hora_termino', '>=', $newformat_hora_fin)
                ->where(function ($query) {
                    $query->whereHas('recarga', function ($query) {
                        $query->where('active', true);
                    });
                })->count();

            if ($validacion_2 > 0) {
                $tiene = true;
            }

            $validacion_3 = Ausentismo::where('recarga_id', $this->recarga->id)
                ->where('user_id', $funcionario->id)
                ->where('tipo_ausentismo_id', $tipo_ausentismo->id)
                ->where('fecha_inicio', $newformat_fecha_ini)
                ->where('fecha_termino', $newformat_fecha_fin)
                ->where('hora_inicio', '>=', $newformat_hora_ini)
                ->where('hora_termino', '<=', $newformat_hora_fin)
                ->where(function ($query) {
                    $query->whereHas('recarga', function ($query) {
                        $query->where('active', true);
                    });
                })->count();

            if ($validacion_3 > 0) {
                $tiene = true;
            }
        }

        return $tiene;
    }

    public function periodoInRecarga($fecha_inicio, $fecha_termino)
    {
        $in_recarga = true;

        $new_fecha_inicio       = Carbon::parse($fecha_inicio)->format('Y-m');
        $new_fecha_termino      = Carbon::parse($fecha_termino)->format('Y-m');

        $tz                     = 'America/Santiago';
        $fecha_recarga_inicio   = Carbon::createFromDate($this->recarga->anio_calculo, $this->recarga->mes_calculo, '01', $tz)->format('Y-m');
        $fecha_recarga_termino  = Carbon::createFromDate($this->recarga->anio_calculo, $this->recarga->mes_calculo, '01', $tz);
        $fecha_recarga_termino  = $fecha_recarga_termino->endOfMonth()->format('Y-m');

        if ($new_fecha_inicio != $fecha_recarga_inicio || $new_fecha_termino != $fecha_recarga_termino) {
            $in_recarga = false;
        }
        return $in_recarga;
    }

    public function validateDuplicadoAusentismos($rut_completo, $tipo_ausentismo, $fecha_inicio, $fecha_termino, $hora_inicio, $hora_termino)
    {
        $tiene                  = false;
        $newformat_fecha_ini    = Carbon::parse($fecha_inicio)->format('Y-m-d');
        $newformat_fecha_fin    = Carbon::parse($fecha_termino)->format('Y-m-d');
        $newformat_hora_ini     = Carbon::parse($hora_inicio)->format('H:m:s');
        $newformat_hora_fin     = Carbon::parse($hora_termino)->format('H:m:s');

        $funcionario            = User::where('rut_completo', $rut_completo)->first();
        $tipo_ausentismo        = TipoAusentismo::where('nombre', $tipo_ausentismo)->first();

        if ($funcionario && $tipo_ausentismo) {
            $validacion = Ausentismo::where('recarga_id', $this->recarga->id)
                ->where('user_id', $funcionario->id)
                ->where('tipo_ausentismo_id', $tipo_ausentismo->id)
                ->where('fecha_inicio', '=', $newformat_fecha_ini)
                ->where('fecha_termino', '=', $newformat_fecha_fin)
                ->where('hora_inicio', '=', $newformat_hora_ini)
                ->where('hora_termino', '=', $newformat_hora_fin)
                ->where('grupo_id', 3)
                ->where(function ($query) {
                    $query->whereHas('recarga', function ($query) {
                        $query->where('active', true);
                    });
                })->count();

            if ($validacion > 0) {
                $tiene = true;
            }
        }
        return $tiene;
    }

    public function returnKeyFile($data)
    {
        $new_key                = "{$data[$this->rut]}_{$data[$this->fecha_inicio]}_{$data[$this->fecha_termino]}_{$data[$this->nombre_tipo_ausentismo]}_{$data[$this->hora_inicio]}_{$data[$this->hora_termino]}";
        return $new_key;
    }

    public function withValidator($validator)
    {
        $assoc_array = array();

        $validator->after(function ($validator) use ($assoc_array) {
            foreach ($validator->getData() as $key => $data) {
                $new_key                = $this->returnKeyFile($data);
                $rut                    = "{$data[$this->rut]}-{$data[$this->dv]}";
                $fecha_inicio           = Carbon::parse($this->transformDate($data[$this->fecha_inicio]));
                $fecha_termino          = Carbon::parse($this->transformDate($data[$this->fecha_termino]));
                $calculo                = $this->totalDiasEnPeriodo($fecha_inicio, $fecha_termino);
                $fecha_inicio_real      = Carbon::parse($calculo[0])->format('Y-m-d');
                $fecha_termino_real     = Carbon::parse($calculo[1])->format('Y-m-d');

                $hora_inicio            = Carbon::parse($this->transformTime($data[$this->hora_inicio]));
                $hora_termino           = Carbon::parse($this->transformTime($data[$this->hora_termino]));

                $validate               = $this->validateRut($rut);
                $periodo_in_recarga     = $this->periodoInRecarga($fecha_inicio_real, $fecha_termino_real);
                $exist_tipo_ausentismo  = $this->existTipoAusentismoInGrupo($data[$this->nombre_tipo_ausentismo]);
                $fechas                 = $this->validateFechasAusentismos($rut, $data[$this->nombre_tipo_ausentismo], $fecha_inicio, $fecha_termino, $hora_inicio, $hora_termino);
                $duplicado              = $this->validateDuplicadoAusentismos($rut, $data[$this->nombre_tipo_ausentismo], $fecha_inicio, $fecha_termino, $hora_inicio, $hora_termino);

                if (!$validate) {
                    $validator->errors()->add($key, 'Rut incorrecto, por favor verificar. Verificado con Módulo 11.');
                } else if (!$periodo_in_recarga) {
                    $validator->errors()->add($key, "Fechas fuera de periodo de recarga.");
                } else if (!$exist_tipo_ausentismo) {
                    $validator->errors()->add($key, 'Tipo de ausentismo no existe en grupo de reglas seleccionado.');
                } else if ($fechas) {
                    $validator->errors()->add($key, 'Ya existe un ausentismo en la fecha/hora de registro.');
                } else if ($duplicado) {
                    $validator->errors()->add($key, 'Registro duplicado en sistema.');
                } else if (in_array($new_key, $assoc_array)) {
                    $validator->errors()->add($key, 'Registro duplicado en archivo.');
                }
                array_push($assoc_array, $new_key);
            }
        });
    }

    public function rules(): array
    {
        return [
            $this->rut => [
                'required',
                'numeric',
                'exists:users,rut'
            ],
            $this->dv => [
                'required',
                'min:1',
                'max:1'
            ],
            $this->nombre_tipo_ausentismo => [
                'required'
            ],
            $this->fecha_inicio => [
                'required',
                'numeric'
            ],
            $this->fecha_termino => [
                'required',
                'numeric'
            ],
            $this->hora_inicio => [
                'required'
            ],
            $this->hora_termino => [
                'required'
            ],
        ];
    }

    public function customValidationMessages()
    {
        return [
            "{$this->rut}.required"                                         => 'El rut es obligatorio.',
            "{$this->rut}.integer"                                          => 'El rut debe ser un valor numérico.',
            "{$this->rut}.exists"                                           => 'El rut no existe en el sistema',

            "{$this->dv}.required"                                          => 'El dv es obligatorio.',
            "{$this->dv}.min"                                               => 'El dv tiene :min caracter mínimo',
            "{$this->dv}.max"                                               => 'El dv tiene :max caracter máximo',

            "{$this->nombre_tipo_ausentismo}.required"                      => 'El nombre de ausentismo obligatorio.',
            "{$this->nombre_tipo_ausentismo}.exists"                        => 'El nombre de ausentismo no existe en el sistema',

            "{$this->fecha_inicio}.required"                                => 'La fecha de inicio es obligatoria.',
            "{$this->fecha_inicio}.date"                                    => 'La fecha debe ser yyyy-mm-dd.',

            "{$this->fecha_termino}.required"                               => 'La fecha de término es obligatoria.',
            "{$this->fecha_termino}.fecha_termino"                          => 'La fecha debe ser yyyy-mm-dd.',

            "{$this->hora_inicio}.required"                                 => 'La hora de inicio es obligatoria.',

            "{$this->hora_termino}.required"                                => 'La hora de término es obligatoria.',
        ];
    }
}
