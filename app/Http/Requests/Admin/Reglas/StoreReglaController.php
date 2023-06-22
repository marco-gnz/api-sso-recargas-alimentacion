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

            'reglas.*.active_tipo_dias_turnante'        => 'nullable',
            'reglas.*.tipo_dias_turnante'               => 'required_if:reglas.*.active_tipo_dias_turnante,1',

            'reglas.*.active_tipo_dias_no_turnante'     => 'nullable',
            'reglas.*.tipo_dias_no_turnante'            => 'required_if:reglas.*.active_tipo_dias_no_turnante,1',

            'reglas.*.meridiano_turnante'               => 'array|required_if:reglas.*.grupo_id,2',
            'reglas.*.meridiano_no_turnante'            => 'array|required_if:reglas.*.grupo_id,2',

            'reglas.*.hora_inicio_turnante_am'          => 'required_if:reglas.*.grupo_id,3',
            'reglas.*.hora_termino_turnante_am'         => 'required_if:reglas.*.grupo_id,3 ',
            'reglas.*.hora_inicio_turnante_pm'          => 'required_if:reglas.*.grupo_id,3',
            'reglas.*.hora_termino_turnante_pm'         => 'required_if:reglas.*.grupo_id,3 ',

            'reglas.*.hora_inicio_no_turnante_am'       => 'required_if:reglas.*.grupo_id,3',
            'reglas.*.hora_termino_no_turnante_am'      => 'required_if:reglas.*.grupo_id,3 ',
            'reglas.*.hora_inicio_no_turnante_pm'       => 'required_if:reglas.*.grupo_id,3',
            'reglas.*.hora_termino_no_turnante_pm'      => 'required_if:reglas.*.grupo_id,3 '
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

            'reglas.*.tipo_dias_turnante.required_if'           => 'El :attribute es obligatorio',
            'reglas.*.tipo_dias_no_turnante.required_if'        => 'El :attribute es obligatorio',

            'reglas.*.meridiano_turnante.required_if'           => 'El :attribute es obligatorio',
            'reglas.*.meridiano_no_turnante.required_if'        => 'El :attribute es obligatorio',

            'reglas.*.hora_inicio_turnante_am.required_if'         => 'La :attribute es obligatoria',
            'reglas.*.hora_termino_turnante_am.required_if'        => 'La :attribute es obligatoria',
            'reglas.*.hora_inicio_turnante_pm.required_if'         => 'La :attribute es obligatoria',
            'reglas.*.hora_termino_turnante_pm.required_if'        => 'La :attribute es obligatoria',

            'reglas.*.hora_inicio_no_turnante_am.required_if'      => 'La :attribute es obligatoria',
            'reglas.*.hora_termino_no_turnante_am.required_if'     => 'La :attribute es obligatoria',
            'reglas.*.hora_inicio_no_turnante_pm.required_if'      => 'La :attribute es obligatoria',
            'reglas.*.hora_termino_no_turnante_pm.required_if'     => 'La :attribute es obligatoria',
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
            'reglas.*.tipo_dias_turnante'       => 'tipo de días',
            'reglas.*.tipo_dias_no_turnante'    => 'tipo de días',
            'reglas.*.meridiano_turnante'       => 'meridiano T',
            'reglas.*.meridiano_no_turnante'    => 'meridiano NT',

            'reglas.*.hora_inicio_turnante_am'     => 'hora inicio T am',
            'reglas.*.hora_termino_turnante_am'    => 'hora término T am',
            'reglas.*.hora_inicio_turnante_pm'     => 'hora inicio T pm',
            'reglas.*.hora_termino_turnante_pm'    => 'hora término T pm',

            'reglas.*.hora_inicio_no_turnante_am'     => 'hora inicio NT am',
            'reglas.*.hora_termino_no_turnante_am'    => 'hora término NT am',
            'reglas.*.hora_inicio_no_turnante_pm'     => 'hora inicio NT pm',
            'reglas.*.hora_termino_no_turnante_pm'    => 'hora término NT pm',
        ];
    }
}
