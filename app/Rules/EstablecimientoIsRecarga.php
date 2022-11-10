<?php

namespace App\Rules;

use App\Models\Establecimiento;
use Illuminate\Contracts\Validation\Rule;

class EstablecimientoIsRecarga implements Rule
{
    public function __construct($establecimiento_recarga)
    {
        $this->establecimiento_recarga = $establecimiento_recarga;
    }

    public $establecimiento_recarga;

    public function passes($attribute, $value)
    {
        $equals = true;
        $establecimiento_excel = Establecimiento::where('cod_sirh', $value)->first();

        if ($establecimiento_excel) {
            if ($this->establecimiento_recarga->id != $establecimiento_excel->id) {
                $equals = false;
            }
        }else{
            $equals = false;
        }
        return $equals;
    }

    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message()
    {
        return 'El establecimiento debe ser '.$this->establecimiento_recarga->sigla;
    }
}
