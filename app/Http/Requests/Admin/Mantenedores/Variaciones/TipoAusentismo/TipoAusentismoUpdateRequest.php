<?php

namespace App\Http\Requests\Admin\Mantenedores\Variaciones\TipoAusentismo;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class TipoAusentismoUpdateRequest extends FormRequest
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
            'tipo'          => ['nullable'],
            'codigo_sirh'   => ['nullable', Rule::unique('tipo_ausentismos', 'codigo_sirh')->ignore($this->id)],
            'nombre'        => ['required', Rule::unique('tipo_ausentismos', 'nombre')->ignore($this->id)],
            'sigla'         => ['required', Rule::unique('tipo_ausentismos', 'sigla')->ignore($this->id)]
        ];
    }

    public function messages()
    {
        return [
            'codigo_sirh.required'             => 'El :attribute es obligatorio',
            'codigo_sirh.unique'               => 'El :attribute ya existe en el sistema',

            'nombre.required'                 => 'El :attribute es obligatorio',
            'nombre.unique'                   => 'El :attribute ya existe en el sistema',

            'sigla.required'                 => 'La :attribute es obligatoria',
            'sigla.unique'                   => 'La :attribute ya existe en el sistema',
        ];
    }

    public function attributes()
    {
        return [
            'cod_sirh'      => 'cod. sirh',
            'nombre'        => 'nombre',
            'sigla'         => 'sigla'
        ];
    }
}
