<?php

namespace App\Http\Requests\Admin\Recargas;

use Illuminate\Foundation\Http\FormRequest;

class StoreRecargaRequest extends FormRequest
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
            'establecimiento_id'    => ['required'],
            'anio_beneficio'        => ['required'],
            'mes_beneficio'         => ['required'],
            'anio_calculo'          => ['required'],
            'mes_calculo'           => ['required'],
            'monto_dia'             => ['required', 'numeric']
        ];
    }

    public function messages()
    {
        return [
            'establecimiento_id.required'       => 'El :attribute es obligatorio',

            'fecha_beneficio.required'          => 'La :attribute es obligatoria',
            'fecha_beneficio.date'              => 'La :attribute debe ser una fecha válida',

            'fecha_calculo.required'            => 'La :attribute es obligatoria',
            'fecha_calculo.date'                => 'La :attribute debe ser una fecha válida',

            'monto_dia.required'                => 'El :attribute es obligatorio',
            'monto_dia.numeric'                 => 'El :attribute debe ser un número válido',
        ];
    }

    public function attributes()
    {
        return [
            'establecimiento_id'    => 'establecimiento',
            'fecha_beneficio'       => 'fecha de beneficio',
            'fecha_calculo'         => 'fecha para el cálculo',
            'monto_dia'             => 'monto por día'
        ];
    }
}
