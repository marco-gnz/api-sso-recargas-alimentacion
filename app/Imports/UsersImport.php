<?php

namespace App\Imports;

use App\Models\User;
use Maatwebsite\Excel\Concerns\ToModel;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Illuminate\Validation\Rule;
use Maatwebsite\Excel\Concerns\WithValidation;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use App\Rules\RutValidateRule;
use Maatwebsite\Excel\Concerns\ToArray;
use Illuminate\Support\Facades\Validator;

class UsersImport implements ToCollection, WithHeadingRow, WithValidation
{
    public $data;
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
        return 1;
    }

    public function collection(Collection $rows)
    {
        $funcionarios = [];
        if (count($rows) > 0) {
            foreach ($rows as $row) {
                $rut = $row['rut'] . '-' . $row['dv'];
                $existe = User::where('rut', $rut)->first();

                $data = [
                    'rut'           => $row['rut'],
                    'dv'            => $row['dv'],
                    'rut_completo'  => $row['rut_completo'],
                    'nombres'       => $row['nombres'],
                    'apellidos'     => $row['apellidos'],
                    'email'         => $row['email'],
                    'existe'        => $existe ? 'Si' : 'No'
                ];

                array_push($funcionarios, $data);
            }
            $this->data = $funcionarios;
        }
    }

    public function rules(): array
    {
        return [
            'rut' => [
                'required',
                'numeric'
            ],
            'dv' => [
                'required',
                'min:1',
                'max:1'
            ],
            'rut_completo' => [
                'required',
                new RutValidateRule
            ],
            'nombres' => [
                'required',
                'string'
            ],
            'apellidos' => [
                'required',
                'string'
            ],
            'email' => [
                'nullable',
                'email'
            ],
        ];
    }

    public function customValidationMessages()
    {
        return [
            'rut.required'          => 'El rut es obligatorio.',
            'rut.unique'            => 'El rut ya existe en el sistema.',
            'rut.numeric'           => 'El rut debe ser un valor numérico.',
            'rut.min'               => 'El rut tiene :min caracteres mínimo',
            'rut.max'               => 'El rut tiene :max caracteres máximo',

            'dv.required'           => 'El dv es obligatorio.',
            'dv.min'                => 'El dv tiene :min caracter mínimo',
            'dv.max'                => 'El dv tiene :max caracter máximo',

            'nombres.required'      => 'El nombre es obligatorio.',

            'apellidos.required'    => 'El apellido es obligatorio.',

            'email.email'           => 'El correo es invalido.',
        ];
    }
}
