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
            'monto_dia'             => ['required', 'numeric']
        ];
    }

    public function messages()
    {
        return [
            'monto_dia.required'                => 'El :attribute es obligatorio',
            'monto_dia.numeric'                 => 'El :attribute debe ser un número válido',
        ];
    }

    public function attributes()
    {
        return [
            'monto_dia'             => 'monto por día'
        ];
    }
}
