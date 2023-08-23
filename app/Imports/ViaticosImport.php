<?php

namespace App\Imports;

use App\Models\User;
use App\Models\Viatico;
use App\Rules\FechaRecarga;
use Carbon\Carbon;
use Maatwebsite\Excel\Concerns\ToModel;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithValidation;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use App\Http\Controllers\Admin\Calculos\AnalisisRegistroController;
use App\Http\Controllers\Admin\Esquema\EsquemaController;
use App\Models\Esquema;
use Illuminate\Support\Facades\Log;

class ViaticosImport implements WithValidation, ToCollection, WithHeadingRow
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

    public $data;

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

    public function collection(Collection $rows)
    {
        try {
            $viaticos = [];
            if (count($rows) > 0) {
                foreach ($rows as $row) {
                    $rut                = "{$row[$this->rut]}-{$row[$this->dv]}";
                    $funcionario        = User::where('rut', $rut)->first();

                    if ($funcionario) {
                        $esquema_controller                 = new EsquemaController;
                        $esquema                            = $esquema_controller->returnEsquema($funcionario->id, $this->recarga->id);
                        $turnante                           = $esquema ? ($esquema->es_turnante != 2 ? true : false) : null;

                        $fecha_inicio                       = Carbon::parse($this->transformDate($row[$this->fecha_inicio]));
                        $fecha_termino                      = Carbon::parse($this->transformDate($row[$this->fecha_termino]));
                        $fecha_resolucion                   = Carbon::parse($this->transformDate($row[$this->fecha_resolucion]));
                        $analisis_registro_controller       = new AnalisisRegistroController;
                        $analisis_viaticos                  = $analisis_registro_controller->analisisViaticos($turnante, $this->recarga, $funcionario, $fecha_inicio, $fecha_termino);

                        $data = [
                            'nombres'                                           => $funcionario->nombre_completo,
                            'turnante'                                          => $esquema ? Esquema::TURNANTE_NOM[$esquema->es_turnante] : '--',
                            'fecha'                                             => "{$analisis_viaticos->fecha_inicio->format('d-m-Y')} / {$analisis_viaticos->fecha_termino->format('d-m-Y')}",
                            'jornada'                                           => $row[$this->jornada],
                            'tipo_comision'                                     => $row[$this->tipo_comision],
                            'motivo_viatico'                                    => $row[$this->motivo_viatico],
                            'valor_viatico'                                     => $row[$this->valor_viatico],
                            'fecha_periodo'                                     => "{$analisis_viaticos->fecha_inicio_periodo->format('d-m-Y')} / {$analisis_viaticos->fecha_termino_periodo->format('d-m-Y')}",
                            'dias_naturales'                                    => $analisis_viaticos->total_dias_ausentismo_periodo_calculo,
                            'total_dias_habiles_ausentismo_periodo'             => $analisis_viaticos->total_dias_habiles_ausentismo_periodo_calculo,
                            'descuento_en_turnos'                               => $analisis_viaticos->descuento_en_turnos ? 'Si' : 'No'
                        ];
                        array_push($viaticos, $data);
                    }
                }
            }
            $this->data = $viaticos;
        } catch (\Exception $error) {
            return $error->getMessage();
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
            $fecha_recarga_inicio   = Carbon::createFromDate($this->recarga->anio_calculo, $this->recarga->mes_calculo, '01', $tz);
            $fecha_recarga_termino  = Carbon::createFromDate($this->recarga->anio_calculo, $this->recarga->mes_calculo, '01', $tz);
            $fecha_recarga_termino  = $fecha_recarga_termino->endOfMonth();
            $fecha_recarga_inicio   = $fecha_recarga_inicio->format('Y-m-d');
            $fecha_recarga_termino  = $fecha_recarga_termino->format('Y-m-d');

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

    public function viaticoDuplicado($rut, $fecha_inicio_real, $fecha_termino_real)
    {
        $existe         = false;
        $funcionario    = User::where('rut_completo', $rut)->first();
        if ($funcionario) {
            $viaticos   = $funcionario->viaticos()
                ->where('recarga_id', $this->recarga->id)
                ->where('fecha_inicio_periodo', $fecha_inicio_real)
                ->where('fecha_termino_periodo', $fecha_termino_real)
                ->where(function ($query) {
                    $query->whereHas('recarga', function ($query) {
                        $query->where('active', true);
                    });
                })
                ->count();

            if ($viaticos > 0) {
                $existe = true;
            }
        }
        return $existe;
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

    public function periodoInRecarga($fecha_inicio, $fecha_termino)
    {
        $in_recarga = true;

        $new_fecha_inicio       = Carbon::parse($fecha_inicio)->format('Y-m');
        $new_fecha_termino      = Carbon::parse($fecha_termino)->format('Y-m');

        $tz                     = 'America/Santiago';
        $fecha_recarga_inicio   = Carbon::createFromDate($this->recarga->anio_calculo, $this->recarga->mes_calculo, '01', $tz)->format('Y-m');
        $fecha_recarga_termino  = Carbon::createFromDate($this->recarga->anio_calculo, $this->recarga->mes_calculo, '01', $tz);
        $fecha_recarga_termino  = $fecha_recarga_termino->endOfMonth()->format('Y-m');

        if ($new_fecha_inicio != $fecha_recarga_inicio || $new_fecha_termino != $fecha_recarga_termino) {
            $in_recarga = false;
        }
        return $in_recarga;
    }

    public function returnKeyFile($data)
    {
        $new_key  = "{$data[$this->rut]}_{$data[$this->fecha_inicio]}_{$data[$this->fecha_termino]}_{$data[$this->jornada]}_{$data[$this->tipo_resolucion]}_{$data[$this->numero_resolucion]}_{$data[$this->fecha_resolucion]}_{$data[$this->tipo_comision]}_{$data[$this->motivo_viatico]}_{$data[$this->valor_viatico]}";
        return $new_key;
    }

    public function withValidator($validator)
    {
        $assoc_array = array();

        $validator->after(function ($validator) use ($assoc_array) {
            foreach ($validator->getData() as $key => $data) {
                $new_key                        = $this->returnKeyFile($data);
                $rut                            = "{$data[$this->rut]}-{$data[$this->dv]}";
                $fecha_inicio                   = Carbon::parse($this->transformDate($data[$this->fecha_inicio]));
                $fecha_termino                  = Carbon::parse($this->transformDate($data[$this->fecha_termino]));
                $calculo                        = $this->totalDiasEnPeriodo($fecha_inicio, $fecha_termino);
                $fecha_inicio_real              = Carbon::parse($calculo[0])->format('Y-m-d');
                $fecha_termino_real             = Carbon::parse($calculo[1])->format('Y-m-d');

                $validate                       = $this->validateRut($rut);
                $exist_funcionario_in_recarga   = $this->existFuncionarioInRecarga($rut);
                $periodo_in_recarga             = $this->periodoInRecarga($fecha_inicio_real, $fecha_termino_real);
                $viatico_duplicado              = $this->viaticoDuplicado($rut, $fecha_inicio_real, $fecha_termino_real);


                if (!$validate) {
                    $validator->errors()->add($key, 'Rut incorrecto, por favor verificar. Verificado con Módulo 11.');
                } /* else if (!$exist_funcionario_in_recarga) {
                    $validator->errors()->add($key, 'Funcionario no existe en recarga como vigente.');
                }  */else if (!$periodo_in_recarga) {
                    $validator->errors()->add($key, "Fechas fuera de periodo de recarga.");
                } else if ($viatico_duplicado) {
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
