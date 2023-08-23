<?php

namespace App\Imports;

use App\Models\User;
use App\Models\Viatico;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;
use Carbon\Carbon;
use App\Http\Controllers\Admin\Calculos\ActualizarEsquemaController;
use App\Http\Controllers\Admin\Esquema\EsquemaController;
use App\Http\Controllers\Admin\Calculos\AnalisisRegistroController;
use App\Models\Esquema;
use Illuminate\Support\Facades\Log;

class ViaticosImportStore implements ToModel, WithHeadingRow, WithValidation
{
    public function  __construct($recarga, $columnas, $row_columnas)
    {
        $this->recarga                  = $recarga;
        $this->columnas                 = $columnas;
        $this->row_columnas             = $row_columnas;

        $this->rut                   = $this->columnas[0];
        $this->dv                    = $this->columnas[1];
        $this->fecha_inicio          = $this->columnas[2];
        $this->fecha_termino         = $this->columnas[3];
        $this->jornada               = $this->columnas[4];
        $this->tipo_resolucion       = $this->columnas[5];
        $this->numero_resolucion     = $this->columnas[6];
        $this->fecha_resolucion      = $this->columnas[7];
        $this->tipo_comision         = $this->columnas[8];
        $this->motivo_viatico        = $this->columnas[9];
        $this->valor_viatico         = $this->columnas[10];

        $this->anio                  = $recarga->anio;
        $this->mes                   = $recarga->mes;
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
        try {
            $rut                = "{$row[$this->rut]}-{$row[$this->dv]}";
            $funcionario        = User::where('rut', $rut)->first();
            if ($funcionario) {
                $esquema_controller     = new EsquemaController;
                $esquema                = $esquema_controller->returnEsquema($funcionario->id, $this->recarga->id);
                $turnante                = $esquema ? ($esquema->es_turnante != 2 ? true : false) : null;

                $fecha_inicio       = Carbon::parse($this->transformDate($row[$this->fecha_inicio]));
                $fecha_termino      = Carbon::parse($this->transformDate($row[$this->fecha_termino]));
                $fecha_resolucion   = Carbon::parse($this->transformDate($row[$this->fecha_resolucion]));
                /* $calculo            = $this->totalDiasEnPeriodo($fecha_inicio, $fecha_termino); */

                $analisis_registro_controller       = new AnalisisRegistroController;
                $analisis_viaticos                  = $analisis_registro_controller->analisisViaticos($turnante, $this->recarga, $funcionario, $fecha_inicio, $fecha_termino);

                $data = [
                    'fecha_inicio'                          => $analisis_viaticos->fecha_inicio->format('Y-m-d'),
                    'fecha_termino'                         => $analisis_viaticos->fecha_termino->format('Y-m-d'),
                    'total_dias'                            => $analisis_viaticos->total_dias_ausentismo_periodo,
                    'fecha_inicio_periodo'                  => $analisis_viaticos->fecha_inicio_periodo->format('Y-m-d'),
                    'fecha_termino_periodo'                 => $analisis_viaticos->fecha_termino_periodo->format('Y-m-d'),

                    'total_dias_periodo'                    => $analisis_viaticos->total_dias_ausentismo_periodo,
                    'total_dias_habiles_periodo'            => $analisis_viaticos->total_dias_habiles_ausentismo_periodo,
                    'total_dias_periodo_turno'              => $analisis_viaticos->total_dias_ausentismo_periodo_turno,
                    'total_dias_habiles_periodo_turno'      => $analisis_viaticos->total_dias_habiles_ausentismo_periodo_turno,
                    'descuento_turno_libre'                 => $analisis_viaticos->descuento_en_turnos,

                    'jornada'                               => $row[$this->jornada],
                    'tipo_resolucion'                       => $row[$this->tipo_resolucion],
                    'n_resolucion'                          => $row[$this->numero_resolucion],
                    'fecha_resolucion'                      => $fecha_resolucion->format('Y-m-d'),
                    'tipo_comision'                         => $row[$this->tipo_comision],
                    'motivo_viatico'                        => $row[$this->motivo_viatico],
                    'valor_viatico'                         => $row[$this->valor_viatico],
                    'recarga_id'                            => $this->recarga->id,
                    'esquema_id'                            => $esquema ? $esquema->id : NULL,
                    'user_id'                               => $funcionario->id,
                ];

                $viatico = Viatico::create($data);

                if ($viatico) {
                    $cartola_controller = new ActualizarEsquemaController;
                    $cartola_controller->updateEsquemaViaticos($funcionario, $this->recarga);
                    $this->importados++;
                    return $viatico;
                }
            }
        } catch (\Exception $error) {
            Log::info($error->getMessage());
            return $error->getMessage();
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
        $assoc_array = array();

        $validator->after(function ($validator) use ($assoc_array) {
            foreach ($validator->getData() as $key => $data) {
                $new_key                = "{$data[$this->rut]}_{$data[$this->fecha_inicio]}_{$data[$this->fecha_termino]}_{$data[$this->jornada]}_{$data[$this->tipo_resolucion]}_{$data[$this->numero_resolucion]}_{$data[$this->fecha_resolucion]}_{$data[$this->tipo_comision]}_{$data[$this->motivo_viatico]}_{$data[$this->valor_viatico]}";
                $rut                    = "{$data[$this->rut]}-{$data[$this->dv]}";
                $validate               = $this->validateRut($rut);

                if (!$validate) {
                    $validator->errors()->add($key, 'Rut incorrecto, por favor verificar. Verificado con Módulo 11.');
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
            $this->fecha_inicio => [
                'required'
            ],
            $this->fecha_termino => [
                'required'
            ],
            $this->jornada => [
                'required'
            ],
            $this->tipo_resolucion => [
                'required'
            ],
            $this->numero_resolucion => [
                'required'
            ],
            $this->fecha_resolucion => [
                'required'
            ],
            $this->tipo_comision => [
                'required'
            ],
            $this->motivo_viatico => [
                'required'
            ],
            $this->valor_viatico => [
                'required'
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

            "{$this->fecha_inicio}.required"                                => 'La fecha de inicio es obligatoria.',

            "{$this->fecha_termino}.required"                               => 'La fecha de término es obligatoria.',

            "{$this->jornada}.required"                                     => 'La jornada es obligatoria.',

            "{$this->tipo_resolucion}.required"                             => 'El tipo de resolución es obligatorio.',

            "{$this->numero_resolucion}.required"                           => 'El N° de resolución es obligatorio.',

            "{$this->fecha_resolucion}.required"                            => 'La fecha de resolución es obligatoria.',

            "{$this->tipo_comision}.required"                               => 'La comisión es obligatoria.',

            "{$this->motivo_viatico}.required"                              => 'El motívo de viático es obligatorio.',

            "{$this->valor_viatico}.required"                               => 'El valor de viático es obligatorio.',
        ];
    }
}
