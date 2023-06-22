<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePasswordUserRequest extends FormRequest
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
            'password'              => ['required'],
            'new_password'          => ['required', 'min:3'],
            'confirm_new_password'  => ['required', 'min:3', 'same:new_password']
        ];
    }

    public function messages()
    {
        return [
            'password.required'                     => 'La :attribute es obligatoria',

            'new_password.required'                 => 'La :attribute es obligatoria',
            'new_password.min'                      => 'La :attribute debe tener mínimo :min carácteres',

            'confirm_new_password.required'         => 'La :attribute es obligatoria',
            'confirm_new_password.min'              => 'La :attribute debe tener mínimo :min carácteres',
            'confirm_new_password.same'             => 'La :attribute debe ser idéntica a la nueva contraseña',

        ];
    }

    public function attributes()
    {
        return [
            'password'                  => 'contraseña',
            'new_password'              => 'nueva contraseña',
            'confirm_new_password'      => 'confirmación de nueva contraseña',
        ];
    }
}
