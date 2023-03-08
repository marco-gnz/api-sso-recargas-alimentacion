<?php

namespace App\Http\Requests\Admin\Asistencias;

use Illuminate\Foundation\Http\FormRequest;

class UpdateAsistenciaResumenRequest extends FormRequest
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
            'tipo_asistencia_turno_id'    => ['required'],
            'observacion'                 => ['required', 'max:255']
        ];
    }

    public function messages()
    {
        return [
            'tipo_asistencia_turno_id.required'     => 'El :attribute es obligatorio',

            'observacion.required'                  => 'La :attribute es obligatoria',
            'observacion.max'                       => 'La :attribute requiere :max caracteres máximo',
        ];
    }

    public function attributes()
    {
        return [
            'tipo_asistencia_turno_id'    => 'tipo de turno',
            'observacion'                 => 'observación'
        ];
    }
}
