<?php

namespace App\Imports;

use App\Models\Cargo;
use App\Models\Establecimiento;
use App\Models\Hora;
use App\Models\Ley;
use App\Models\Planta;
use App\Models\Unidad;
use App\Models\User;
use App\Rules\EstablecimientoIsRecarga;
use App\Rules\FechaRecarga;
use Maatwebsite\Excel\Concerns\ToModel;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Illuminate\Validation\Rule;
use Maatwebsite\Excel\Concerns\WithValidation;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use App\Rules\RutValidateRule;
use App\Rules\TipeValueDateContrato;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\ToArray;
use Illuminate\Support\Facades\Validator;

class UsersImport implements WithValidation, ToCollection, WithHeadingRow
{
    public function  __construct($recarga, $columnas, $row_columnas)
    {
        $this->recarga              = $recarga;
        $this->columnas             = $columnas;
        $this->row_columnas         = $row_columnas;

        $this->rut                  = $this->columnas[0];
        $this->dv                   = $this->columnas[1];
        $this->nombres              = $this->columnas[2];
        $this->apellidos            = $this->columnas[3];
        $this->email                = $this->columnas[4];
        $this->cod_establecimietno  = $this->columnas[5];
        $this->cod_unidad           = $this->columnas[6];
        $this->nom_planta           = $this->columnas[7];
        $this->cod_cargo            = $this->columnas[8];
        $this->ley                  = $this->columnas[9];
        $this->horas                = $this->columnas[10];
        $this->fecha_inicio         = $this->columnas[11];
        $this->fecha_termino        = $this->columnas[12];
        $this->fecha_alejamiento    = $this->columnas[13];
    }

    public $data;
    public $value_date_indefinido = '00/00/0000';
    /**
     * @param array $row
     *
     * @return \Illuminate\Database\Eloquent\Model|null
     */

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
            return Carbon::instance(\PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($value));
        } catch (\ErrorException $e) {
            return Carbon::createFromFormat($format, $value);
        }
    }

    public function totalDiasEnPeriodo($fecha_inicio, $fecha_termino)
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
            $fecha_recarga_inicio = $fecha_recarga_inicio->format('Y-m-d');
            $fecha_recarga_termino = $fecha_recarga_termino->format('Y-m-d');

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

    public function returnFechaTerminoContrato($fecha_termino, $fecha_alejamiento)
    {
        $fecha = null;
        switch ($fecha_termino) {
            case ($fecha_termino != $this->value_date_indefinido && $fecha_alejamiento === $this->value_date_indefinido):
                $fecha       = Carbon::parse($this->transformDate($fecha_termino));
                break;

            case ($fecha_termino === $this->value_date_indefinido && $fecha_alejamiento != $this->value_date_indefinido):
                $fecha       = Carbon::parse($this->transformDate($fecha_alejamiento));
                break;
            default:
                $tz                     = 'America/Santiago';
                $fecha_recarga_termino  = Carbon::createFromDate($this->recarga->anio_beneficio, $this->recarga->mes_beneficio, '01', $tz);
                $fecha                  = $fecha_recarga_termino->endOfMonth();
                break;
        }
        return $fecha;
    }

    public function collection(Collection $rows)
    {
        try {
            $funcionarios = [];
            if (count($rows) > 0) {
                foreach ($rows as $key => $row) {
                    $rut                = "{$row[strtolower($this->rut)]}-{$row[strtolower($this->dv)]}";
                    $existe             = User::where('rut', $rut)->first();
                    $establecimiento    = Establecimiento::where('cod_sirh', $row[strtolower($this->cod_establecimietno)])->firstOrFail();
                    $unidad             = Unidad::where('cod_sirh', $row[strtolower($this->cod_unidad)])->firstOrFail();
                    $planta             = Planta::where('nombre', $row[strtolower($this->nom_planta)])->firstOrFail();
                    $cargo              = Cargo::where('cod_sirh', $row[strtolower($this->cod_cargo)])->firstOrFail();
                    $ley                = Ley::where('nombre', $row[strtolower($this->ley)])->orWhere('codigo', $row[strtolower($this->ley)])->firstOrFail();
                    $horas              = Hora::where('nombre', $row[strtolower($this->horas)])->firstOrFail();
                    $fecha_inicio       = Carbon::parse($this->transformDate($row[$this->fecha_inicio]));
                    $fecha_termino      = $this->returnFechaTerminoContrato($row[$this->fecha_termino], $row[$this->fecha_alejamiento]);
                    $calculo            = $this->totalDiasEnPeriodo($fecha_inicio, $fecha_termino);

                    $data = [
                        'rut'                           => $rut,
                        'nombres'                       => $row[strtolower($this->nombres)],
                        'apellidos'                     => $row[strtolower($this->apellidos)],
                        'email'                         => $row[$this->email] != null ? $row[strtolower($this->email)] : '--',
                        'existe'                        => $existe ? 'Si' : 'No',
                        'establecimiento'               => $establecimiento->sigla,
                        'unidad'                        => $unidad->nombre,
                        'planta'                        => $planta->nombre,
                        'cargo'                         => $cargo->nombre,
                        'ley'                           => $ley->nombre,
                        'hora'                          => $horas->nombre,
                        'fecha_inicio'                  => $calculo[0] ? Carbon::parse($calculo[0])->format('d-m-Y') : '¡error!',
                        'fecha_termino'                 => $calculo[1] ? Carbon::parse($calculo[1])->format('d-m-Y') : '¡error!',
                    ];

                    array_push($funcionarios, $data);
                }
                $this->data = $funcionarios;
            }
        } catch (\Exception $error) {
            $this->data = $error->getMessage();
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
                $calculo             = $this->totalDiasEnPeriodo($fecha_inicio, $fecha_termino);

                $fecha_inicio_real          = Carbon::parse($calculo[0])->format('Y-m-d');
                $fecha_termino_real         = Carbon::parse($calculo[1])->format('Y-m-d');
                $periodo_in_recarga         =  $this->periodoInRecarga($fecha_inicio_real, $fecha_termino_real);
                $contrato_duplicado         = $this->contratoDuplicado($rut, $fecha_inicio_real, $fecha_termino_real);

                if (!$validate) {
                    $validator->errors()->add($key, 'Rut incorrecto, por favor verificar. Verificado con Módulo 11.');
                } else if (!$periodo_in_recarga) {
                    $validator->errors()->add($key, "Fechas fuera de periodo de recarga.");
                } else if ($contrato_duplicado) {
                    $validator->errors()->add($key, "Ya existe un registro idéntico en el sistema.");
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
                'string',
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
                'required',
            ],
            $this->fecha_termino => [
                'required',
                new TipeValueDateContrato,
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
