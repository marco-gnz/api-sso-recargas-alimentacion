<?php

namespace App\Http\Requests\Admin\Reglas;

use Illuminate\Foundation\Http\FormRequest;

class StoreReglaController extends FormRequest
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
            'reglas'                            => 'present|array',

            'reglas.*.recarga_id'               => 'nullable | unique:reglas',

            'reglas.*.tipo_ausentismo_id'       => 'required',

            'reglas.*.grupo_id'                 => 'required',

            'reglas.*.active'                   => 'required_if:reglas.*.grupo_id,2,3',
            'reglas.*.meridiano'                => 'array|required_if:reglas.*.grupo_id,2',
            'reglas.*.hora_inicio'              => 'required_if:reglas.*.grupo_id,3',
            'reglas.*.hora_termino'             => 'required_if:reglas.*.grupo_id,3 '
        ];
    }

    public function messages()
    {
        return [
            'reglas.*.recarga_id.required'              => 'La :attribute es obligatoria',
            'reglas.*.recarga_id.unique'                => 'La :attribute ya existe en el sistema',

            'reglas.*.tipo_ausentismo_id.required'      => 'El :attribute es obligatorio',

            'reglas.*.grupo_id.required'                  => 'El :attribute es obligatorio',

            'reglas.*.active.required_if'               => 'La :attribute es obligatoria',

            'reglas.*.meridiano.required_if'            => 'El :attribute es obligatorio',

            'reglas.*.hora_inicio.required_if'          => 'La :attribute es obligatoria',
            'reglas.*.hora_termino.required_if'         => 'La :attribute es obligatoria',
        ];
    }

    public function attributes()
    {
        return [
            'reglas.*.recarga_id'               => 'recarga',
            'reglas.*.tipo_ausentismo_id'       => 'tipo de ausentismo',
            'reglas.*.grupo_id'                 => 'grupo',
            'reglas.*.active'                   => 'regla',
            'reglas.*.meridiano'                => 'meridiano',
            'reglas.*.hora_inicio'              => 'hora',
            'reglas.*.hora_termino'             => 'hora',
        ];
    }
}
