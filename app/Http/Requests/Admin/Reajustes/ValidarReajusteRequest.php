<?php

namespace App\Http\Requests\Admin\Reajustes;

use Illuminate\Foundation\Http\FormRequest;

class ValidarReajusteRequest extends FormRequest
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
            'aprobar'       => ['required'],
            'observacion'   => ['required_if:aprobar,false']
        ];
    }

    public function messages()
    {
        return [
            'observacion.required_if'   => 'La :attribute es obligatoria',
        ];
    }

    public function attributes()
    {
        return [
            'observacion'   => 'observación'
        ];
    }
}
