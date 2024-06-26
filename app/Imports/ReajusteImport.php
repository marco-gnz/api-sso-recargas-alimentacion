<?php

namespace App\Imports;

use App\Models\Reajuste;
use Maatwebsite\Excel\Concerns\ToModel;
use App\Models\User;
use App\Models\Viatico;
use App\Rules\FechaRecarga;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithValidation;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use App\Http\Controllers\Admin\Calculos\AnalisisRegistroController;
use App\Http\Controllers\Admin\Esquema\EsquemaController;
use App\Models\Esquema;
use App\Models\Recarga;
use App\Models\TipoAusentismo;
use App\Models\TipoIncremento;
use Illuminate\Support\Facades\Log;

class ReajusteImport implements WithValidation, ToCollection, WithHeadingRow
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
        $this->incremento            = $this->columnas[4];
        $this->tipo_ajuste           = $this->columnas[5];
        $this->causal_rebaja         = $this->columnas[6];
        $this->causal_incremento     = $this->columnas[7];
        $this->total_dias            = $this->columnas[8];
        $this->valor_dia             = $this->columnas[9];
        $this->observacion           = $this->columnas[10];
        $this->tipos_ajustes         = (array)['DIAS', 'MONTO'];
        $this->tipo_calculos         = (array)['REBAJA', 'INCREMENTO'];
    }

    public $data;
    public $data_sobrante;

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
            $ajustes            = [];
            $ajustes_sobrante   = [];
            if (count($rows) > 0) {
                foreach ($rows as $row) {
                    $rut                    = "{$row[$this->rut]}-{$row[$this->dv]}";
                    $funcionario            = User::where('rut', $rut)->first();

                    $tipo_ajuste            = $row[$this->tipo_ajuste];
                    $incremento             = $row[$this->incremento];
                    $causal_rebaja          = $row[$this->causal_rebaja];
                    $causal_incremento      = $row[$this->causal_incremento];
                    $total_dias             = $row[$this->total_dias];
                    $valor_dia              = $row[$this->valor_dia];
                    $observacion            = $row[$this->observacion];

                    $fecha_inicio           = Carbon::parse($this->transformDate($row[$this->fecha_inicio]));
                    $fecha_termino          = Carbon::parse($this->transformDate($row[$this->fecha_termino]));

                    $total_monto            = $valor_dia * $total_dias;
                    if ($funcionario) {
                        $esquema_controller     = new EsquemaController;
                        $esquema                = $esquema_controller->returnEsquema($funcionario->id, $this->recarga->id);
                        $turnante               = $esquema ? ($esquema->es_turnante != 2 ? true : false) : null;

                        if ($esquema) {
                            $total_dias_cancelar  = 0;
                            $total_monto_cancelar = 0;
                            if ($tipo_ajuste === $this->tipos_ajustes[0]) {
                                if ($incremento === $this->tipo_calculos[1]) {
                                    $total_dias_cancelar = $esquema->total_dias_cancelar + $total_dias;
                                } else {
                                    $total_dias_cancelar = $esquema->total_dias_cancelar - $total_dias;
                                }
                                $total_monto_cancelar = $this->recarga->monto_dia * $total_dias_cancelar;
                            } else {
                                $total_dias_cancelar  = $esquema->total_dias_cancelar;
                                if ($incremento === $this->tipo_calculos[1]) {
                                    $total_monto_cancelar = $esquema->monto_total_cancelar + $total_monto;
                                } else {
                                    $total_monto_cancelar = $esquema->monto_total_cancelar - $total_monto;
                                }
                            }
                            $data    = [
                                'funcionario'           => $funcionario->nombre_completo,
                                'turnante'              => $esquema ? Esquema::TURNANTE_NOM[$esquema->es_turnante] : '--',
                                'fechas'                => "{$fecha_inicio->format('d-m-Y')} / {$fecha_termino->format('d-m-Y')}",
                                'tipo_ajuste'           => $tipo_ajuste,
                                'tipo_calculo'          => $incremento,
                                'causal'                => $incremento === 'REBAJA' ? $causal_rebaja : $causal_incremento,
                                'total_dias'            => $total_dias,
                                'valor_dia'             => $valor_dia ? "$" . number_format($valor_dia, 0, ",", ".") : '--',
                                'total_monto'           => $total_monto ? "$" . number_format($total_monto, 0, ",", ".") : '--',
                                'DC Cartola'            => $esquema->total_dias_cancelar,
                                'MC Cartola'            => "$" . number_format($esquema->monto_total_cancelar, 0, ',', '.'),
                                '*DC Propuesta*'        => $total_dias_cancelar,
                                '*MC Propuesta*'        => "$" . number_format($total_monto_cancelar, 0, ',', '.')
                            ];
                            array_push($ajustes, $data);
                        } else {
                            $data    = [
                                'rut'                   => $row[$this->rut],
                                'dv'                    => $row[$this->dv],
                                'fecha_inicio'          => $fecha_inicio->format('d-m-Y'),
                                'fecha_termino'         => $fecha_termino->format('d-m-Y'),
                                'tipo_ajuste'           => $tipo_ajuste,
                                'tipo_calculo'          => $incremento,
                                'causal'                => $incremento === 'REBAJA' ? $causal_rebaja : $causal_incremento,
                                'total_dias'            => $total_dias,
                                'valor_dia'             => $valor_dia ? $valor_dia : '',
                                'observacion'           => $observacion,
                                'motivo'                => config('app.name') . ":" . "No registra en tabla resumen."
                            ];
                            array_push($ajustes_sobrante, $data);
                        }
                    } else {
                        $data    = [
                            'rut'                   => $row[$this->rut],
                            'dv'                    => $row[$this->dv],
                            'fecha_inicio'          => $fecha_inicio->format('d-m-Y'),
                            'fecha_termino'         => $fecha_termino->format('d-m-Y'),
                            'tipo_ajuste'           => $tipo_ajuste,
                            'tipo_calculo'          => $incremento,
                            'causal'                => $incremento === 'REBAJA' ? $causal_rebaja : $causal_incremento,
                            'total_dias'            => $total_dias,
                            'valor_dia'             => $valor_dia ? $valor_dia : '',
                            'observacion'           => $observacion,
                            'motivo'                => config('app.name') . ":" . "Rut no registra en sistema."
                        ];
                        array_push($ajustes_sobrante, $data);
                    }
                }
            }
            $this->data             = $ajustes;
            $this->data_sobrante    = $ajustes_sobrante;
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

    public function existTipoAjuste($tipo_ajuste)
    {
        if (!in_array($tipo_ajuste, $this->tipos_ajustes)) {
            return false;
        }
        return true;
    }

    public function existTipoIncremento($incremento)
    {
        if (!in_array($incremento, $this->tipo_calculos)) {
            return false;
        }
        return true;
    }

    public function existCausales($incremento, $causal_rebaja, $causal_incremento)
    {
        switch ($incremento) {
            case $this->tipo_calculos[0]:
                $tipo_ausentismo = TipoAusentismo::where('nombre', $causal_rebaja)->first();

                if ($tipo_ausentismo) {
                    return true;
                }

                return false;
                break;

            case $this->tipo_calculos[1]:
                $tipo_incremento = TipoIncremento::where('nombre', $causal_incremento)->first();

                if ($tipo_incremento) {
                    return true;
                }

                return false;
                break;
        }
    }

    public function returnKeyFile($data)
    {
        $new_key  = "{$data[$this->rut]}_{$data[$this->fecha_inicio]}_{$data[$this->fecha_termino]}_{$data[$this->tipo_ajuste]}_{$data[$this->causal_rebaja]}_{$data[$this->causal_incremento]}_{$data[$this->total_dias]}_{$data[$this->observacion]}";
        return $new_key;
    }

    public function validateDate($rut_completo, $fecha_inicio, $fecha_termino, $incremento, $tipo_ajuste, $causal_incremento, $causal_rebaja)
    {
        try {
            $fecha_inicio   = Carbon::parse($fecha_inicio)->format('Y-m-d');
            $fecha_termino  = Carbon::parse($fecha_termino)->format('Y-m-d');
            $funcionario    = User::where('rut_completo', $rut_completo)->first();

            $tipo_incremento = TipoIncremento::where('nombre', $causal_incremento)->first();
            $tipo_ausentismo = TipoAusentismo::where('nombre', $causal_rebaja)->first();

            $incremento   = $incremento === 'INCREMENTO' ? true : false;
            $tipo_ajuste  = $tipo_ajuste === 'DIAS' ? 0 : 1;

            if ($funcionario) {
                $reajustes = Reajuste::where('recarga_id', $this->recarga->id)
                    ->where('user_id', $funcionario->id)
                    ->where('incremento', $incremento)
                    ->where('tipo_reajuste', $tipo_ajuste)
                    ->where(function ($query) use ($fecha_inicio, $fecha_termino) {
                        $query->where(function ($query) use ($fecha_inicio, $fecha_termino) {
                            $query->where('fecha_inicio', '<=', $fecha_inicio)
                                ->where('fecha_termino', '>=', $fecha_inicio);
                        })->orWhere(function ($query) use ($fecha_inicio, $fecha_termino) {
                            $query->where('fecha_inicio', '<=', $fecha_termino)
                                ->where('fecha_termino', '>=', $fecha_termino);
                        })->orWhere(function ($query) use ($fecha_inicio, $fecha_termino) {
                            $query->where('fecha_inicio', '>=', $fecha_inicio)
                                ->where('fecha_termino', '<=', $fecha_termino);
                        });
                    })
                    ->where(function ($q) use ($incremento, $tipo_incremento, $tipo_ausentismo) {
                        if ($incremento) {
                            $q->where('tipo_incremento_id', $tipo_incremento->id);
                        } else {
                            $q->where('tipo_ausentismo_id', $tipo_ausentismo->id);
                        }
                    })->whereIn('last_status', [0, 1]);

                $tiene = $reajustes->whereHas('recarga', function ($query) {
                    $query->where('active', true);
                })->count();


                if ($tiene > 0) {
                    return true;
                }
                return false;
            }
        } catch (\Exception $error) {
            Log::info($error->getMessage());
        }
    }

    public function validateDuplicate($rut_completo, $fecha_inicio, $fecha_termino, $incremento, $tipo_ajuste, $causal_incremento, $causal_rebaja, $total_dias, $valor_dia, $observacion)
    {
        try {
            $fecha_inicio   = Carbon::parse($fecha_inicio)->format('Y-m-d');
            $fecha_termino  = Carbon::parse($fecha_termino)->format('Y-m-d');
            $funcionario    = User::where('rut_completo', $rut_completo)->first();

            $tipo_incremento = TipoIncremento::where('nombre', $causal_incremento)->first();
            $tipo_ausentismo = TipoAusentismo::where('nombre', $causal_rebaja)->first();

            $incremento   = $incremento === 'INCREMENTO' ? true : false;
            $tipo_ajuste  = $tipo_ajuste === 'DIAS' ? 0 : 1;

            if ($funcionario) {
                $reajustes = Reajuste::where('recarga_id', $this->recarga->id)
                    ->where('user_id', $funcionario->id)
                    ->where('incremento', $incremento)
                    ->where('tipo_reajuste', $tipo_ajuste)
                    ->where('fecha_inicio', $fecha_inicio)
                    ->where('fecha_termino', $fecha_termino)
                    ->where(function ($q) use ($incremento, $tipo_incremento, $tipo_ausentismo) {
                        if ($incremento) {
                            $q->where('tipo_incremento_id', $tipo_incremento->id);
                        } else {
                            $q->where('tipo_ausentismo_id', $tipo_ausentismo->id);
                        }
                    })->where(function ($q) use ($tipo_ajuste, $total_dias, $valor_dia) {
                        if ($tipo_ajuste === 0) {
                            $q->where('total_dias', $total_dias);
                        } else {
                            $q->where('total_dias', $total_dias)
                                ->where('valor_dia', $valor_dia);
                        }
                    })
                    ->where('observacion', $observacion)
                    ->whereIn('last_status', [0, 1]);

                $tiene = $reajustes->whereHas('recarga', function ($query) {
                    $query->where('active', true);
                })->count();

                if ($tiene > 0) {
                    return true;
                }
                return false;
            }
        } catch (\Exception $error) {
            Log::info($error->getMessage());
        }
    }

    public function withValidator($validator)
    {
        $assoc_array = array();

        $validator->after(function ($validator) use ($assoc_array) {
            foreach ($validator->getData() as $key => $data) {
                $fecha_inicio                   = Carbon::parse($this->transformDate($data[$this->fecha_inicio]));
                $fecha_termino                  = Carbon::parse($this->transformDate($data[$this->fecha_termino]));
                $new_key                        = $this->returnKeyFile($data);
                $rut                            = "{$data[$this->rut]}-{$data[$this->dv]}";
                $validate                       = $this->validateRut($rut);
                $exist_incremento               = $this->existTipoIncremento($data[$this->incremento]);
                $exist_tipo_ajuste              = $this->existTipoAjuste($data[$this->tipo_ajuste]);
                $exist_causales                 = $this->existCausales($data[$this->incremento], $data[$this->causal_rebaja], $data[$this->causal_incremento]);
                $validate_duplicate             = $this->validateDuplicate($rut, $fecha_inicio, $fecha_termino, $data[$this->incremento], $data[$this->tipo_ajuste], $data[$this->causal_incremento], $data[$this->causal_rebaja], $data[$this->total_dias], $data[$this->valor_dia], $data[$this->observacion]);

                if (!$validate) {
                    $validator->errors()->add($key, 'Rut incorrecto, por favor verificar. Verificado con Módulo 11.');
                } else if (!$exist_incremento) {
                    $validator->errors()->add($key, "El incremento debe ser REBAJA o INCREMENTO");
                } else if (!$exist_tipo_ajuste) {
                    $validator->errors()->add($key, "El tipo de ajuste debe ser DIAS o MONTO");
                } else if (!$exist_causales) {
                    $validator->errors()->add($key, 'El tipo de rebaja o incremento no existe en el sistema.');
                } else if ($validate_duplicate) {
                    $validator->errors()->add($key, 'Funcionario registra un ajuste idéntico.');
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
            $this->tipo_ajuste => [
                'required'
            ],
            $this->incremento => [
                'required'
            ],
            $this->causal_rebaja => [
                'required_if:tipo_ajuste,REBAJA'
            ],
            $this->causal_incremento => [
                'required_if:tipo_ajuste,INCREMENTO'
            ],
            $this->total_dias => [
                'required',
                'numeric',
                'integer'
            ],
            $this->valor_dia => [
                'required_if:incremento,MONTO',
                'nullable',
                'numeric'
            ],
            $this->observacion => [
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

            "{$this->tipo_ajuste}.required"                                 => 'El tipo de ajuste es obligatorio.',

            "{$this->incremento}.required"                                  => 'El tipo de cálculo es obligatorio.',

            "{$this->causal_rebaja}.required_if"                            => 'La causal de rebaja es obligatoria.',

            "{$this->causal_incremento}.required_if"                        => 'La causal de incremento es obligatoria.',

            "{$this->total_dias}.required"                                  => 'El total de días es obligatorio.',
            "{$this->total_dias}.numeric"                                   => 'El total de días debe ser numérico.',
            "{$this->total_dias}.integer"                                   => 'El total de días debe ser número entero.',

            "{$this->valor_dia}.required_if"                                => 'El valor del día es obligatorio.',
            "{$this->valor_dia}.numeric"                                    => 'El valor del día debe ser numérico.',

            "{$this->observacion}.required"                                 => 'La observación es obligatoria.',
        ];
    }
}
