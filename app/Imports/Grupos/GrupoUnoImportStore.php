<?php

namespace App\Imports\Grupos;

use App\Models\Ausentismo;
use App\Models\Regla;
use App\Models\TipoAusentismo;
use App\Models\User;
use App\Rules\FechaRecarga;
use App\Rules\RutValidateRule;
use Carbon\Carbon;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithValidation;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use App\Http\Controllers\Admin\Calculos\ActualizarEsquemaController;
use App\Http\Controllers\Admin\Esquema\EsquemaController;
use App\Http\Controllers\Admin\Calculos\AnalisisRegistroController;
use Illuminate\Support\Facades\Log;

class GrupoUnoImportStore implements ToModel, WithHeadingRow, WithValidation
{
    public function  __construct($recarga, $columnas, $row_columnas)
    {
        $this->recarga                  = $recarga;
        $this->columnas                 = $columnas;
        $this->row_columnas             = $row_columnas;

        $this->rut                      = strtolower($this->columnas[0]);
        $this->dv                       = strtolower($this->columnas[1]);
        $this->nombre_tipo_ausentismo   = strtolower($this->columnas[2]);
        $this->fecha_inicio             = strtolower($this->columnas[3]);
        $this->fecha_termino            = strtolower($this->columnas[4]);
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

    public function existTipoAusentismoInGrupo($tipo_ausentismo)
    {
        $exist = false;
        if ($tipo_ausentismo) {
            $querys = $this->recarga->reglas()->where('tipo_ausentismo_id', $tipo_ausentismo->id)->where('grupo_id', 1)->count();

            if ($querys > 0) {
                $exist = true;
            }
        }

        return $exist;
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

    public function model(array $row)
    {
        $rut                    = "{$row[strtolower($this->rut)]}-{$row[strtolower($this->dv)]}";
        $funcionario            = User::where('rut', $rut)->first();

        $nom_tipo_ausentismo    = ltrim($row[$this->nombre_tipo_ausentismo]);
        $tipo_ausentismo        = TipoAusentismo::where('codigo_sirh', $nom_tipo_ausentismo)->orWhere('nombre', $nom_tipo_ausentismo)->first();

        if ($funcionario && $tipo_ausentismo) {
            $esquema_controller     = new EsquemaController;
            $esquema                = $esquema_controller->returnEsquema($funcionario->id, $this->recarga->id);
            $exist_tipo_ausentismo  = $this->existTipoAusentismoInGrupo($tipo_ausentismo);
            $turnante               = $esquema ? ($esquema->es_turnante != 2 ? true : false) : false;

            $regla                  = $this->recarga->reglas()
                ->where('grupo_id', 1)
                ->where('tipo_ausentismo_id', $tipo_ausentismo->id)
                ->first();

            $fecha_inicio           = Carbon::parse($this->transformDate($row[strtolower($this->fecha_inicio)]));
            $fecha_termino          = Carbon::parse($this->transformDate($row[strtolower($this->fecha_termino)]));

            $analisis_registro_controller       = new AnalisisRegistroController;
            $analisis_ausentismo_grupo_uno      = $analisis_registro_controller->analisisAusentismoGrupoUno($turnante, $this->recarga, $funcionario, $fecha_inicio, $fecha_termino);

            if ($exist_tipo_ausentismo) {
                $fecha_inicio_real      = Carbon::parse($analisis_ausentismo_grupo_uno->fecha_inicio)->format('Y-m-d');
                $fecha_termino_real     = Carbon::parse($analisis_ausentismo_grupo_uno->fecha_termino)->format('Y-m-d');

                $compensatorio_dias  = $this->validateFechaDiasCompensatoriosAndCompensacionTiempoLibre($rut, $tipo_ausentismo->nombre, $fecha_inicio, $fecha_termino);
                $fechas              = $this->validateFechasAusentismos($rut, $tipo_ausentismo->nombre, $fecha_inicio_real, $fecha_termino_real);


                if (!$compensatorio_dias && $regla && !$fechas->value) {
                    $data = [
                        'fecha_inicio'                          => $analisis_ausentismo_grupo_uno->fecha_inicio->format('Y-m-d'),
                        'fecha_termino'                         => $analisis_ausentismo_grupo_uno->fecha_termino->format('Y-m-d'),
                        'fecha_inicio_periodo'                  => $analisis_ausentismo_grupo_uno->fecha_inicio_periodo->format('Y-m-d'),
                        'fecha_termino_periodo'                 => $analisis_ausentismo_grupo_uno->fecha_termino_periodo->format('Y-m-d'),
                        'total_dias_ausentismo'                 => $analisis_ausentismo_grupo_uno->total_dias_ausentismo,

                        'total_dias_ausentismo_periodo'                 => $analisis_ausentismo_grupo_uno->total_dias_ausentismo_periodo,
                        'total_dias_habiles_ausentismo_periodo'         => $analisis_ausentismo_grupo_uno->total_dias_habiles_ausentismo_periodo,
                        'total_dias_ausentismo_periodo_turno'           => $analisis_ausentismo_grupo_uno->total_dias_ausentismo_periodo_turno,
                        'total_dias_habiles_ausentismo_periodo_turno'   => $analisis_ausentismo_grupo_uno->total_dias_habiles_ausentismo_periodo_turno,

                        'user_id'                               => $funcionario->id,
                        'tipo_ausentismo_id'                    => $tipo_ausentismo->id,
                        'regla_id'                              => $regla->id,
                        'grupo_id'                              => 1,
                        'recarga_id'                            => $this->recarga->id,
                        'esquema_id'                            => $esquema ? $esquema->id : NULL,
                        'tiene_descuento'                       => true,
                        'descuento_turno_libre'                 => $analisis_ausentismo_grupo_uno->descuento_en_turnos
                    ];

                    $ausentismo = Ausentismo::create($data);

                    if ($ausentismo) {
                        $cartola_controller = new ActualizarEsquemaController;
                        $cartola_controller->updateAusentismosGrupoUno($funcionario, $this->recarga, 1);
                        $this->importados++;
                        return $ausentismo;
                    }
                }
            }
        }
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

    public function validateFechaDiasCompensatoriosAndCompensacionTiempoLibre($rut_completo, $tipo_ausentismo, $fecha_inicio, $fecha_termino)
    {
        $existe = false;

        $newformat_fecha_ini    = Carbon::parse($fecha_inicio)->format('Y-m-d');
        $newformat_fecha_fin    = Carbon::parse($fecha_termino)->format('Y-m-d');

        $funcionario            = User::where('rut_completo', $rut_completo)->first();
        $tipo_ausentismo        = ltrim($tipo_ausentismo);
        $tipo_ausentismo        = TipoAusentismo::where('nombre', $tipo_ausentismo)->first();

        if (($funcionario && $tipo_ausentismo) && ($tipo_ausentismo->id === 3)) {
            $compensacion_tiempo_libre_id = 26;
            $validacion = Ausentismo::where('recarga_id', $this->recarga->id)
                ->where('user_id', $funcionario->id)
                ->where('tipo_ausentismo_id', $compensacion_tiempo_libre_id)
                ->where('fecha_inicio_periodo', '>=', $newformat_fecha_ini)
                ->where('fecha_termino_periodo', '<=', $newformat_fecha_fin)
                ->where(function ($query) {
                    $query->whereHas('recarga', function ($query) {
                        $query->where('active', true);
                    });
                })->count();

            if ($validacion > 0) {
                $existe = true;
            }
        }
        return $existe;
    }

    public function validateFechasAusentismos($rut_completo, $tipo_ausentismo, $fecha_inicio, $fecha_termino)
    {
        $newformat_fecha_ini = Carbon::parse($fecha_inicio)->format('Y-m-d');
        $newformat_fecha_fin = Carbon::parse($fecha_termino)->format('Y-m-d');

        $funcionario = User::where('rut_completo', $rut_completo)->first();
        $tipo_ausentismo = ltrim($tipo_ausentismo);
        $tipo_ausentismo = TipoAusentismo::where('nombre', $tipo_ausentismo)->first();
        $compensacion_tiempo_libre_id = 26;

        if ($funcionario && $tipo_ausentismo) {
            $query = $this->recarga->ausentismos()->where('recarga_id', $this->recarga->id)
                ->where('user_id', $funcionario->id)
                ->where('tipo_ausentismo_id', '!=', $compensacion_tiempo_libre_id)
                ->where(function ($query) use ($newformat_fecha_ini, $newformat_fecha_fin) {
                    $query->where(function ($query) use ($newformat_fecha_ini, $newformat_fecha_fin) {
                        $query->where('fecha_inicio_periodo', '<=', $newformat_fecha_ini)
                            ->where('fecha_termino_periodo', '>=', $newformat_fecha_ini);
                    })->orWhere(function ($query) use ($newformat_fecha_ini, $newformat_fecha_fin) {
                        $query->where('fecha_inicio_periodo', '<=', $newformat_fecha_fin)
                            ->where('fecha_termino_periodo', '>=', $newformat_fecha_fin);
                    })->orWhere(function ($query) use ($newformat_fecha_ini, $newformat_fecha_fin) {
                        $query->where('fecha_inicio_periodo', '>=', $newformat_fecha_ini)
                            ->where('fecha_termino_periodo', '<=', $newformat_fecha_fin);
                    });
                });

            $tiene = $query->whereHas('recarga', function ($query) {
                $query->where('active', true);
            })->first();

            if ($tiene) {
                Log::info($tiene);
                return (object) [
                    'ausentismo'       => $tiene,
                    'value'         => $tiene ? true : false
                ];
            }
        }

        return (object) [
            'ausentismo'       => null,
            'value'         => false
        ];
    }

    public function validateDuplicadoAusentismos($rut_completo, $tipo_ausentismo, $fecha_inicio, $fecha_termino)
    {
        $tiene = false;
        $newformat_fecha_ini = Carbon::parse($fecha_inicio)->format('Y-m-d');
        $newformat_fecha_fin = Carbon::parse($fecha_termino)->format('Y-m-d');

        $funcionario        = User::where('rut_completo', $rut_completo)->first();
        $tipo_ausentismo    = ltrim($tipo_ausentismo);
        $tipo_ausentismo    = TipoAusentismo::where('nombre', $tipo_ausentismo)->first();

        if ($funcionario && $tipo_ausentismo) {
            $validacion = Ausentismo::where('recarga_id', $this->recarga->id)
                ->where('user_id', $funcionario->id)
                ->where('tipo_ausentismo_id', $tipo_ausentismo->id)
                ->where('fecha_inicio', '=', $newformat_fecha_ini)
                ->where('fecha_termino', '=', $newformat_fecha_ini)
                ->where('grupo_id', 1)
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
        $new_key = "{$data[$this->rut]}_{$data[$this->fecha_inicio]}_{$data[$this->fecha_termino]}_{$data[$this->nombre_tipo_ausentismo]}";
        return $new_key;
    }

    public function existFuncionarioInRecarga($rut)
    {
        $existe         = false;
        $funcionario    = User::where('rut_completo', $rut)->first();

        if ($funcionario) {
            $query_results = $this->recarga->whereHas('users', function ($query) use ($funcionario) {
                $query->where('recarga_user.user_id', $funcionario->id);
            })->count();

            if ($query_results > 0) {
                $existe = true;
            }
        }
        return $existe;
    }

    public function existTipoAusentismo($value)
    {
        $exist = false;

        $value = ltrim($value);
        $tipo_ausentismo = TipoAusentismo::where('codigo_sirh', $value)->orWhere('nombre', $value)->first();

        if ($tipo_ausentismo) {
            $exist = true;
        }

        return $exist;
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

                $periodo_in_recarga     = $this->periodoInRecarga($fecha_inicio_real, $fecha_termino_real);
                $validate               = $this->validateRut($rut);
                $exist_t_ausen          = $this->existTipoAusentismo($data[$this->nombre_tipo_ausentismo]);
                /* $fechas                 = $this->validateFechasAusentismos($rut, $data[$this->nombre_tipo_ausentismo], $fecha_inicio_real, $fecha_termino_real); */
                $duplicado              = $this->validateDuplicadoAusentismos($rut, $data[$this->nombre_tipo_ausentismo], $fecha_inicio, $fecha_termino);

                if (!$validate) {
                    $validator->errors()->add($key, 'Rut incorrecto, por favor verificar. Verificado con Módulo 11.');
                } else if (!$periodo_in_recarga) {
                    $validator->errors()->add($key, "Fechas fuera de periodo de recarga.");
                } else if (!$exist_t_ausen) {
                    $validator->errors()->add($key, 'Tipo de ausentismo no existe.');
                } /* else if ($fechas->value) {
                    $validator->errors()->add($key, "Ya existe un ausentismo en las fechas de registro.");
                }  */ else if ($duplicado) {
                    $validator->errors()->add($key, 'Registro duplicado.');
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
            ]
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
            "{$this->fecha_termino}.date"                                   => 'La fecha debe ser yyyy-mm-dd.',
        ];
    }
}
