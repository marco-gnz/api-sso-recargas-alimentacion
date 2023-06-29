<?php

namespace App\Imports;

use App\Models\Asistencia;
use App\Models\Establecimiento;
use App\Models\TipoAsistenciaTurno;
use App\Rules\EstablecimientoIsRecarga;
use App\Rules\RutValidateRule;
use App\Models\User;
use Carbon\Carbon;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithValidation;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use App\Http\Controllers\Admin\Calculos\ActualizarEsquemaController;
use App\Http\Controllers\Admin\Esquema\EsquemaController;

class AsistenciaImportStore implements ToModel, WithHeadingRow, WithValidation
{
    public function  __construct($recarga, $columnas, $row_columnas, $feriados)
    {
        $this->recarga                  = $recarga;
        $this->columnas                 = $columnas;
        $this->row_columnas             = $row_columnas;
        $this->feriados                 = $feriados;


        $this->rut                      = $this->columnas[0];
        $this->dv                       = $this->columnas[1];
        $this->cod_establecimiento      = $this->columnas[2];

        $this->turno_largo              = 'L';
        $this->turno_nocturno           = 'N';
        $this->dia_libre                = 'X';
    }
    public $importados  = 0;
    public $editados    = 0;

    public function headingRow(): int
    {
        return $this->row_columnas;
    }

    private function existeFechaEnFeriado($fecha)
    {
        $existe = false;

        if (in_array($fecha, $this->feriados)) {
            $existe = true;
        }
        return $existe;
    }

    private function existFechaEnContrato($funcionario, $fecha)
    {
        $existe = false;

        $contratos = $funcionario->contratos()
            ->where('recarga_id', $this->recarga->id)
            ->where(function ($query) use ($fecha) {
                $query->where('fecha_inicio_periodo', '<=', $fecha)
                    ->where('fecha_termino_periodo', '>=', $fecha);
            })
            ->count();

        if ($contratos > 0) {
            $existe = true;
        }

        return $existe;
    }

    public function model(array $row)
    {
        try {
            $rut                = "{$row[$this->rut]}-{$row[$this->dv]}";
            $funcionario        = User::where('rut', $rut)->first();
            $establecimiento    = Establecimiento::where('cod_sirh', $row[$this->cod_establecimiento])->first();

            if ($funcionario && $establecimiento) {
                $esquema_controller = new EsquemaController;
                $esquema            = $esquema_controller->returnEsquema($funcionario->id, $this->recarga->id);
                $turno_largo                = 0;
                $turno_nocturno             = 0;
                $dias_libres                = 0;
                $total_dias_feriados_turno  = 0;
                $turno_largo_en_contrato    = 0;
                $turno_nocturno_en_contrato = 0;
                $dias_libres_en_contrato    = 0;
                $total_dias_feriados_turno_en_periodo_contrato = 0;

                foreach ($this->columnas as $key => $value) {
                    $data = [
                        'user_id'               => $funcionario->id,
                        'recarga_id'            => $this->recarga->id,
                        'establecimiento_id'    => $establecimiento->id,
                        'esquema_id'            => $esquema ? $esquema->id : NULL
                    ];

                    if (is_numeric($value)) {
                        $date                   = $this->transformDate($value);
                        $tipo_asistencia_turno  = TipoAsistenciaTurno::where('nombre', $row[$value])->first();

                        if ($tipo_asistencia_turno) {
                            $existe_asistencia = $this->existAsistencia($date, $tipo_asistencia_turno->id, $funcionario->id, $establecimiento->id);

                            $data['fecha']                      = Carbon::parse($date->format('Y-m-d'))->format('Y-m-d');
                            $data['dia']                        = Carbon::parse($date->format('Y-m-d'))->format('d');
                            $data['mes']                        = Carbon::parse($date->format('Y-m-d'))->format('m');
                            $data['anio']                       = Carbon::parse($date->format('Y-m-d'))->format('Y');
                            $data['tipo_asistencia_turno_id']   = $tipo_asistencia_turno->id;



                            if (!$existe_asistencia[0]) {
                                $asistencia = Asistencia::create($data);
                                if ($asistencia) {
                                    $this->importados++;
                                }
                            } else {
                                $asistencia = $existe_asistencia[1];

                                $update = false;
                                if ($asistencia->tipo_asistencia_turno_id != $tipo_asistencia_turno->id) {
                                    $update = $asistencia->update([
                                        'tipo_asistencia_turno_id'  => $tipo_asistencia_turno->id
                                    ]);

                                    if ($update) {
                                        $this->editados++;
                                    }
                                }
                            }
                            $existe_en_contrato = $this->existFechaEnContrato($funcionario, $asistencia->fecha);
                            $existe_en_feriados = $this->existeFechaEnFeriado($asistencia->fecha);

                            $nom = $asistencia->tipoAsistenciaTurno->nombre;
                            if ($existe_en_contrato) {
                                if ($nom === 'L') {
                                    $turno_largo_en_contrato++;
                                } else if ($nom === 'N') {
                                    $turno_nocturno_en_contrato++;
                                } else if ($nom === 'X') {
                                    $dias_libres_en_contrato++;
                                }

                                if ($existe_en_feriados) {
                                    $total_dias_feriados_turno_en_periodo_contrato++;
                                }
                            }

                            if ($nom === 'L') {
                                $turno_largo++;
                            } else if ($nom === 'N') {
                                $turno_nocturno++;
                            } else if ($nom === 'X') {
                                $dias_libres++;
                            }
                            if ($existe_en_feriados) {
                                $total_dias_feriados_turno++;
                            }
                        }
                    }
                }
                $totales = (object) [
                    'turno_largo'                                           => $turno_largo,
                    'turno_nocturno'                                        => $turno_nocturno,
                    'dias_libres'                                           => $dias_libres,
                    'total_dias_feriados_turno'                             => $total_dias_feriados_turno,
                    'turno_largo_en_contrato'                               => $turno_largo_en_contrato,
                    'turno_nocturno_en_contrato'                            => $turno_nocturno_en_contrato,
                    'dias_libres_en_contrato'                               => $dias_libres_en_contrato,
                    'total_dias_feriados_turno_en_periodo_contrato'         => $total_dias_feriados_turno_en_periodo_contrato,
                    'calculo_turno'                                         => $turno_largo_en_contrato + $turno_nocturno_en_contrato,
                    'total_turno'                                           => $turno_largo + $turno_nocturno + $dias_libres
                ];

                $cartola_controller = new ActualizarEsquemaController;
                $cartola_controller->updateEsquemaTurnos($funcionario, $this->recarga, $totales);
            }
        } catch (\Exception $error) {
            return $error->getMessage();
        }
    }

    public function existAsistencia($date, $tipo_asistencia_turno_id, $funcionario_id, $establecimiento_id)
    {
        $existe = false;


        $fecha_format = Carbon::parse($date->format('Y-m-d'))->format('Y-m-d');

        $asistencia = Asistencia::where('fecha', $fecha_format)
            ->where('user_id', $funcionario_id)
            ->where('recarga_id', $this->recarga->id)
            ->where('establecimiento_id', $establecimiento_id)
            /* ->where('tipo_asistencia_turno_id', $tipo_asistencia_turno_id) */
            ->first();

        if ($asistencia) {
            $existe = true;
        }

        return array($existe, $asistencia);
    }

    public function transformDateExcel($number)
    {
        $format     = Carbon::parse($number)->format('Y-m-d');
        $str_date   = strtotime($format);
        $excel_date = floatval(25569 + $str_date / 86400);

        return $excel_date;
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

    public function existFuncionarioInRecarga($rut)
    {
        $existe         = false;
        $funcionario    = User::where('rut_completo', $rut)->first();

        if ($funcionario) {
            $query_results = $this->recarga->esquemas()->where('user_id', $funcionario->id)->count();

            if ($query_results > 0) {
                $existe = true;
            }
        }
        return $existe;
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            foreach ($validator->getData() as $key => $data) {
                $rut        = "{$data[$this->rut]}-{$data[$this->dv]}";
                $validate   = $this->validateRut($rut);
                $exist_value = $this->existValuesInDate($data);
                $exist_funcionario_in_recarga   = $this->existFuncionarioInRecarga($rut);

                if (!$validate) {
                    $validator->errors()->add($key, 'Rut incorrecto, por favor verificar. Verificado con Módulo 11.');
                } else if (!$exist_funcionario_in_recarga) {
                    $validator->errors()->add($key, 'Funcionario no existe en recarga como vigente.');
                } else if (!$exist_value[0]) {
                    $validator->errors()->add($key, $exist_value[1]);
                }
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
