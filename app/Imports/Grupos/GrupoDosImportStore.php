<?php

namespace App\Imports\Grupos;

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

    public $importados = 0;

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

    public function isDecimal($n) {
        // Note that floor returns a float
        return is_numeric($n) && floor($n) != $n;
    }


    public function model(array $row)
    {
        $rut                = "{$row[$this->rut]}-{$row[$this->dv]}";
        $funcionario        = User::where('rut', $rut)->first();
        $tipo_ausentismo    = TipoAusentismo::where('nombre', $row[$this->nombre_tipo_ausentismo])->first();
        $meridiano          = Meridiano::where('codigo', $row[$this->meridiano])->first();

        if ($funcionario && $tipo_ausentismo && $meridiano) {
            $regla                  = Regla::where('tipo_ausentismo_id', $tipo_ausentismo->id)->where('recarga_id', $this->recarga->id)->first();
            $fecha_inicio           = Carbon::parse($this->transformDate($row[strtolower($this->fecha_inicio)]));
            $fecha_termino          = Carbon::parse($this->transformDate($row[strtolower($this->fecha_termino)]));
            $total_dias_ausentismo  = $row[$this->total_ausentismo];

            $is_decimal = $this->isDecimal($total_dias_ausentismo);

            if ($is_decimal) {
                $total_dias_ausentismo = number_format($total_dias_ausentismo, 2, '.', '');
            }


            $data = [
                'fecha_inicio'                  => $fecha_inicio->format('Y-m-d'),
                'fecha_termino'                 => $fecha_termino->format('Y-m-d'),
                'fecha_inicio_periodo'          => $fecha_inicio->format('Y-m-d'),
                'fecha_termino_periodo'         => $fecha_termino->format('Y-m-d'),
                'total_dias_ausentismo'         => $total_dias_ausentismo,
                'total_dias_ausentismo_periodo' => $total_dias_ausentismo,
                'user_id'                       => $funcionario->id,
                'tipo_ausentismo_id'            => $tipo_ausentismo->id,
                'regla_id'                      => $regla->id,
                'grupo_id'                      => $regla->grupoAusentismo->id,
                'establecimiento_id'            => $this->recarga->establecimiento->id,
                'unidad_id'                     => $funcionario->unidad->id,
                'planta_id'                     => $funcionario->planta->id,
                'cargo_id'                      => $funcionario->cargo->id,
                'recarga_id'                    => $this->recarga->id,
                'meridiano_id'                  => $meridiano->id
            ];

            $ausentismo = Ausentismo::create($data);

            if ($ausentismo) {
                $this->importados++;
                return $ausentismo;
            }
        }
    }

    public function esTurnante($funcionario)
    {
        $turnante = false;

        $turno      = $funcionario->turnos()->where('recarga_id', $this->recarga->id)->where('es_turnante', true)->first();
        $asistencia = $funcionario->asistencias()->where('recarga_id', $this->recarga->id)->first();

        if (($turno) && ($turno->es_turnante && $asistencia)) {
            $turnante = true;
        } else if ($asistencia && !$turno) {
            $turnante = true;
        } else if ($turno && !$asistencia) {
            $turnante = null;
        }

        return $turnante;
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
        $meridiano          = Meridiano::where('codigo', $meridiano_row)->first();
        $funcionario        = User::where('rut', $rut)->first();

        $turnante           = $this->esTurnante($funcionario);
        $turnante_message   = $turnante ? 'TURNANTE' : 'NO TURNANTE';
        if ($meridiano) {
            $regla = Regla::where('tipo_ausentismo_id', $tipo_ausentismo->id)
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
        return array($existe, $message);
    }

    public function existTipoAusentismoInGrupo($nombre_tipo_ausentismo)
    {
        $exist = false;

        $tipo_ausentismo = TipoAusentismo::where('nombre', $nombre_tipo_ausentismo)->first();

        if ($tipo_ausentismo) {
            $regla = Regla::where('tipo_ausentismo_id', $tipo_ausentismo->id)->where('grupo_id', 2)->first();

            if ($regla) {
                $exist = true;
            }
        }
        return $exist;
    }

    public function validateFechasAusentismos($rut_completo, $tipo_ausentismo, $fecha_inicio, $fecha_termino)
    {
        $tiene                  = false;
        $newformat_fecha_ini    = Carbon::parse($fecha_inicio)->format('Y-m-d');
        $newformat_fecha_fin    = Carbon::parse($fecha_termino)->format('Y-m-d');

        $funcionario            = User::where('rut_completo', $rut_completo)->first();
        $tipo_ausentismo        = TipoAusentismo::where('nombre', $tipo_ausentismo)->first();

        if ($funcionario && $tipo_ausentismo) {
            $validacion_1 = Ausentismo::where('recarga_id', $this->recarga->id)
                ->where('user_id', $funcionario->id)
                /* ->where('tipo_ausentismo_id', $tipo_ausentismo->id) */
                ->where('fecha_inicio', '<=', $newformat_fecha_ini)
                ->where('fecha_termino', '>=', $newformat_fecha_ini)
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
                /* ->where('tipo_ausentismo_id', $tipo_ausentismo->id) */
                ->where('fecha_inicio', '<=', $newformat_fecha_fin)
                ->where('fecha_termino', '>=', $newformat_fecha_fin)
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
                /* ->where('tipo_ausentismo_id', $tipo_ausentismo->id) */
                ->where('fecha_inicio', '>=', $newformat_fecha_ini)
                ->where('fecha_termino', '<=', $newformat_fecha_fin)
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
        $meridiano          = Meridiano::where('codigo', $nombre_meridiano)->first();

        if ($funcionario && $tipo_ausentismo && $meridiano) {
            $validacion = Ausentismo::where('recarga_id', $this->recarga->id)
                ->where('user_id', $funcionario->id)
                ->where('tipo_ausentismo_id', $tipo_ausentismo->id)
                ->where('fecha_inicio', '=', $newformat_fecha_ini)
                ->where('fecha_termino', '=', $newformat_fecha_ini)
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

    public function withValidator($validator)
    {
        $assoc_array = array();

        $validator->after(function ($validator) use ($assoc_array) {
            foreach ($validator->getData() as $key => $data) {
                $new_key                = "{$data[$this->rut]}_{$data[$this->fecha_inicio]}_{$data[$this->fecha_termino]}_{$data[$this->nombre_tipo_ausentismo]}_{$data[$this->total_ausentismo]}_{$data[$this->meridiano]}";
                $rut                    = "{$data[$this->rut]}-{$data[$this->dv]}";
                $fecha_inicio           = Carbon::parse($this->transformDate($data[$this->fecha_inicio]));
                $fecha_termino          = Carbon::parse($this->transformDate($data[$this->fecha_termino]));

                $validate               = $this->validateRut($rut);
                $exist_regla            = $this->validateExistMeridianoInRegla($rut, $data[$this->nombre_tipo_ausentismo], $data[$this->meridiano]);
                $exist_tipo_ausentismo  = $this->existTipoAusentismoInGrupo($data[$this->nombre_tipo_ausentismo]);
                $fechas                 = $this->validateFechasAusentismos($rut, $data[$this->nombre_tipo_ausentismo], $fecha_inicio, $fecha_termino);
                $duplicado              = $this->validateDuplicadoAusentismos($rut, $data[$this->nombre_tipo_ausentismo], $fecha_inicio, $fecha_termino, $data[$this->meridiano]);

                if (!$validate) {
                    $validator->errors()->add($key, 'Rut incorrecto, por favor verificar. Verificado con Módulo 11.');
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
                'min:1',
                'max:1'
            ],
            $this->nombre_tipo_ausentismo => [
                'exists:tipo_ausentismos,nombre'
            ],
            $this->fecha_inicio => [
                'required',
                new FechaRecarga(true, $this->fecha_inicio, $this->recarga)
            ],
            $this->fecha_termino => [
                'required',
                new FechaRecarga(false, $this->fecha_termino, $this->recarga)
            ],
            $this->total_ausentismo => [
                'required',
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

            "{$this->total_ausentismo}.required"                            => 'El total ausentismo de ausentismo obligatorio.',
            "{$this->total_ausentismo}.integer"                             => 'El total ausentismo debe ser numérico.',

            "{$this->meridiano}.required"                                   => 'El meridiano es obligatorio.',
            "{$this->meridiano}.exists"                                     => 'El meridiano no existe en el sistema',
        ];
    }
}
