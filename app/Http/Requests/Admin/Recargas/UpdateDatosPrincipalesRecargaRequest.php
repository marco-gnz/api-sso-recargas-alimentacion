<?php

namespace App\Http\Requests\Admin\Recargas;

use Illuminate\Foundation\Http\FormRequest;

class UpdateDatosPrincipalesRecargaRequest extends FormRequest
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
            'total_dias_habiles'    => ['required', 'numeric'],
            'monto_dia'             => ['required', 'numeric']
        ];
    }

    public function messages()
    {
        return [
            'total_dias_habiles.required'       => 'El :attribute es obligatorio',
            'total_dias_habiles.numeric'        => 'El :attribute debe ser un número válido',

            'monto_dia.required'                => 'El :attribute es obligatorio',
            'monto_dia.numeric'                 => 'El :attribute debe ser un número válido',
        ];
    }

    public function attributes()
    {
        return [
            'total_dias_habiles'    => 'total de días habiles',
            'monto_dia'             => 'monto por día'
        ];
    }
}
