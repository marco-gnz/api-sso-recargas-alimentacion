<?php

namespace App\Rules;

use Illuminate\Contracts\Validation\Rule;

class TipeValueDateContrato implements Rule
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
        $error = true;
        if ((!is_numeric($value) && $value != '00/00/0000')) {
            $error = false;
        }

        return $error;
    }

    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message()
    {
        return 'Error en fecha.';
    }
}
