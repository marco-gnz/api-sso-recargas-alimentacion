<?php

namespace App\Http\Requests\Admin\Feriados;

use Illuminate\Foundation\Http\FormRequest;

class StoreFeriadosRequest extends FormRequest
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
            'codigo_recarga'                => ['required'],

            'feriados'                      => ['present', 'array'],

            'feriados.*.fecha'              => ['required', 'date'],
            'feriados.*.nombre'             => ['required'],
            'feriados.*.irrenunciable'      => ['required'],
            'feriados.*.tipo'               => ['required'],
            'feriados.*.observacion'        => ['nullable'],
        ];
    }
}
