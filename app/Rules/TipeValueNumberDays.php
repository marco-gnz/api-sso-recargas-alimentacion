<?php

namespace App\Rules;

use Illuminate\Contracts\Validation\Rule;

class TipeValueNumberDays implements Rule
{
    /**
     * Create a new rule instance.
     *
     * @return void
     */
    public function __construct($incremento)
    {
        $this->incremento = $incremento;
    }

    public $incremento;

    /**
     * Determine if the validation rule passes.
     *
     * @param  string  $attribute
     * @param  mixed  $value
     * @return bool
     */
    public function passes($attribute, $value)
    {
        $if_passing = true;
        if ($this->incremento && $value < 0) {
            $if_passing = false;
        }else if(!$this->incremento && $value > 0){
            $if_passing = false;
        }
        return $if_passing;
    }

    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message()
    {
        $valor = $this->incremento ? 'positivo' : 'negativo';
        return "Valor debe ser {$valor}";
    }
}
