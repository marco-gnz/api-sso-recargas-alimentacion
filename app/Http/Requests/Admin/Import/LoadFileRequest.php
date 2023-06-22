<?php

namespace App\Http\Requests\Admin\Import;

use Illuminate\Foundation\Http\FormRequest;

class LoadFileRequest extends FormRequest
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
            'codigo_recarga'    => ['required', 'exists:recargas,codigo'],
            'row_columnas'      => ['required'],
            'columnas'          => ['required'],
            'id_carga'          => ['required'],
            'file'              => ['required', 'file', 'mimes:xlsx,xls', 'max:10000']
        ];
    }
}
