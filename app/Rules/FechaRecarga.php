<?php

namespace App\Rules;

use Carbon\Carbon;
use Illuminate\Contracts\Validation\Rule;

class FechaRecarga implements Rule
{
    public function __construct($position, $fecha, $recarga)
    {
        $this->position = $position;
        $this->fecha    = $fecha;
        $this->recarga  = $recarga;
    }

    public $fecha;
    public $recarga;
    public $position;

    public function passes($attribute, $value)
    {
        $pasa = true;
        $fecha_request  = Carbon::instance(\PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($value));
        $new_fecha      = Carbon::parse($fecha_request)->format('Y-m-d');

        $tz                     = 'America/Santiago';
        $fecha_recarga_inicio   = Carbon::createFromDate($this->recarga->anio, $this->recarga->mes, '01', $tz);
        $fecha_recarga_termino  = Carbon::createFromDate($this->recarga->anio, $this->recarga->mes, '01', $tz);
        $fecha_recarga_termino  = $fecha_recarga_termino->endOfMonth();
        $fecha_recarga_inicio->format('Y-m-d');
        $fecha_recarga_termino->format('Y-m-d');

        if ($this->position) {
            //fecha_inico
            if ($new_fecha > $fecha_recarga_termino) {
                $pasa = false;
            }
        } else {
            if ($new_fecha < $fecha_recarga_inicio) {
                $pasa = false;
            }
        }

        return $pasa;
    }

    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message()
    {
        return 'Inconsistencia en fechas.';
    }
}
