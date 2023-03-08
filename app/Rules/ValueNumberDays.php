<?php

namespace App\Rules;

use Illuminate\Contracts\Validation\Rule;

class ValueNumberDays implements Rule
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
        $not_cero = true;

        if($value === 0){
            $not_cero = false;
        }

        return $not_cero;
    }

    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message()
    {
        return 'No es posible ingresar valores en 0.';
    }
}
