<?php

namespace App\Http\Requests\Enviar;

use Illuminate\Foundation\Http\FormRequest;

class EnviarCartolaRequest extends FormRequest
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
            'funcionario_id'        => ['required', 'exists:users,uuid'],
            'esquema_id'            => ['required'],
            'email'                 => ['required', 'email'],
        ];
    }

    public function messages()
    {
        return [
            'funcionario_id.required'             => 'El :attribute es obligatorio',
            'funcionario_id.exists'               => 'El :attribute no existe en los registros',

            'esquema_id.required'                 => 'La :attribute es obligatoria',

            'email.required'                      => 'El :attribute es obligatorio',
            'email.email'                         => 'El :attribute debe ser un email válido',
        ];
    }

    public function attributes()
    {
        return [
            'funcionario_id'              => 'funcionario',
            'esquema_id'                  => 'cartola',
            'email'                       => 'correo'
        ];
    }
}
