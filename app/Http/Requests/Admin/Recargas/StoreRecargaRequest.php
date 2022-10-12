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
            'fecha'                 => ['required', 'date'],
            'anio'                  => ['required'],
            'mes'                   => ['required'],
            'total_dias_habiles'    => ['required', 'numeric'],
            'monto_dia'             => ['required', 'numeric']
        ];
    }

    public function messages()
    {
        return [
            'establecimiento_id.required'       => 'El :attribute es obligatorio',

            'fecha.required'                    => 'La :attribute es obligatoria',
            'fecha.date'                        => 'La :attribute debe ser una fecha válida',

            'total_dias_habiles.required'       => 'El :attribute es obligatorio',
            'total_dias_habiles.numeric'        => 'El :attribute debe ser un número válido',

            'monto_dia.required'                => 'El :attribute es obligatorio',
            'monto_dia.numeric'                 => 'El :attribute debe ser un número válido',
        ];
    }

    public function attributes()
    {
        return [
            'establecimiento_id'    => 'establecimiento',
            'fecha'                 => 'fecha',
            'total_dias_habiles'    => 'total de días habiles',
            'monto_dia'             => 'monto por día'
        ];
    }
}
