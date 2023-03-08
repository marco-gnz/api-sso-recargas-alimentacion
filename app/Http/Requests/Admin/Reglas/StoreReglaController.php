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
            'reglas'                                    => 'present|array',

            'reglas.*.recarga_id'                       => 'nullable',

            'reglas.*.tipo_ausentismo_id'               => 'required',

            'reglas.*.grupo_id'                         => 'required',

            'reglas.*.turno'                            => 'required',

            'reglas.*.active'                           => 'required_if:reglas.*.grupo_id,2,3',

            'reglas.*.meridiano_turnante'               => 'array|required_if:reglas.*.grupo_id,2',
            'reglas.*.meridiano_no_turnante'            => 'array|required_if:reglas.*.grupo_id,2',

            'reglas.*.hora_inicio_turnante'             => 'required_if:reglas.*.grupo_id,3',
            'reglas.*.hora_termino_turnante'            => 'required_if:reglas.*.grupo_id,3 ',

            'reglas.*.hora_inicio_no_turnante'          => 'required_if:reglas.*.grupo_id,3',
            'reglas.*.hora_termino_no_turnante'         => 'required_if:reglas.*.grupo_id,3 '
        ];
    }

    public function messages()
    {
        return [
            'reglas.*.recarga_id.required'                      => 'La :attribute es obligatoria',

            'reglas.*.tipo_ausentismo_id.required'              => 'El :attribute es obligatorio',

            'reglas.*.grupo_id.required'                        => 'El :attribute es obligatorio',

            'reglas.*.turno.required'                           => 'El :attribute es obligatorio',

            'reglas.*.active.required_if'                       => 'La :attribute es obligatoria',

            'reglas.*.meridiano_turnante.required_if'           => 'El :attribute es obligatorio',
            'reglas.*.meridiano_no_turnante.required_if'        => 'El :attribute es obligatorio',

            'reglas.*.hora_inicio_turnante.required_if'         => 'La :attribute es obligatoria',
            'reglas.*.hora_termino_turnante.required_if'        => 'La :attribute es obligatoria',

            'reglas.*.hora_inicio_no_turnante.required_if'      => 'La :attribute es obligatoria',
            'reglas.*.hora_termino_no_turnante.required_if'     => 'La :attribute es obligatoria',
        ];
    }

    public function attributes()
    {
        return [
            'reglas.*.recarga_id'               => 'recarga',
            'reglas.*.tipo_ausentismo_id'       => 'tipo de ausentismo',
            'reglas.*.grupo_id'                 => 'grupo',
            'reglas.*.turno'                    => 'turno',
            'reglas.*.active'                   => 'regla',
            'reglas.*.meridiano_turnante'       => 'meridiano T',
            'reglas.*.meridiano_no_turnante'    => 'meridiano NT',
            'reglas.*.hora_inicio_turnante'     => 'hora inicio T',
            'reglas.*.hora_termino_turnante'    => 'hora término T',
            'reglas.*.hora_inicio_no_turnante'  => 'hora inicio NT',
            'reglas.*.hora_termino_no_turnante' => 'hora término NT',
        ];
    }
}
