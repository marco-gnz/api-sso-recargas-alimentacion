<?php

namespace App\Http\Requests\Admin\Reajustes;

use App\Rules\TipeValueNumberDays;
use App\Rules\ValueNumberDays;
use Illuminate\Foundation\Http\FormRequest;

class StoreReajusteRequest extends FormRequest
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
            'recarga_codigo'        => ['required', 'exists:recargas,codigo'],
            'user_id'               => ['required', 'exists:users,id'],
            'fecha_inicio'          => ['required', 'date', 'before_or_equal:fecha_termino'],
            'fecha_termino'         => ['required', 'date', 'after_or_equal:fecha_inicio'],
            'incremento'            => ['required'],
            'tipo_reajuste'         => ['required'],
            'valor_dia'             => ['required_if:tipo_reajuste,1', 'numeric', 'gt:0', 'min:0'],
            'monto_ajuste'          => ['required_if:tipo_reajuste,1', 'numeric', new TipeValueNumberDays($this->incremento), new ValueNumberDays],
            'tipo_ausentismo_id'    => ['required_if:incremento,0'],
            'tipo_incremento_id'    => ['required_if:incremento,1'],
            'dias'                  => ['required', 'numeric', new TipeValueNumberDays($this->incremento), new ValueNumberDays],
            'observacion'           => ['required', 'max:255'],
        ];
    }

    public function messages()
    {
        return [
            'fecha_inicio.required'             => 'La :attribute es obligatoria',
            'fecha_inicio.date'                 => 'La :attribute debe ser una fecha válida',
            'fecha_inicio.before_or_equal'      => 'La :attribute debe ser anterior a fecha de término',

            'fecha_termino.required'            => 'La :attribute es obligatoria',
            'fecha_termino.date'                => 'La :attribute debe ser una fecha válida',
            'fecha_termino.after_or_equal'      => 'La :attribute debe ser superior a fecha de inicio',

            'valor_dia.required_if'             => 'El :attribute es obligatorio',
            'valor_dia.numeric'                 => 'El :attribute debe ser numérico',
            'valor_dia.gt'                      => 'El :attribute no puede ser valor 0',
            'valor_dia.min'                     => 'El :attribute debe ser mayor a :min',

            'incremento.required'               => 'El :attribute es obligatorio',

            'tipo_ausentismo_id.required_if'    => 'El :attribute es obligatorio',

            'tipo_incremento_id.required_if'    => 'El :attribute es obligatorio',

            'dias.required'                     => 'El :attribute es obligatorio',
            'dias.numeric'                      => 'El :attribute debe ser numérico',

            'observacion.required'              => 'La :attribute es obligatoria',
            'observacion.max'                   => 'La :attribute admite hasta :max caracteres',
        ];
    }

    public function attributes()
    {
        return [
            'fecha_inicio'              => 'fecha inicio',
            'fecha_termino'             => 'fecha término',
            'incremento'                => 'tipo de reajuste',
            'tipo_ausentismo_id'        => 'tipo de ausentismo',
            'tipo_incremento_id'        => 'tipo de incremento',
            'dias'                      => 'días',
            'observacion'               => 'observación'
        ];
    }
}
