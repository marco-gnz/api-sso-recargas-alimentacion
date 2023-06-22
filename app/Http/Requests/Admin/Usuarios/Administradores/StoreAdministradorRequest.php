<?php

namespace App\Http\Requests\Admin\Usuarios\Administradores;

use App\Rules\RutDvValidateRule;
use Illuminate\Validation\Rule;
use Illuminate\Foundation\Http\FormRequest;

class StoreAdministradorRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'rut'                   => ['required','min:7','max:8', Rule::unique('users', 'rut'), new RutDvValidateRule($this->dv)],
            'dv'                    => ['required', 'min:1', 'max:1'],
            'nombres'               => ['required'],
            'apellidos'             => ['required'],
            'email'                 => ['required', 'email', Rule::unique('users', 'email')],
            'roles_id'              => ['required'],
            'establecimientos_id'   => ['required'],
            'permisos'              => ['nullable']
        ];
    }

    public function messages()
    {
        return [
            'rut.required'                      => 'El :attribute es obligatorio',
            'rut.min'                           => 'El :attribute son mínimo :min caracteres',
            'rut.max'                           => 'El :attribute son máximo :max caracteres',
            'rut.unique'                        => 'El :attribute ya existe en el sistema',

            'dv.required'                       => 'El :attribute es obligatorio',
            'dv.min'                            => 'El :attribute son mínimo :min caracter',
            'dv.max'                            => 'El :attribute son máximo :max caracter',

            'nombres.required'                  => 'El :attribute es obligatorio',

            'apellidos.required'                => 'El :attribute es obligatorio',

            'email.required'                    => 'El :attribute es obligatorio',
            'email.email'                       => 'El :attribute debe ser un email (Debe incluir @ y punto)',

            'roles_id.required'                 => 'El :attribute es obligatorio',

            'establecimientos_id.required'      => 'El :attribute es obligatorio'
        ];
    }

    public function attributes()
    {
        return [
            'rut'                   => 'rut',
            'dv'                    => 'dv',
            'nombres'               => 'nombre',
            'apellidos'             => 'apellido',
            'email'                 => 'email',
            'roles_id'              => 'perfil',
            'establecimientos_id'   => 'establecimiento'
        ];
    }
}
