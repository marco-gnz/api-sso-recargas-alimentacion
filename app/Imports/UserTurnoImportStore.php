<?php

namespace App\Imports;

use App\Models\Calidad;
use App\Models\Establecimiento;
use App\Models\Planta;
use App\Models\ProcesoTurno;
use App\Models\Unidad;
use App\Models\User;
use App\Models\UserTurno;
use Carbon\Carbon;
use App\Rules\RutValidateRule;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;
use App\Rules\EstablecimientoIsRecarga;


class UserTurnoImportStore implements ToModel, WithHeadingRow, WithValidation
{
    public function  __construct($recarga, $columnas, $row_columnas)
    {
        $this->recarga                          = $recarga;
        $this->columnas                         = $columnas;
        $this->row_columnas                     = $row_columnas;

        $this->rut                              = $this->columnas[0];
        $this->dv                               = $this->columnas[1];
        $this->folio                            = $this->columnas[2];
        $this->proceso                          = $this->columnas[3];
        $this->anio_pago                        = $this->columnas[4];
        $this->mes_pago                         = $this->columnas[5];
        $this->asignacion_tercer_turno          = $this->columnas[6];
        $this->bonificacion_asignacion_turno    = $this->columnas[7];
        $this->asignacion_cuarto_turno          = $this->columnas[8];
    }

    public $importados  = 0;

    public function headingRow(): int
    {
        return $this->row_columnas;
    }

    public function model(array $row)
    {
        try {
            $rut                = "{$row[$this->rut]}-{$row[$this->dv]}";
            $funcionario        = User::where('rut', $rut)->first();
            $proceso            = ProcesoTurno::where('cod_sirh', $row[$this->proceso])->orWhere('nombre', $row[$this->proceso])->first();

            if ($funcionario && $proceso) {
                $turnante       = $this->validateTurno($proceso->id, $row[$this->asignacion_tercer_turno], $row[$this->asignacion_cuarto_turno]);
                $data = [
                    'folio'                             => $row[$this->folio] != null ? $row[$this->folio] : NULL,
                    'anio'                              => $this->recarga->anio_beneficio,
                    'mes'                               => $this->recarga->mes_beneficio,
                    'asignacion_tercer_turno'           => $row[$this->asignacion_tercer_turno],
                    'bonificacion_asignacion_turno'     => $row[$this->bonificacion_asignacion_turno],
                    'asignacion_cuarto_turno'           => $row[$this->asignacion_cuarto_turno],
                    'user_id'                           => $funcionario->id,
                    'recarga_id'                        => $this->recarga->id,
                    'proceso_id'                        => $proceso->id,
                    'es_turnante'                       => $turnante
                ];

                $turno = UserTurno::create($data);

                if ($turno) {
                    $this->importados++;
                    return $turno;
                }
            }
        } catch (\Exception $error) {
            return $error->getMessage();
        }
    }

    public function validateTurno($proceso_id, $asignacion_tercer_turno, $asignacion_cuarto_turno)
    {
        $value       = 'Pago Normal';
        $es_turnante = false;
        $pago_normal = ProcesoTurno::where('cod_sirh', $value)->orWhere('nombre', $value)->first();

        if (($pago_normal->id === $proceso_id) && ($asignacion_tercer_turno > 0 && $asignacion_cuarto_turno > 0)) {
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

    public function validateRecarga($anio, $mes)
    {
        $existe = true;

        if ($this->recarga->anio_beneficio != $anio || $this->recarga->mes_beneficio != $mes) {
            $existe = false;
        }

        return $existe;
    }

    public function validateDuplicado($data)
    {
        $existe = false;
        $rut                = "{$data[$this->rut]}-{$data[$this->dv]}";
        $funcionario        = User::where('rut', $rut)->first();
        $proceso            = ProcesoTurno::where('cod_sirh', $data[$this->proceso])->orWhere('nombre', $data[$this->proceso])->first();

        if ($funcionario && $proceso) {
            $turno = UserTurno::where('recarga_id', $this->recarga->id)
                ->where('user_id', $funcionario->id)
                ->where('folio', $data[$this->folio])
                ->where('anio', $data[$this->anio_pago])
                ->where('mes', $data[$this->mes_pago])
                ->where('asignacion_tercer_turno', $data[$this->asignacion_tercer_turno])
                ->where('bonificacion_asignacion_turno', $data[$this->bonificacion_asignacion_turno])
                ->where('asignacion_cuarto_turno', $data[$this->asignacion_cuarto_turno])
                ->where('proceso_id', $proceso->id)
                ->first();

            if ($turno) {
                $existe = true;
            }
        }
        return $existe;
    }

    public function returnKeyFile($data)
    {
        $new_key  = "{$data[$this->rut]}_{$data[$this->folio]}_{$data[$this->proceso]}_{$data[$this->anio_pago]}_{$data[$this->mes_pago]}_{$data[$this->asignacion_tercer_turno]}_{$data[$this->bonificacion_asignacion_turno]}_{$data[$this->asignacion_cuarto_turno]}";
        return $new_key;
    }

    public function withValidator($validator)
    {
        $assoc_array = array();
        $validator->after(function ($validator) use($assoc_array) {
            foreach ($validator->getData() as $key => $data) {
                $new_key            = $this->returnKeyFile($data);
                $rut                = "{$data[$this->rut]}-{$data[$this->dv]}";
                $validate_rut       = $this->validateRut($rut);
                $validate_recarga   = $this->validateRecarga((int)$data[$this->anio_pago], $data[$this->mes_pago]);
                $is_duplicado       = $this->validateDuplicado($data);

                if (!$validate_rut) {
                    $validator->errors()->add($key, 'Rut incorrecto, por favor verificar. Verificado con Módulo 11.');
                } else if (!$validate_recarga) {
                    $message = "Registro debe ser en año {$this->recarga->anio_beneficio} y mes {$this->recarga->mes_beneficio}";
                    $validator->errors()->add($key, $message);
                } else if ($is_duplicado) {
                    $validator->errors()->add($key, 'Ya existe un registro idéntico en el sistema.');
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
            $this->folio => [
                'nullable'
            ],
            $this->proceso => [
                'required',
                'exists:proceso_turnos,nombre'
            ],
            $this->anio_pago => [
                'required',
                'numeric'
            ],
            $this->mes_pago => [
                'required',
                'numeric',
            ],
            $this->asignacion_tercer_turno => [
                'required',
                'numeric',
            ],
            $this->bonificacion_asignacion_turno => [
                'required',
                'numeric',
            ],
            $this->asignacion_cuarto_turno => [
                'required',
                'numeric',
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

            "{$this->proceso}.required"                                     => 'El proceso es obligatorio.',
            "{$this->proceso}.exists"                                       => 'El proceso no existe en el sistema.',

            "{$this->anio_pago}.required"                                   => 'El año es obligatorio.',
            "{$this->anio_pago}.numeric"                                    => 'El año debe ser numérico',

            "{$this->mes_pago}.required"                                    => 'El mes es obligatorio.',
            "{$this->mes_pago}.numeric"                                     => 'El mes debe ser numérico',

            "{$this->asignacion_tercer_turno}.required"                     => 'La asignacion tercer turno es obligatoria.',
            "{$this->asignacion_tercer_turno}.numeric"                      => 'La asignacion tercer turno debe ser numérico',

            "{$this->bonificacion_asignacion_turno}.required"               => 'La bonificacion asignacion turno es obligatoria.',
            "{$this->bonificacion_asignacion_turno}.numeric"                => 'La bonificacion asignacion turno debe ser numérico',

            "{$this->asignacion_cuarto_turno}.required"                     => 'La asignacion cuarto turno es obligatoria.',
            "{$this->asignacion_cuarto_turno}.numeric"                      => 'La asignacion cuarto turno debe ser numérico',
        ];
    }
}
