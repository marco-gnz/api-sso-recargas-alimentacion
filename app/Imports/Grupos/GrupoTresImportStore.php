<?php

namespace App\Imports\Grupos;

use App\Models\Ausentismo;
use App\Models\Regla;
use App\Models\TipoAusentismo;
use App\Models\User;
use App\Rules\FechaRecarga;
use Carbon\Carbon;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithValidation;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class GrupoTresImportStore implements ToModel, WithHeadingRow, WithValidation
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

    public function transformTime($value, $format = 'H:i:s')
    {
        try {
            return Carbon::instance(\PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($value));
        } catch (\ErrorException $e) {
            return Carbon::createFromFormat($format, $value);
        }
    }

    public function model(array $row)
    {
        $rut                = "{$row[$this->rut]}-{$row[$this->dv]}";
        $funcionario        = User::where('rut', $rut)->first();
        $tipo_ausentismo    = TipoAusentismo::where('nombre', $row[$this->nombre_tipo_ausentismo])->first();

        if ($funcionario && $tipo_ausentismo) {
            $turnante       = $this->esTurnante($funcionario);
            $regla          = Regla::where('tipo_ausentismo_id', $tipo_ausentismo->id)->where('turno_funcionario', $turnante)->first();
            $fecha_inicio   = Carbon::parse($this->transformDate($row[$this->fecha_inicio]));
            $fecha_termino  = Carbon::parse($this->transformDate($row[$this->fecha_termino]));
            $hora_inicio    = Carbon::parse($this->transformTime($row[$this->hora_inicio]));
            $hora_termino   = Carbon::parse($this->transformTime($row[$this->hora_termino]));
            $diff_hours     = $hora_inicio->floatDiffInHours($hora_termino);

            $hora_inicio_regla    = Carbon::parse($regla->hora_inicio);
            $hora_termino_regla   = Carbon::parse($regla->hora_termino);
            $concat_inicio        = "{$fecha_inicio->format('Y-m-d')} {$hora_inicio->format('H:i:s')}";
            $concat_termino       = "{$fecha_termino->format('Y-m-d')} {$hora_termino->format('H:i:s')}";
            $concat_inicio_regla  = "{$fecha_inicio->format('Y-m-d')} {$hora_inicio_regla->format('H:i:s')}";
            $concat_termino_regla = "{$fecha_termino->format('Y-m-d')} {$hora_termino_regla->format('H:i:s')}";
            $tiene_descuento      = $this->tieneDescuento($regla, $concat_inicio, $concat_termino, $concat_inicio_regla, $concat_termino_regla);

            $fecha_inicio         = $fecha_inicio->format('Y-m-d');
            $fecha_termino        = $fecha_termino->format('Y-m-d');

            $fecha_termino_new    = Carbon::parse($fecha_inicio);
            $fecha_termino_new    = Carbon::parse($fecha_termino);
            $diff_days            = $fecha_termino_new->diffInDays($fecha_termino_new) + 1;

            $data = [
                'fecha_inicio'                  => $fecha_inicio,
                'fecha_termino'                 => $fecha_termino,
                'fecha_inicio_periodo'          => $fecha_inicio,
                'fecha_termino_periodo'         => $fecha_termino,
                'hora_inicio'                   => $hora_inicio->format('H:i:s'),
                'hora_termino'                  => $hora_termino->format('H:i:s'),
                'total_horas_ausentismo'        => $diff_hours,
                'total_dias_ausentismo'         => $diff_days,
                'total_dias_ausentismo_periodo' => $diff_days,
                'tiene_descuento'               => $tiene_descuento,
                'user_id'                       => $funcionario->id,
                'tipo_ausentismo_id'            => $tipo_ausentismo->id,
                'regla_id'                      => $regla->id,
                'grupo_id'                      => $regla->grupoAusentismo->id,
                'establecimiento_id'            => $this->recarga->establecimiento->id,
                'unidad_id'                     => $funcionario->unidad->id,
                'planta_id'                     => $funcionario->planta->id,
                'cargo_id'                      => $funcionario->cargo->id,
                'recarga_id'                    => $this->recarga->id
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

    public function tieneDescuento($regla, $hora_inicio_archivo_concat, $hora_termino_archivo_concat, $concat_inicio_regla, $concat_termino_regla)
    {
        $tiene_descuento = false;

        $hora_inicio_archivo   = Carbon::parse($hora_inicio_archivo_concat)->format('H:i:s');
        $hora_termino_archivo  = Carbon::parse($hora_termino_archivo_concat)->format('H:i:s');
        $fecha_inicio_regla    = Carbon::parse($concat_inicio_regla)->format('H:i:s');
        $fecha_termino_regla   = Carbon::parse($concat_termino_regla)->format('H:i:s');

        if ($hora_inicio_archivo >= $fecha_inicio_regla && $hora_termino_archivo <= $fecha_termino_regla) {
            $tiene_descuento = true;
        } else if ($hora_inicio_archivo <= $fecha_inicio_regla && $hora_termino_archivo >= $fecha_termino_regla) {
            $tiene_descuento = true;
        } else if ($hora_inicio_archivo <= $fecha_inicio_regla && $hora_termino_archivo >= $fecha_inicio_regla) {
            $tiene_descuento = true;
        } else if (($hora_inicio_archivo < $fecha_termino_regla) && $hora_inicio_archivo >= $fecha_inicio_regla && $hora_termino_archivo >= $fecha_inicio_regla) {
            $tiene_descuento = true;
        }
        return $tiene_descuento;
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

    public function existTipoAusentismoInGrupo($nombre_tipo_ausentismo)
    {
        $exist = false;

        $tipo_ausentismo = TipoAusentismo::where('nombre', $nombre_tipo_ausentismo)->first();

        if ($tipo_ausentismo) {
            $regla = Regla::where('tipo_ausentismo_id', $tipo_ausentismo->id)->where('grupo_id', 3)->first();

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

    public function withValidator($validator)
    {
        $assoc_array = array();

        $validator->after(function ($validator) use ($assoc_array) {
            foreach ($validator->getData() as $key => $data) {
                $new_key                = "{$data[$this->rut]}_{$data[$this->fecha_inicio]}_{$data[$this->fecha_termino]}_{$data[$this->nombre_tipo_ausentismo]}_{$data[$this->hora_inicio]}_{$data[$this->hora_termino]}";
                $rut                    = "{$data[$this->rut]}-{$data[$this->dv]}";
                $fecha_inicio           = Carbon::parse($this->transformDate($data[$this->fecha_inicio]));
                $fecha_termino          = Carbon::parse($this->transformDate($data[$this->fecha_termino]));
                $hora_inicio            = Carbon::parse($this->transformTime($data[$this->hora_inicio]));
                $hora_termino           = Carbon::parse($this->transformTime($data[$this->hora_termino]));

                $validate               = $this->validateRut($rut);
                $exist_tipo_ausentismo  = $this->existTipoAusentismoInGrupo($data[$this->nombre_tipo_ausentismo]);
                $fechas                 = $this->validateFechasAusentismos($rut, $data[$this->nombre_tipo_ausentismo], $fecha_inicio, $fecha_termino, $hora_inicio, $hora_termino);
                $duplicado              = $this->validateDuplicadoAusentismos($rut, $data[$this->nombre_tipo_ausentismo], $fecha_inicio, $fecha_termino, $hora_inicio, $hora_termino);

                if (!$validate) {
                    $validator->errors()->add($key, 'Rut incorrecto, por favor verificar. Verificado con Módulo 11.');
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
