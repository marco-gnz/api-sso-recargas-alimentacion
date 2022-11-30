<?php

namespace App\Imports;

use App\Models\Calidad;
use App\Models\User;
use App\Models\UserTurno;
use App\Models\Establecimiento;
use App\Models\Planta;
use App\Models\ProcesoTurno;
use App\Models\Recarga;
use App\Models\Regla;
use App\Models\Unidad;
use Maatwebsite\Excel\Concerns\ToModel;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Illuminate\Validation\Rule;
use Maatwebsite\Excel\Concerns\WithValidation;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use App\Rules\EstablecimientoIsRecarga;
use App\Rules\RutValidateRule;
use Carbon\Carbon;
use Maatwebsite\Excel\Concerns\ToArray;
use Illuminate\Support\Facades\Validator;

class UserTurnoImport implements WithValidation, ToCollection, WithHeadingRow
{
    public function  __construct($recarga, $columnas, $row_columnas)
    {
        $this->recarga                  = $recarga;
        $this->columnas                 = $columnas;
        $this->row_columnas             = $row_columnas;

        $this->rut                              = $this->columnas[0];
        $this->dv                               = $this->columnas[1];
        $this->proceso                          = $this->columnas[2];
        $this->anio_pago                         = $this->columnas[3];
        $this->mes_pago                          = $this->columnas[4];
        $this->calidad_juridica                  = $this->columnas[5];
        $this->cod_establecimietno              = $this->columnas[6];
        $this->unidad                           = $this->columnas[7];
        $this->nombre_planta                     = $this->columnas[8];
        $this->asignacion_tercer_turno          = $this->columnas[9];
        $this->bonificacion_asignacion_turno    = $this->columnas[10];
        $this->asignacion_cuarto_turno          = $this->columnas[11];

        $this->anio                              = $recarga->anio;
        $this->mes                              = $recarga->mes;
    }

    public $data;

    public function headingRow(): int
    {
        return $this->row_columnas;
    }

    public function collection(Collection $rows)
    {
        try {
            $turnos = [];
            if (count($rows) > 0) {
                foreach ($rows as $row) {
                    $rut                = "{$row[$this->rut]}-{$row[$this->dv]}";
                    $funcionario        = User::where('rut', $rut)->first();
                    $proceso            = ProcesoTurno::where('cod_sirh', $row[$this->proceso])->orWhere('nombre', $row[$this->proceso])->first();
                    $calidad            = Calidad::where('cod_sirh', $row[$this->calidad_juridica])->orWhere('nombre', $row[$this->calidad_juridica])->first();
                    $establecimiento    = Establecimiento::where('cod_sirh', $row[$this->cod_establecimietno])->first();
                    $unidad             = Unidad::where('cod_sirh', $row[$this->unidad])->first();
                    $planta             = Planta::where('cod_sirh', $row[$this->nombre_planta])->orWhere('nombre', $row[$this->nombre_planta])->first();
                    $turnante           = $this->validateTurno($proceso->id, $row[$this->asignacion_tercer_turno], $row[$this->bonificacion_asignacion_turno], $row[$this->asignacion_cuarto_turno]);

                    if ($funcionario && $proceso && $calidad && $establecimiento && $unidad && $planta) {
                        $data = [
                            'rut'                               => $funcionario->rut_completo,
                            'nombres'                           => $funcionario->nombre_completo,
                            'año'                               => $row[$this->anio_pago],
                            'mes'                               => $row[$this->mes_pago],
                            'calidad'                           => $calidad->nombre,
                            'establecimiento'                   => $establecimiento->sigla,
                            'unidad'                            => $unidad->nombre,
                            'planta'                            => $planta->nombre,
                            'proceso'                           => $proceso->nombre,
                            'asignacion tercer turno'           => $row[$this->asignacion_tercer_turno],
                            'bonificacion asignacion turno'     => $row[$this->bonificacion_asignacion_turno],
                            'asignacion_cuarto_turno'           => $row[$this->asignacion_cuarto_turno],
                            'turnante'                          => $turnante ? 'Si' : 'No'
                        ];
                        array_push($turnos, $data);
                    }
                }
            }
            $this->data = $turnos;
        } catch (\Exception $error) {
            return $error->getMessage();
        }
    }

    public function validateTurno($proceso_id, $asignacion_tercer_turno, $bonificacion_asignacion_turno, $asignacion_cuarto_turno)
    {
        $value = 'Pago Normal';
        $es_turnante = false;
        $pago_normal = ProcesoTurno::where('cod_sirh', $value)->orWhere('nombre', $value)->first();

        if (($pago_normal->id === $proceso_id) && ($asignacion_tercer_turno > 0 && $bonificacion_asignacion_turno > 0 && $asignacion_cuarto_turno > 0)) {
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

        if ($this->recarga->anio != $anio || $this->recarga->mes != $mes) {
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
        $calidad            = Calidad::where('cod_sirh', $data[$this->calidad_juridica])->orWhere('nombre', $data[$this->calidad_juridica])->first();
        $establecimiento    = Establecimiento::where('cod_sirh', $data[$this->cod_establecimietno])->first();
        $unidad             = Unidad::where('cod_sirh', $data[$this->unidad])->first();
        $planta             = Planta::where('cod_sirh', $data[$this->nombre_planta])->orWhere('nombre', $data[$this->nombre_planta])->first();

        $turno = UserTurno::where('recarga_id', $this->recarga->id)
            ->where('user_id', $funcionario->id)
            ->where('anio', $data[$this->anio_pago])
            ->where('mes', $data[$this->mes_pago])
            ->where('asignacion_tercer_turno', $data[$this->asignacion_tercer_turno])
            ->where('bonificacion_asignacion_turno', $data[$this->bonificacion_asignacion_turno])
            ->where('asignacion_cuarto_turno', $data[$this->asignacion_cuarto_turno])
            ->where('proceso_id', $proceso->id)
            ->where('calidad_id', $calidad->id)
            ->where('establecimiento_id', $establecimiento->id)
            ->where('unidad_id', $unidad->id)
            ->where('planta_id', $planta->id)
            ->first();

        if ($turno) {
            $existe = true;
        }

        return $existe;
    }

    public function withValidator($validator)
    {

        $validator->after(function ($validator) {

            foreach ($validator->getData() as $key => $data) {
                $rut                = "{$data[$this->rut]}-{$data[$this->dv]}";
                $validate_rut       = $this->validateRut($rut);
                $validate_recarga   = $this->validateRecarga((int)$data[$this->anio_pago], $data[$this->mes_pago]);
                $is_duplicado       = $this->validateDuplicado($data);

                if (!$validate_rut) {
                    $validator->errors()->add($key, 'Rut incorrecto, por favor verificar. Verificado con Módulo 11.');
                } else if (!$validate_recarga) {
                    $message = "Registro debe ser en año {$this->anio} y mes {$this->mes}";
                    $validator->errors()->add($key, $message);
                }else if($is_duplicado){
                    $validator->errors()->add($key, 'Registro duplicado.');
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
                'digits:1'
            ],
            $this->proceso => [
                'required',
                'exists:proceso_turnos,nombre'
            ],
            $this->anio_pago => [
                'required',
                'numeric',
                'digits:4'
            ],
            $this->mes_pago => [
                'required',
                'numeric',
                'digits:2'
            ],
            $this->calidad_juridica => [
                'required',
                'exists:calidads,cod_sirh'
            ],
            $this->cod_establecimietno => [
                'required',
                'exists:establecimientos,cod_sirh',
                new EstablecimientoIsRecarga($this->recarga->establecimiento)
            ],
            $this->unidad => [
                'required',
                'exists:unidads,cod_sirh'
            ],
            $this->nombre_planta => [
                'required',
                'exists:plantas,nombre'
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
            "{$this->dv}.digits"                                            => 'El dv tiene :digits caracter máximo',


            "{$this->cod_establecimietno}.required"                         => 'El código es obligatorio',
            "{$this->cod_establecimietno}codigo_establecimiento.exists"     => 'El código no existe en el sistema',

            "{$this->proceso}.required"                                     => 'El proceso es obligatorio.',
            "{$this->proceso}.exists"                                       => 'El proceso no existe en el sistema.',

            "{$this->anio_pago}.required"                                   => 'El año es obligatorio.',
            "{$this->anio_pago}.numeric"                                    => 'El año debe ser numérico',
            "{$this->anio_pago}.digits"                                     => 'El año debe tener :digits dígitos',

            "{$this->mes_pago}.required"                                    => 'El mes es obligatorio.',
            "{$this->mes_pago}.numeric"                                     => 'El mes debe ser numérico',
            "{$this->mes_pago}.digits"                                      => 'El mes debe tener :digits dígitos',

            "{$this->calidad_juridica}.required"                            => 'La calidad es obligatoria.',
            "{$this->calidad_juridica}.exists"                              => 'La calidad no existe en el sistema.',

            "{$this->cod_establecimietno}.required"                         => 'El código es obligatorio.',
            "{$this->cod_establecimietno}.exists"                           => 'El código no existe en el sistema.',

            "{$this->unidad}.required"                                      => 'La unidad es obligatoria.',
            "{$this->unidad}.exists"                                        => 'La unidad no existe en el sistema.',

            "{$this->nombre_planta}.required"                               => 'La planta es obligatoria.',
            "{$this->nombre_planta}.exists"                                 => 'La planta no existe en el sistema.',

            "{$this->asignacion_tercer_turno}.required"                     => 'La asignacion tercer turno es obligatoria.',
            "{$this->asignacion_tercer_turno}.numeric"                      => 'La asignacion tercer turno debe ser numérico',

            "{$this->bonificacion_asignacion_turno}.required"               => 'La bonificacion asignacion turno es obligatoria.',
            "{$this->bonificacion_asignacion_turno}.numeric"                => 'La bonificacion asignacion turno debe ser numérico',

            "{$this->asignacion_cuarto_turno}.required"                     => 'La asignacion cuarto turno es obligatoria.',
            "{$this->asignacion_cuarto_turno}.numeric"                      => 'La asignacion cuarto turno debe ser numérico',
        ];
    }
}
