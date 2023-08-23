<?php

namespace App\Imports\Grupos;

use App\Http\Controllers\Admin\Calculos\ActualizarEsquemaController;
use App\Models\Ausentismo;
use App\Models\Meridiano;
use App\Models\Regla;
use App\Models\TipoAusentismo;
use App\Models\User;
use App\Rules\FechaRecarga;
use Carbon\Carbon;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithValidation;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use App\Http\Controllers\Admin\Esquema\EsquemaController;
use App\Http\Controllers\Admin\Calculos\AnalisisRegistroController;
use Illuminate\Support\Facades\Log;

class GrupoDosImportStore implements ToModel, WithHeadingRow, WithValidation
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
        $this->total_ausentismo         = $this->columnas[5];
        $this->meridiano                = $this->columnas[6];
    }

    public $importados  = 0;
    public $editados    = 0;

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

    public function isDecimal($n)
    {
        // Note that floor returns a float
        return is_numeric($n) && floor($n) != $n;
    }


    public function model(array $row)
    {
        try {
            $rut                = "{$row[$this->rut]}-{$row[$this->dv]}";
            $funcionario        = User::where('rut', $rut)->first();

            $nom_tipo_ausentismo    = ltrim($row[$this->nombre_tipo_ausentismo]);
            $tipo_ausentismo        = TipoAusentismo::where('codigo_sirh', $nom_tipo_ausentismo)->orWhere('nombre', $nom_tipo_ausentismo)->first();
            $meridiano              = Meridiano::where('codigo', $row[$this->meridiano])->orWhere('nombre', $row[$this->meridiano])->first();

            if ($funcionario && $tipo_ausentismo && $meridiano) {
                $esquema_controller     = new EsquemaController;
                $esquema                = $esquema_controller->returnEsquema($funcionario->id, $this->recarga->id);
                $turnante               = $esquema ? ($esquema->es_turnante != 2 ? true : false) : false;
                /* $regla                  = $this->recarga->reglas()
                    ->where('turno_funcionario', $turnante)
                    ->where('grupo_id', 2)
                    ->where('tipo_ausentismo_id', $tipo_ausentismo->id)
                    ->whereHas('meridianos', function ($query) use ($meridiano) {
                        $query->where('meridiano_regla.meridiano_id', $meridiano->id)
                            ->where('meridiano_regla.active', true);
                    })
                    ->first(); */
                $fecha_inicio           = Carbon::parse($this->transformDate($row[strtolower($this->fecha_inicio)]));
                $fecha_termino          = Carbon::parse($this->transformDate($row[strtolower($this->fecha_termino)]));
                /* $calculo                = $this->totalDiasEnPeriodo($fecha_inicio->format('d-m-Y'), $fecha_termino->format('d-m-Y'));
                $total_dias_ausentismo  = $row[$this->total_ausentismo];

                if ($total_dias_ausentismo) {
                    $is_decimal = $this->isDecimal($total_dias_ausentismo);
                    if ($is_decimal) {
                        $total_dias_ausentismo = number_format($total_dias_ausentismo, 2, '.', '');
                    }
                } */

                $analisis_registro_controller       = new AnalisisRegistroController;
                $analisis_ausentismo_grupo_dos      = $analisis_registro_controller->analisisAusentismoGrupoDos($turnante, $this->recarga, $funcionario, $fecha_inicio, $fecha_termino, $meridiano, $tipo_ausentismo);

                /* $data = [
                    'fecha_inicio'                                      => $calculo[1] != null ? Carbon::parse($calculo[1])->format('Y-m-d') : NULL,
                    'fecha_termino'                                     => $calculo[2] != null ? Carbon::parse($calculo[2])->format('Y-m-d') : NULL,
                    'fecha_inicio_periodo'                              => $calculo[4] != null ? Carbon::parse($calculo[4])->format('Y-m-d') : NULL,
                    'fecha_termino_periodo'                             => $calculo[5] != null ? Carbon::parse($calculo[5])->format('Y-m-d') : NULL,
                    'total_dias_ausentismo'                             => $calculo[0],
                    'total_dias_ausentismo_periodo'                     => $calculo[3],
                    'total_dias_habiles_ausentismo_periodo_meridiano'   => $total_dias_ausentismo != null ? $total_dias_ausentismo : 0,
                    'user_id'                                           => $funcionario->id,
                    'tipo_ausentismo_id'                                => $tipo_ausentismo->id,
                    'regla_id'                                          => $regla ? $regla->id : null,
                    'grupo_id'                                          => 2,
                    'recarga_id'                                        => $this->recarga->id,
                    'meridiano_id'                                      => $meridiano->id,
                    'esquema_id'                                        => $esquema ? $esquema->id : NULL,
                    'tiene_descuento'                                   => $regla ? true : false
                ]; */

                $data = [
                    'fecha_inicio'                          => $analisis_ausentismo_grupo_dos->fecha_inicio->format('Y-m-d'),
                    'fecha_termino'                         => $analisis_ausentismo_grupo_dos->fecha_termino->format('Y-m-d'),
                    'fecha_inicio_periodo'                  => $analisis_ausentismo_grupo_dos->fecha_inicio_periodo->format('Y-m-d'),
                    'fecha_termino_periodo'                 => $analisis_ausentismo_grupo_dos->fecha_termino_periodo->format('Y-m-d'),
                    'total_dias_ausentismo'                 => $analisis_ausentismo_grupo_dos->total_dias_ausentismo,

                    'total_dias_ausentismo_periodo'                 => $analisis_ausentismo_grupo_dos->total_dias_ausentismo_periodo,
                    'total_dias_habiles_ausentismo_periodo'         => $analisis_ausentismo_grupo_dos->total_dias_habiles_ausentismo_periodo,
                    'total_dias_ausentismo_periodo_turno'           => $analisis_ausentismo_grupo_dos->total_dias_ausentismo_periodo_turno,
                    'total_dias_habiles_ausentismo_periodo_turno'   => $analisis_ausentismo_grupo_dos->total_dias_habiles_ausentismo_periodo_turno,

                    'user_id'                               => $funcionario->id,
                    'tipo_ausentismo_id'                    => $tipo_ausentismo->id,
                    'regla_id'                              => $analisis_ausentismo_grupo_dos->regla ? $analisis_ausentismo_grupo_dos->regla->id : NULL,
                    'grupo_id'                              => 2,
                    'recarga_id'                            => $this->recarga->id,
                    'esquema_id'                            => $esquema ? $esquema->id : NULL,
                    'meridiano_id'                          => $meridiano->id,
                    'descuento_turno_libre'                 => $analisis_ausentismo_grupo_dos->descuento_en_turnos,
                    'tiene_descuento'                       => $analisis_ausentismo_grupo_dos->descuento_value
                ];

                $ausentismo = Ausentismo::create($data);
                if ($ausentismo) {
                    $cartola_controller = new ActualizarEsquemaController;
                    $cartola_controller->updateAusentismosGrupoDos($funcionario, $this->recarga, 2);
                    $this->importados++;
                    return $ausentismo;
                }
            }
        } catch (\Exception $error) {
            Log::info($error->getMessage());
        }
    }

    public function totalDiasEnPeriodo($fecha_inicio, $fecha_termino, $dias = 0, $dias_periodo = 0)
    {
        try {
            $new_fecha_inicio   = Carbon::parse($fecha_inicio);
            $new_fecha_termino  = Carbon::parse($fecha_termino);

            $new_fecha_inicio = $new_fecha_inicio->format('Y-m-d');
            $new_fecha_termino = $new_fecha_termino->format('Y-m-d');

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
                    $dias_periodo       = $inicio->diffInDays($termino) + 1;
                    break;

                case (($new_fecha_inicio >= $fecha_recarga_inicio) && ($new_fecha_termino > $fecha_recarga_termino)):
                    $inicio             = Carbon::parse($new_fecha_inicio);
                    $termino            = Carbon::parse($fecha_recarga_termino);
                    $dias_periodo       = $inicio->diffInDays($termino) + 1;
                    break;

                case (($new_fecha_inicio < $fecha_recarga_inicio) && ($new_fecha_termino <= $fecha_recarga_termino)):
                    $inicio             = Carbon::parse($fecha_recarga_inicio);
                    $termino            = Carbon::parse($new_fecha_termino);
                    $dias_periodo       = $inicio->diffInDays($termino) + 1;
                    break;

                case (($new_fecha_inicio < $fecha_recarga_inicio) && ($new_fecha_termino > $fecha_recarga_termino)):
                    $inicio             = Carbon::parse($fecha_recarga_inicio);
                    $termino            = Carbon::parse($fecha_recarga_termino);
                    $dias_periodo       = $inicio->diffInDays($termino) + 1;
                    break;

                default:
                    $dias_periodo = 'error';
                    break;
            }
            $ini        = Carbon::parse($fecha_inicio);
            $ter        = Carbon::parse($fecha_termino);
            $dias       = $ini->diffInDays($ter) + 1;

            return array($dias, $new_fecha_inicio, $new_fecha_termino, $dias_periodo, $inicio, $termino);
        } catch (\Exception $error) {
            return $error->getMessage();
        }
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

    public function validateExistMeridianoInRegla($rut, $nombre_tipo_ausentismo, $meridiano_row)
    {
        $existe  = true;
        $message = null;
        $regla   = null;

        $tipo_ausentismo    = TipoAusentismo::where('nombre', $nombre_tipo_ausentismo)->first();
        $meridiano          = Meridiano::where('codigo', $meridiano_row)->orWhere('nombre', $meridiano_row)->first();
        $funcionario        = User::where('rut', $rut)->with('turnos', 'asistencias', 'contratos')->first();

        if ($funcionario) {
            $turnante           = $this->esTurnante($funcionario);
            $turnante_message   = $turnante ? 'TURNANTE' : 'NO TURNANTE';
            if ($meridiano) {
                $regla = Regla::where('tipo_ausentismo_id', $tipo_ausentismo->id)
                    ->where('recarga_id', $this->recarga->id)
                    ->where('turno_funcionario', $turnante)
                    ->whereHas('meridianos', function ($query) use ($meridiano) {
                        $query->where('meridiano_regla.meridiano_id', $meridiano->id);
                    })
                    ->first();
                if (!$regla) {
                    $existe     = false;
                    $message    = "Meridiano {$meridiano->nombre} no existe en regla de {$turnante_message}";
                }
            }
        }

        return array($existe, $message);
    }

    public function existTipoAusentismoInGrupo($nombre_tipo_ausentismo)
    {
        $exist = false;

        $tipo_ausentismo = TipoAusentismo::where('nombre', $nombre_tipo_ausentismo)->first();

        if ($tipo_ausentismo) {
            $regla = Regla::where('tipo_ausentismo_id', $tipo_ausentismo->id)->where('grupo_id', 2)->where('recarga_id', $this->recarga->id)->first();

            if ($regla) {
                $exist = true;
            }
        }
        return $exist;
    }

    public function validateFechasAusentismos($rut_completo, $tipo_ausentismo, $fecha_inicio, $fecha_termino, $meridiano_row)
    {
        $tiene                  = false;
        $newformat_fecha_ini    = Carbon::parse($fecha_inicio)->format('Y-m-d');
        $newformat_fecha_fin    = Carbon::parse($fecha_termino)->format('Y-m-d');

        $funcionario            = User::where('rut_completo', $rut_completo)->first();
        $tipo_ausentismo        = TipoAusentismo::where('nombre', $tipo_ausentismo)->first();
        $meridiano              = Meridiano::where('codigo', $meridiano_row)->orWhere('nombre', $meridiano_row)->first();

        if ($funcionario && $tipo_ausentismo && $meridiano) {
            $validacion_1 = Ausentismo::where('recarga_id', $this->recarga->id)
                ->where('user_id', $funcionario->id)
                ->where('tipo_ausentismo_id', $tipo_ausentismo->id)
                ->where('fecha_inicio', '<=', $newformat_fecha_ini)
                ->where('fecha_termino', '>=', $newformat_fecha_ini)
                ->where('meridiano_id', $meridiano->id)
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
                ->where('fecha_inicio', '<=', $newformat_fecha_fin)
                ->where('fecha_termino', '>=', $newformat_fecha_fin)
                ->where('meridiano_id', $meridiano->id)
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
                ->where('fecha_inicio', '>=', $newformat_fecha_ini)
                ->where('fecha_termino', '<=', $newformat_fecha_fin)
                ->where('meridiano_id', $meridiano->id)
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

    public function validateDuplicadoAusentismos($rut_completo, $tipo_ausentismo, $fecha_inicio, $fecha_termino, $nombre_meridiano)
    {
        $tiene                  = false;
        $newformat_fecha_ini    = Carbon::parse($fecha_inicio)->format('Y-m-d');
        $newformat_fecha_fin    = Carbon::parse($fecha_termino)->format('Y-m-d');

        $funcionario        = User::where('rut_completo', $rut_completo)->first();
        $tipo_ausentismo    = TipoAusentismo::where('nombre', $tipo_ausentismo)->first();
        $meridiano          = Meridiano::where('codigo', $nombre_meridiano)->orWhere('nombre', $nombre_meridiano)->first();

        if ($funcionario && $tipo_ausentismo && $meridiano) {
            $validacion = Ausentismo::where('recarga_id', $this->recarga->id)
                ->where('user_id', $funcionario->id)
                ->where('tipo_ausentismo_id', $tipo_ausentismo->id)
                ->where('fecha_inicio', '=', $newformat_fecha_ini)
                ->where('fecha_termino', '=', $newformat_fecha_fin)
                ->where('meridiano_id', '=', $meridiano->id)
                ->where('grupo_id', 2)
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

    public function returnKeyFile($data)
    {
        $new_key                = "{$data[$this->rut]}_{$data[$this->fecha_inicio]}_{$data[$this->fecha_termino]}_{$data[$this->nombre_tipo_ausentismo]}_{$data[$this->total_ausentismo]}_{$data[$this->meridiano]}";
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
                $fecha_inicio_real      = $calculo[4] ? Carbon::parse($calculo[4])->format('Y-m-d') : null;
                $fecha_termino_real     = $calculo[5] ? Carbon::parse($calculo[5])->format('Y-m-d') : null;

                $validate               = $this->validateRut($rut);
                $periodo_in_recarga     = $this->periodoInRecarga($fecha_inicio_real, $fecha_termino_real);
                $exist_regla            = $this->validateExistMeridianoInRegla($rut, $data[$this->nombre_tipo_ausentismo], $data[$this->meridiano]);
                $exist_tipo_ausentismo  = $this->existTipoAusentismoInGrupo($data[$this->nombre_tipo_ausentismo]);
                $fechas                 = $this->validateFechasAusentismos($rut, $data[$this->nombre_tipo_ausentismo], $fecha_inicio, $fecha_termino, $data[$this->meridiano]);
                $duplicado              = $this->validateDuplicadoAusentismos($rut, $data[$this->nombre_tipo_ausentismo], $fecha_inicio, $fecha_termino, $data[$this->meridiano]);

                if (!$validate) {
                    $validator->errors()->add($key, 'Rut incorrecto, por favor verificar. Verificado con Módulo 11.');
                } else if (!$periodo_in_recarga) {
                    $validator->errors()->add($key, "Fechas fuera de periodo de recarga.");
                } else if (!$exist_regla[0]) {
                    $validator->errors()->add($key, $exist_regla[1]);
                } else if (in_array($new_key, $assoc_array)) {
                    $validator->errors()->add($key, 'Registro duplicado en archivo.');
                } else if (!$exist_tipo_ausentismo) {
                    $validator->errors()->add($key, 'Tipo de ausentismo no existe en grupo de reglas seleccionado.');
                } else if ($fechas) {
                    $validator->errors()->add($key, 'Ya existe un ausentismo en las fechas de registro.');
                } else if ($duplicado) {
                    $validator->errors()->add($key, 'Registro duplicado en sistema.');
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
            $this->total_ausentismo => [
                'nullable',
                'numeric'
            ],
            $this->meridiano => [
                'required',
                'exists:meridianos,codigo'
            ],
        ];
    }

    public function customValidationMessages()
    {
        return [
            "{$this->rut}.required"                                         => 'El rut es obligatorio.',
            "{$this->rut}.numeric"                                          => 'El rut debe ser un valor numérico.',
            "{$this->rut}.exists"                                           => 'El rut no existe en el sistema',

            "{$this->dv}.required"                                          => 'El dv es obligatorio.',
            "{$this->dv}.min"                                               => 'El dv tiene :min caracter mínimo',
            "{$this->dv}.max"                                               => 'El dv tiene :max caracter máximo',

            "{$this->nombre_tipo_ausentismo}.required"                      => 'El nombre de ausentismo obligatorio.',

            "{$this->fecha_inicio}.required"                                => 'La fecha de inicio es obligatoria.',
            "{$this->fecha_inicio}.date"                                    => 'La fecha debe ser yyyy-mm-dd.',

            "{$this->fecha_termino}.required"                               => 'La fecha de término es obligatoria.',
            "{$this->fecha_termino}.fecha_termino"                          => 'La fecha debe ser yyyy-mm-dd.',

            "{$this->total_ausentismo}.required"                            => 'El total ausentismo de ausentismo obligatorio.',
            "{$this->total_ausentismo}.integer"                             => 'El total ausentismo debe ser numérico.',

            "{$this->meridiano}.required"                                   => 'El meridiano es obligatorio.',
            "{$this->meridiano}.exists"                                     => 'El meridiano no existe en el sistema',
        ];
    }
}
