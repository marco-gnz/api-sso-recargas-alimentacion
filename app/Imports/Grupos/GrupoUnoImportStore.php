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

    public function totalDiasEnPeriodo($fecha_inicio, $fecha_termino, $dias = 0, $dias_periodo = 0)
    {
        try {
            $new_fecha_inicio   = Carbon::parse($fecha_inicio);
            $new_fecha_termino  = Carbon::parse($fecha_termino);

            $new_fecha_inicio->format('Y-m-d');
            $new_fecha_termino->format('Y-m-d');

            $tz                     = 'America/Santiago';
            $fecha_recarga_inicio   = Carbon::createFromDate($this->recarga->anio, $this->recarga->mes, '01', $tz);
            $fecha_recarga_termino  = Carbon::createFromDate($this->recarga->anio, $this->recarga->mes, '01', $tz);
            $fecha_recarga_termino  = $fecha_recarga_termino->endOfMonth();
            $fecha_recarga_inicio->format('Y-m-d');
            $fecha_recarga_termino->format('Y-m-d');

            switch ($this->recarga) {
                case (($new_fecha_inicio >= $fecha_recarga_inicio) && ($new_fecha_termino <= $fecha_recarga_termino)):
                    $inicio             = Carbon::parse($new_fecha_inicio->format('Y-m-d'));
                    $termino            = Carbon::parse($new_fecha_termino->format('Y-m-d'));
                    $dias_periodo       = $inicio->diffInDays($termino) + 1;
                    break;

                case (($new_fecha_inicio >= $fecha_recarga_inicio) && ($new_fecha_termino > $fecha_recarga_termino)):
                    $inicio             = Carbon::parse($new_fecha_inicio->format('Y-m-d'));
                    $termino            = Carbon::parse($fecha_recarga_termino->format('Y-m-d'));
                    $dias_periodo       = $inicio->diffInDays($termino) + 1;
                    break;

                case (($new_fecha_inicio < $fecha_recarga_inicio) && ($new_fecha_termino <= $fecha_recarga_termino)):
                    $inicio             = Carbon::parse($fecha_recarga_inicio->format('Y-m-d'));
                    $termino            = Carbon::parse($new_fecha_termino->format('Y-m-d'));
                    $dias_periodo       = $inicio->diffInDays($termino) + 1;
                    break;

                case (($new_fecha_inicio < $fecha_recarga_inicio) && ($new_fecha_termino > $fecha_recarga_termino)):
                    $inicio             = Carbon::parse($fecha_recarga_inicio->format('Y-m-d'));
                    $termino            = Carbon::parse($fecha_recarga_termino->format('Y-m-d'));
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
        $rut           = "{$row[strtolower($this->rut)]}-{$row[strtolower($this->dv)]}";
        $funcionario   = User::where('rut', $rut)->with('establecimiento', 'unidad', 'planta', 'cargo')->first();

        $tipo_ausentismo = TipoAusentismo::where('nombre', $row[strtolower($this->nombre_tipo_ausentismo)])->first();

        if ($funcionario && $tipo_ausentismo) {
            $regla          = Regla::where('tipo_ausentismo_id', $tipo_ausentismo->id)->where('recarga_id', $this->recarga->id)->first();

            $fecha_inicio   = Carbon::parse($this->transformDate($row[strtolower($this->fecha_inicio)]));
            $fecha_termino  = Carbon::parse($this->transformDate($row[strtolower($this->fecha_termino)]));
            $calculo        = $this->totalDiasEnPeriodo($fecha_inicio->format('d-m-Y'), $fecha_termino->format('d-m-Y'));

            $data = [
                'turno'                         => $funcionario->turno,
                'fecha_inicio'                  => $calculo[1],
                'fecha_termino'                 => $calculo[2],
                'fecha_inicio_periodo'          => $calculo[4],
                'fecha_termino_periodo'         => $calculo[5],
                'total_dias_ausentismo'         => $calculo[0],
                'total_dias_ausentismo_periodo' => $calculo[3],
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

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            foreach ($validator->getData() as $key => $data) {
                $rut        = "{$data[$this->rut]}-{$data[$this->dv]}";
                $validate   = $this->validateRut($rut);

                if (!$validate) {
                    $validator->errors()->add($key, 'Rut incorrecto, por favor verificar. Verificado con Módulo 11.');
                }
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
            "{$this->nombre_tipo_ausentismo}.exists"                        => 'El nombre de ausentismo no existe en el sistema',

            "{$this->fecha_inicio}.required"                                => 'La fecha de inicio es obligatoria.',
            "{$this->fecha_inicio}.date"                                    => 'La fecha debe ser yyyy-mm-dd.',

            "{$this->fecha_termino}.required"                               => 'La fecha de término es obligatoria.',
            "{$this->fecha_termino}.fecha_termino"                          => 'La fecha debe ser yyyy-mm-dd.',
        ];
    }
}
