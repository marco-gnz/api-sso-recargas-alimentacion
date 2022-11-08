<?php

namespace App\Imports;

use App\Models\Cargo;
use App\Models\Establecimiento;
use App\Models\Planta;
use App\Models\Recarga;
use App\Models\Unidad;
use App\Models\User;
use App\Rules\EstablecimientoIsRecarga;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithValidation;
use App\Rules\RutValidateRule;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

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
    }

    public $importados          = 0;
    public $editados            = 0;
    public $cargados_recarga    = 0;

    public function uniqueBy()
    {
        return 'email';
    }

    public function headingRow(): int
    {
        return $this->row_columnas;
    }

    public function model(array $row)
    {
        $rut                = "{$row[strtolower($this->rut)]}-{$row[strtolower($this->dv)]}";
        $user               = User::where('rut', $rut)->first();
        $establecimiento    = Establecimiento::where('cod_sirh', $row[strtolower($this->cod_establecimietno)])->first();
        $unidad             = Unidad::where('cod_sirh', $row[strtolower($this->cod_unidad)])->first();
        $planta             = Planta::where('nombre', $row[strtolower($this->nom_planta)])->first();
        $cargo              = Cargo::where('cod_sirh', $row[strtolower($this->cod_cargo)])->first();

        if ($user) {
            $data = [
                'nombres'               => $row[strtolower($this->nombres)],
                'apellidos'             => $row[strtolower($this->apellidos)],
                'email'                 => $row[strtolower($this->email)] != null ? $row[strtolower($this->email)] : $user->email,
                'establecimiento_id'    => $establecimiento->id,
                'unidad_id'             => $unidad->id,
                'planta_id'             => $planta->id,
                'cargo_id'              => $cargo->id
            ];
            $update = $user->update($data);

            if ($update) {
                $this->editados++;
            }
        } else {
            $data = [
                'rut'                   => $row[strtolower($this->rut)],
                'dv'                    => $row[strtolower($this->dv)],
                'nombres'               => $row[strtolower($this->nombres)],
                'apellidos'             => $row[strtolower($this->apellidos)],
                'email'                 => $row[strtolower($this->email)],
                'establecimiento_id'    => $establecimiento->id,
                'unidad_id'             => $unidad->id,
                'planta_id'             => $planta->id,
                'cargo_id'              => $cargo->id
            ];
            $user = User::create($data);

            if ($user) {
                $this->importados++;
            }
        }

        $user = $user->fresh();

        $user_in_recarga = $this->recarga->users->where('id', $user->id)->first();
        if (!$user_in_recarga) {
            $this->cargados_recarga++;
            $user->recargas()->attach($this->recarga->id);
        }

        return $user;
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
            ]
        ];
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
        ];
    }
}
