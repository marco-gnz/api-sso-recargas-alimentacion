<?php

namespace App\Http\Requests\Admin\Mantenedores\Cargo;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateUnidadRequest extends FormRequest
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
            'id'            => ['required'],
            'tipo'          => ['required'],
            'cod_sirh'      => ['required', Rule::unique('unidads', 'cod_sirh')->ignore($this->id)],
            'nombre'        => ['required', Rule::unique('unidads', 'nombre')->ignore($this->id)],
        ];
    }

    public function messages()
    {
        return [
            'cod_sirh.required'             => 'El :attribute es obligatorio',
            'cod_sirh.unique'               => 'El :attribute ya existe en el sistema',

            'nombre.required'                 => 'El :attribute es obligatorio',
            'nombre.unique'                   => 'El :attribute ya existe en el sistema',
        ];
    }

    public function attributes()
    {
        return [
            'cod_sirh'      => 'cod. sirh',
            'nombre'        => 'nombre'
        ];
    }
}
