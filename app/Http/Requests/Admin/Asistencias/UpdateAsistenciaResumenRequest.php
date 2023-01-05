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
        ];
    }

    public function messages()
    {
        return [
            'tipo_asistencia_turno_id.required'       => 'El :attribute es obligatorio',
        ];
    }

    public function attributes()
    {
        return [
            'tipo_asistencia_turno_id'    => 'tipo de turno',
        ];
    }
}
