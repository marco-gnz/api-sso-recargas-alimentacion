<?php

namespace App\Rules;

use Illuminate\Contracts\Validation\Rule;

class RutValidateRule implements Rule
{
    /**
     * Create a new rule instance.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Determine if the validation rule passes.
     *
     * @param  string  $attribute
     * @param  mixed  $value
     * @return bool
     */
    public function passes($attribute, $value)
    {
        $value  = preg_replace('/[^k0-9]/i', '', $value);
        $dv     = substr($value, -1);
        $numero = substr($value, 0, strlen($value) - 1);
        $i      = 2;
        $suma   = 0;
        foreach (array_reverse(str_split($numero)) as $v) {
            if ($i == 8)
                $i = 2;

                if(is_numeric($v)){
                    $suma += $v * $i;
                    ++$i;
                }
        }

        $dvr = 11 - ($suma % 11);

        if ($dvr == 11)
            $dvr = 0;
        if ($dvr == 10)
            $dvr = 'K';

        if ((string)$dvr == strtoupper($dv))
            return true;
        else
            return false;
            //19270290
    }

    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message()
    {
        return 'Rut incorrecto, por favor verificar. Verificado con aritmética Módulo 11.';
    }
}
