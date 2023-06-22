<?php

namespace App\Http\Requests\Admin\Export;

use Illuminate\Foundation\Http\FormRequest;

class PlanillaExportRequest extends FormRequest
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
            'codigo'        => ['required', 'exists:recargas,codigo'],
            'campos_id'     => ['required'],
            'campos_slug'   => 'required'
        ];
    }

    public function messages()
    {
        return [
            'codigo.required'             => 'El :attribute es obligatorio',
            'codigo.exists'               => 'El :attribute no existe en los registros',

            'campos_id.required'             => 'El :attribute es obligatorio',
        ];
    }

    public function attributes()
    {
        return [
            'campos_id'              => 'campo',
        ];
    }
}
