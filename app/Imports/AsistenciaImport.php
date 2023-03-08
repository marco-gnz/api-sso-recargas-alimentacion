<?php

namespace App\Imports;

use App\Models\Asistencia;
use App\Models\Establecimiento;
use App\Models\User;
use App\Rules\EstablecimientoIsRecarga;
use Carbon\Carbon;
use Maatwebsite\Excel\Concerns\ToModel;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithValidation;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class AsistenciaImport implements ToCollection, WithHeadingRow, WithValidation
{

    public function  __construct($recarga, $columnas, $row_columnas)
    {
        $this->recarga                  = $recarga;
        $this->columnas                 = $columnas;
        $this->row_columnas             = $row_columnas;

        $this->rut                      = $this->columnas[0];
        $this->dv                       = $this->columnas[1];
        $this->cod_establecimiento      = $this->columnas[2];

        $this->turno_largo              = 'L';
        $this->turno_nocturno           = 'N';
        $this->dia_libre                = 'X';
    }

    public $data;

    public function headingRow(): int
    {
        return $this->row_columnas;
    }

    public function transformDateExcel($number)
    {
        $format = Carbon::parse($number)->format('Y-m-d');
        $str_date = strtotime($format);
        $excel_date = floatval(25569 + $str_date / 86400);

        return $excel_date;
    }


    public function collection(Collection $rows)
    {
        try {
            $asistencias = [];
            if (count($rows) > 0) {
                foreach ($rows as $row) {
                    $turno_largo    = 0;
                    $turno_nocturno = 0;
                    $dia_libre      = 0;
                    $rut                = "{$row[$this->rut]}-{$row[$this->dv]}";
                    $funcionario        = User::where('rut', $rut)->first();
                    $establecimiento    = Establecimiento::where('cod_sirh', $row[$this->cod_establecimiento])->first();

                    if ($funcionario && $establecimiento) {

                        $data = [
                            'rut'                       => $funcionario->rut_completo,
                            'nombres'                   => $funcionario->nombre_completo,
                            'establecimiento'           => $establecimiento->sigla
                        ];

                        foreach ($this->columnas as $key => $value) {
                            if (is_numeric($value)) {
                                $date = $this->transformDate($value);
                                $data[$date->format('d/m')] = $row[$value];
                                if ($row[$value] === 'L') {
                                    $turno_largo++;
                                } else if ($row[$value] === 'N') {
                                    $turno_nocturno++;
                                } else if ($row[$value] === 'X') {
                                    $dia_libre++;
                                }
                            }
                        }
                        $data['turno_largo']    = $turno_largo;
                        $data['turno_nocturno'] = $turno_nocturno;
                        $data['dia_libre']      = $dia_libre;
                        array_push($asistencias, $data);
                    }
                }
                $this->data = $asistencias;
            }
        } catch (\Exception $error) {
            $this->data = $error->getMessage();
        }
    }

    public function transformDate($value, $format = 'Y-m-d')
    {
        try {
            return Carbon::instance(\PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($value));
        } catch (\ErrorException $e) {
            return Carbon::createFromFormat($format, $value);
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

    public function existValuesInDate($data)
    {
        $existe = true;
        $message = null;
        foreach ($this->columnas as $key => $value) {
            if (is_numeric($value)) {

                if (!$data[$value]) {
                    $existe = false;
                    $message = "No existe el valor {$this->turno_largo}, {$this->turno_nocturno} o {$this->dia_libre}.";
                    return array($existe, $message, $value);
                } else if ($data[$value] != $this->turno_largo && $data[$value] != $this->turno_nocturno && $data[$value] != $this->dia_libre) {
                    $existe = false;
                    $message = "No existe el valor {$data[$value]}";
                    return array($existe, $message);
                }
            }
        }
        return array($existe, $message = null, null);
    }

    public function dataDuplicate($data)
    {
        $existe = false;
        $rut                = "{$data[$this->rut]}-{$data[$this->dv]}";
        $funcionario        = User::where('rut', $rut)->first();
        $establecimiento    = Establecimiento::where('cod_sirh', $data[$this->cod_establecimiento])->first();

        foreach ($this->columnas as $key => $value) {
            if (is_numeric($value)) {
                $date = $this->transformDate($value);

                $validate_1 = Asistencia::where('recarga_id', $this->recarga->id)
                    ->where('establecimiento_id', $establecimiento->id)
                    ->where('user_id', $funcionario->id)
                    ->where('fecha', $date->format('Y-m-d'))
                    ->first();

                if ($validate_1) {
                    $existe = true;
                    $fecha = Carbon::parse($validate_1->fecha)->format('d/m');
                    $message = "{$fecha} ya existe.";
                    return array($existe, $message);
                }
            }
        }
        return array($existe, null);
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            foreach ($validator->getData() as $key => $data) {
                $rut            = "{$data[$this->rut]}-{$data[$this->dv]}";
                $validate       = $this->validateRut($rut);
                $exist_value    = $this->existValuesInDate($data);
                $duplicado      = $this->dataDuplicate($data);

                if (!$validate) {
                    $validator->errors()->add($key, 'Rut incorrecto, por favor verificar. Verificado con Módulo 11.');
                } else if (!$exist_value[0]) {
                    $validator->errors()->add($key, $exist_value[1]);
                } /* else if ($duplicado[0]) {
                    $validator->errors()->add($key, $duplicado[1]);
                } */
            }
        });
    }

    public function rules(): array
    {
        $validate = [
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
            $this->cod_establecimiento => [
                'required',
                'exists:establecimientos,cod_sirh',
                new EstablecimientoIsRecarga($this->recarga->establecimiento)
            ]
        ];
        return $validate;
    }

    public function customValidationMessages()
    {
        $messages = [
            "{$this->rut}.required"                                         => 'El rut es obligatorio.',
            "{$this->rut}.numeric"                                          => 'El rut debe ser un valor numérico.',
            "{$this->rut}.exists"                                           => 'El rut no existe en el sistema',

            "{$this->dv}.required"                                          => 'El dv es obligatorio.',
            "{$this->dv}.min"                                               => 'El dv tiene :min caracter mínimo',
            "{$this->dv}.max"                                               => 'El dv tiene :max caracter máximo',

            "{$this->cod_establecimiento}.required"                         => 'El código es obligatorio',
            "{$this->cod_establecimiento}codigo_establecimiento.exists"     => 'El código no existe en el sistema',
        ];

        return $messages;
    }
}
