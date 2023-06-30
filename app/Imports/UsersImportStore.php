<?php

namespace App\Imports;

use App\Http\Controllers\Admin\Calculos\ActualizarEsquemaController;
use App\Http\Controllers\Admin\Esquema\EsquemaController;
use App\Models\Cargo;
use App\Models\Esquema;
use App\Models\Establecimiento;
use App\Models\Hora;
use App\Models\Ley;
use App\Models\Planta;
use App\Models\Recarga;
use App\Models\RecargaContrato;
use App\Models\Unidad;
use App\Models\User;
use App\Rules\EstablecimientoIsRecarga;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithValidation;
use App\Rules\RutValidateRule;
use App\Rules\TipeValueDateContrato;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Illuminate\Support\Facades\Log;

class UsersImportStore implements ToModel, WithValidation, WithHeadingRow
{
    public function  __construct($recarga, $columnas, $row_columnas)
    {
        $this->recarga              = $recarga;
        $this->columnas             = $columnas;
        $this->row_columnas         = $row_columnas;

        $this->rut                  = strtolower($this->columnas[0]);
        $this->dv                   = strtolower($this->columnas[1]);
        $this->nombres              = strtolower($this->columnas[2]);
        $this->apellidos            = strtolower($this->columnas[3]);
        $this->email                = strtolower($this->columnas[4]);
        $this->cod_establecimietno  = strtolower($this->columnas[5]);
        $this->cod_unidad           = strtolower($this->columnas[6]);
        $this->nom_planta           = strtolower($this->columnas[7]);
        $this->cod_cargo            = strtolower($this->columnas[8]);
        $this->ley                  = strtolower($this->columnas[9]);
        $this->horas                = strtolower($this->columnas[10]);
        $this->fecha_inicio         = strtolower($this->columnas[11]);
        $this->fecha_termino        = strtolower($this->columnas[12]);
        $this->fecha_alejamiento    = strtolower($this->columnas[13]);
    }

    public $importados              = 0;
    public $editados                = 0;
    public $cargados_recarga        = 0;
    public $value_date_indefinido   = '00/00/0000';
    public $respuesta = null;

    public function uniqueBy()
    {
        return 'email';
    }

    public function headingRow(): int
    {
        return $this->row_columnas;
    }

    public function transformDate($value, $format = 'Y-m-d')
    {
        try {
            if ($value != $this->value_date_indefinido) {
                return Carbon::instance(\PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($value));
            }
        } catch (\ErrorException $e) {
            return Carbon::createFromFormat($format, $value);
        }
    }

    public function model(array $row)
    {
        try {
            $rut                = "{$row[strtolower($this->rut)]}-{$row[strtolower($this->dv)]}";
            $user               = User::where('rut', $rut)->first();
            $establecimiento    = Establecimiento::where('cod_sirh', $row[strtolower($this->cod_establecimietno)])->firstOrFail();
            $unidad             = Unidad::where('cod_sirh', $row[strtolower($this->cod_unidad)])->firstOrFail();
            $planta             = Planta::where('nombre', $row[strtolower($this->nom_planta)])->firstOrFail();
            $cargo              = Cargo::where('cod_sirh', $row[strtolower($this->cod_cargo)])->firstOrFail();
            $ley                = Ley::where('nombre', $row[strtolower($this->ley)])->orWhere('codigo', $row[strtolower($this->ley)])->firstOrFail();
            $horas              = Hora::where('nombre', $row[strtolower($this->horas)])->firstOrFail();

            $fecha_inicio       = Carbon::parse($this->transformDate($row[$this->fecha_inicio]));
            $fecha_termino      = $this->returnFechaTerminoContrato($row[$this->fecha_termino], $row[$this->fecha_alejamiento]);
            $calculo            = $this->totalDiasEnPeriodo($fecha_inicio, $fecha_termino->fecha);

            if ($user) {
                $data_user = [
                    'nombres'   => $row[strtolower($this->nombres)] !== $user->nombres ? $row[strtolower($this->nombres)] : $user->nombres,
                    'apellidos' => $row[strtolower($this->apellidos)] !== $user->apellidos ? $row[strtolower($this->apellidos)] : $user->apellidos,
                    'email'     => $row[strtolower($this->email)] !== null && $row[strtolower($this->email)] !== $user->email ? $row[strtolower($this->email)] : $user->email,
                ];

                $update = $user->update($data_user);


                if ($update) {
                    $this->editados++;
                }

                $esquema_controller = new EsquemaController;
                $esquema = $esquema_controller->returnEsquemaOrCreate($user->id, $this->recarga->id);

                $fecha_inicio_periodo   = $calculo[4] != null ? Carbon::parse($calculo[4])->format('Y-m-d') : null;
                $fecha_termino_periodo  = $calculo[5] != null ? Carbon::parse($calculo[5])->format('Y-m-d') : null;

                $data_contrato = [
                    'fecha_inicio'                  => $calculo[1] != null ? Carbon::parse($calculo[1])->format('Y-m-d') : NULL,
                    'fecha_termino'                 => $row[$this->fecha_termino] != $this->value_date_indefinido ? Carbon::parse($calculo[2])->format('Y-m-d') : NULL,
                    'fecha_inicio_periodo'          => $fecha_inicio_periodo,
                    'fecha_termino_periodo'         => $fecha_termino_periodo,
                    'total_dias_contrato'           => $row[$this->fecha_termino] != $this->value_date_indefinido ? $calculo[0] : NULL,
                    'total_dias_contrato_periodo'   => $calculo[3],
                    'alejamiento'                   => $fecha_termino->fecha_alejamiento,
                    'user_id'                       => $user->id,
                    'establecimiento_id'            => $establecimiento->id,
                    'unidad_id'                     => $unidad->id,
                    'planta_id'                     => $planta->id,
                    'cargo_id'                      => $cargo->id,
                    'ley_id'                        => $ley->id,
                    'hora_id'                       => $horas->id,
                    'recarga_id'                    => $this->recarga->id,
                ];

                $new_contrato = RecargaContrato::create($data_contrato);

                if ($new_contrato) {
                    $cartola_controller = new ActualizarEsquemaController;
                    $cartola_controller->storeEsquema($user, $this->recarga, $new_contrato);
                }

                $recarga = $this->recarga;
                $user_in_recarga = $recarga->whereHas('users', function ($query)  use ($user) {
                    $query->where('recarga_user.user_id', $user->id);
                })->where('active', true)->first();

                if (!$user_in_recarga) {
                    $user->recargas()->attach($this->recarga->id);
                    $this->cargados_recarga++;
                }
                $this->importados++;
            } else {
                $data_user = [
                    'rut'                   => $row[strtolower($this->rut)],
                    'dv'                    => $row[strtolower($this->dv)],
                    'nombres'               => $row[strtolower($this->nombres)],
                    'apellidos'             => $row[strtolower($this->apellidos)],
                    'email'                 => $row[strtolower($this->email)],
                ];
                $user = User::create($data_user);
                $user = $user->fresh();

                $esquema_controller = new EsquemaController;
                $esquema = $esquema_controller->returnEsquemaOrCreate($user->id, $this->recarga->id);

                $fecha_inicio_periodo   = $calculo[4] != null ? Carbon::parse($calculo[4])->format('Y-m-d') : null;
                $fecha_termino_periodo  = $calculo[5] != null ? Carbon::parse($calculo[5])->format('Y-m-d') : null;

                $data_contrato = [
                    'fecha_inicio'                  => $calculo[1] != null ? Carbon::parse($calculo[1])->format('Y-m-d') : NULL,
                    'fecha_termino'                 => $row[$this->fecha_termino] != $this->value_date_indefinido ? Carbon::parse($calculo[2])->format('Y-m-d') : NULL,
                    'fecha_inicio_periodo'          => $fecha_inicio_periodo,
                    'fecha_termino_periodo'         => $fecha_termino_periodo,
                    'total_dias_contrato'           => $row[$this->fecha_termino] != $this->value_date_indefinido ? $calculo[0] : NULL,
                    'total_dias_contrato_periodo'   => $calculo[3],
                    'alejamiento'                   => $fecha_termino->fecha_alejamiento,
                    'user_id'                       => $user->id,
                    'establecimiento_id'            => $establecimiento->id,
                    'unidad_id'                     => $unidad->id,
                    'planta_id'                     => $planta->id,
                    'cargo_id'                      => $cargo->id,
                    'ley_id'                        => $ley->id,
                    'hora_id'                       => $horas->id,
                    'recarga_id'                    => $this->recarga->id,
                ];

                $new_contrato = RecargaContrato::create($data_contrato);

                if ($new_contrato) {
                    $cartola_controller = new ActualizarEsquemaController;
                    $cartola_controller->storeEsquema($user, $this->recarga, $new_contrato);
                }

                if ($user) {
                    $recarga = $this->recarga;
                    $user_in_recarga = $recarga->whereHas('users', function ($query)  use ($user) {
                        $query->where('recarga_user.user_id', $user->id);
                    })->where('active', true)->first();

                    if (!$user_in_recarga) {
                        $user->recargas()->attach($this->recarga->id);
                        $this->cargados_recarga++;
                    }
                    $this->importados++;
                }
            }
            return $user;
        } catch (\Exception $error) {
            Log::info($error->getMessage());
            $this->respuesta = $error->getMessage();
            return $this->respuesta;
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
            $fecha_recarga_inicio   = Carbon::createFromDate($this->recarga->anio_beneficio, $this->recarga->mes_beneficio, '01', $tz);
            $fecha_recarga_termino  = Carbon::createFromDate($this->recarga->anio_beneficio, $this->recarga->mes_beneficio, '01', $tz);
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

    public function returnFechaTerminoContrato($fecha_termino, $fecha_termino_alejamiento)
    {
        $tz = 'America/Santiago';
        $fecha_recarga_inicio = Carbon::createFromDate($this->recarga->anio_beneficio, $this->recarga->mes_beneficio, '01', $tz);
        $fecha_recarga_termino = $fecha_recarga_inicio->endOfMonth();

        $fecha = null;
        $fecha_alejamiento = false;

        // Validar fecha de término
        if ($fecha_termino !== null && $fecha_termino !== $this->value_date_indefinido) {
            $fecha_termino_val = Carbon::parse($this->transformDate($fecha_termino));

            if ($fecha_termino_val->format('Y-m-d') <= $fecha_recarga_termino->format('Y-m-d')) {
                $fecha = $fecha_termino_val->format('Y-m-d');
            } else {
                $fecha = $fecha_recarga_termino->format('Y-m-d');
            }
        }

        // Validar fecha de término de alejamiento
        if ($fecha_termino_alejamiento !== null && $fecha_termino_alejamiento !== $this->value_date_indefinido) {
            $fecha_termino_alejamiento_val = Carbon::parse($this->transformDate($fecha_termino_alejamiento));

            if ($fecha_termino_alejamiento_val->format('Y-m-d') <= $fecha_recarga_termino->format('Y-m-d')) {
                $fecha = $fecha_termino_alejamiento_val->format('Y-m-d');
                $fecha_alejamiento = true;
            }
        }

        $response = (object) [
            'fecha' => $fecha,
            'fecha_alejamiento' => $fecha_alejamiento,
        ];

        return $response;
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

    public function periodoInRecarga($fecha_inicio, $fecha_termino)
    {
        $in_recarga = true;

        $new_fecha_inicio       = Carbon::parse($fecha_inicio)->format('Y-m');
        $new_fecha_termino      = Carbon::parse($fecha_termino)->format('Y-m');

        $tz                     = 'America/Santiago';
        $fecha_recarga_inicio   = Carbon::createFromDate($this->recarga->anio_beneficio, $this->recarga->mes_beneficio, '01', $tz)->format('Y-m');
        $fecha_recarga_termino  = Carbon::createFromDate($this->recarga->anio_beneficio, $this->recarga->mes_beneficio, '01', $tz);
        $fecha_recarga_termino  = $fecha_recarga_termino->endOfMonth()->format('Y-m');

        if ($new_fecha_inicio != $fecha_recarga_inicio || $new_fecha_termino != $fecha_recarga_termino) {
            $in_recarga = false;
        }
        return $in_recarga;
    }

    public function contratoDuplicado($rut, $fecha_inicio_real, $fecha_termino_real)
    {
        $existe         = false;
        $funcionario    = User::where('rut_completo', $rut)->first();

        if ($funcionario) {
            $contratos = $funcionario->contratos()
                ->where('recarga_id', $this->recarga->id)
                ->where('fecha_inicio_periodo', $fecha_inicio_real)
                ->where('fecha_termino_periodo', $fecha_termino_real)
                ->where(function ($query) {
                    $query->whereHas('recarga', function ($query) {
                        $query->where('active', true);
                    });
                })->count();

            if ($contratos > 0) {
                $existe = true;
            }
        }
        return $existe;
    }

    public function returnKeyFile($data)
    {
        $new_key = "{$data[$this->rut]}_{$data[$this->cod_establecimietno]}_{$data[$this->fecha_inicio]}_{$data[$this->fecha_termino]}_{$data[$this->fecha_alejamiento]}";
        return $new_key;
    }

    public function withValidator($validator)
    {
        $assoc_array = array();
        $validator->after(function ($validator) use ($assoc_array) {
            foreach ($validator->getData() as $key => $data) {
                $new_key             = $this->returnKeyFile($data);
                $rut                 = "{$data[$this->rut]}-{$data[$this->dv]}";
                $validate            = $this->validateRut($rut);

                $fecha_inicio        = Carbon::parse($this->transformDate($data[$this->fecha_inicio]));
                $fecha_termino       = $this->returnFechaTerminoContrato($data[$this->fecha_termino], $data[$this->fecha_alejamiento]);
                $calculo             = $this->totalDiasEnPeriodo($fecha_inicio, $fecha_termino->fecha);

                $fecha_inicio_real          = $calculo[4] ? Carbon::parse($calculo[4])->format('Y-m-d') : null;
                $fecha_termino_real         = $calculo[5] ? Carbon::parse($calculo[5])->format('Y-m-d') : null;
                $periodo_in_recarga         = $this->periodoInRecarga($fecha_inicio_real, $fecha_termino_real);
                $contrato_duplicado         = $this->contratoDuplicado($rut, $fecha_inicio_real, $fecha_termino_real);

                if (!$validate) {
                    $validator->errors()->add($key, 'Rut incorrecto, por favor verificar. Verificado con Módulo 11.');
                } else if (!$periodo_in_recarga) {
                    $validator->errors()->add($key, "Fechas fuera de periodo de recarga.");
                } else if ($contrato_duplicado) {
                    $validator->errors()->add($key, "Contrato duplicado");
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
                'numeric'
            ],
            $this->dv => [
                'required',
                'min:1',
                'max:1'
            ],
            $this->nombres => [
                'required',
                'string'
            ],
            $this->apellidos => [
                'required',
                'string'
            ],
            $this->email => [
                'nullable',
                'email'
            ],
            $this->cod_establecimietno => [
                'required',
                'exists:establecimientos,cod_sirh',
                new EstablecimientoIsRecarga($this->recarga->establecimiento)
            ],
            $this->cod_unidad => [
                'required',
                'exists:unidads,cod_sirh'
            ],
            $this->nom_planta => [
                'required',
                'exists:plantas,nombre'
            ],
            $this->cod_cargo => [
                'required',
                'exists:cargos,cod_sirh'
            ],
            $this->ley => [
                'required',
                function ($attribute, $value, $fail) {
                    $exists = DB::table('leys')
                        ->where(function ($query) use ($value) {
                            $query->where('nombre', $value)
                                ->orWhere('codigo', $value);
                        })
                        ->exists();

                    if (!$exists) {
                        $fail("El valor proporcionado en $attribute no existe en la tabla 'leys'.");
                    }
                },
            ],
            $this->horas => [
                'required',
                'exists:horas,nombre'
            ],
            $this->fecha_inicio => [
                'required'
            ],
            $this->fecha_termino => [
                'required',
                new TipeValueDateContrato
            ]
        ];
    }

    public function customValidationMessages()
    {
        return [
            "{$this->rut}.required"                                         => 'El rut es obligatorio.',
            "{$this->rut}.unique"                                           => 'El rut ya existe en el sistema.',
            "{$this->rut}.numeric"                                          => 'El rut debe ser un valor numérico.',
            "{$this->rut}.min"                                              => 'El rut tiene :min caracteres mínimo',
            "{$this->rut}.max"                                              => 'El rut tiene :max caracteres máximo',

            "{$this->dv}.required"                                          => 'El dv es obligatorio.',
            "{$this->dv}.min"                                               => 'El dv tiene :min caracter mínimo',
            "{$this->dv}.max"                                               => 'El dv tiene :max caracter máximo',

            "{$this->nombres}.required"                                     => 'El nombre es obligatorio.',

            "{$this->apellidos}.required"                                   => 'El apellido es obligatorio.',

            "{$this->email}.email"                                          => 'El correo es invalido.',

            "{$this->cod_establecimietno}.required"                         => 'El código es obligatorio',
            "{$this->cod_establecimietno}codigo_establecimiento.exists"     => 'El código no existe en el sistema',

            "{$this->cod_unidad}.required"                                  => 'El código es obligatorio',
            "{$this->cod_unidad}.exists"                                    => 'El código no existe en el sistema',

            "{$this->nom_planta}.required"                                  => 'El nombre es obligatorio',
            "{$this->nom_planta}.exists"                                    => 'El nombre no existe en el sistema',

            "{$this->cod_cargo}.required"                                   => 'El código es obligatorio',
            "{$this->cod_cargo}.exists"                                     => 'El código no existe en el sistema',

            "{$this->ley}.required"                                         => 'La Ley es obligatoria',
            "{$this->ley}.exists"                                           => 'La Ley no existe en el sistema',

            "{$this->horas}.required"                                       => 'La hora es obligatoria',
            "{$this->horas}.exists"                                         => 'La hora no existe en el sistema',

            "{$this->fecha_inicio}.required"                                => 'La fecha de inicio es obligatoria.',
            "{$this->fecha_inicio}.date"                                    => 'La fecha debe ser yyyy-mm-dd.',

            "{$this->fecha_termino}.required"                               => 'La fecha de término es obligatoria.',
            "{$this->fecha_termino}.date"                                   => 'La fecha debe ser yyyy-mm-dd.',
        ];
    }
}
