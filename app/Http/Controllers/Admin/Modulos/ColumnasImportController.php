<?php

namespace App\Http\Controllers\Admin\Modulos;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Http\Request;

class ColumnasImportController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth:sanctum']);
    }

    public $numerico    = 'Numérico';
    public $texto       = 'Texto';
    public $fecha       = 'yyyy-mm-dd';

    private function anio()
    {
        $anio   = Carbon::now()->format('Y');
        return $anio;
    }

    private function mes()
    {
        $mes   = Carbon::now()->addMonth(-1)->format('m');
        return $mes;
    }

    public function columasImportarFuncionarios()
    {
        $columnas   = [
            [
                'nombre_columna'        => 'rut',
                'formato'               => $this->numerico,
                'required'              => true,
                'descripcion'           => 'Rut de funcionario'
            ],
            [
                'nombre_columna'        => 'dv',
                'formato'               => $this->texto,
                'required'              => true,
                'descripcion'           => 'Dígito verificador'
            ],
            [
                'nombre_columna'        => 'nombres',
                'formato'               => $this->texto,
                'required'              => true,
                'descripcion'           => 'Nombre de funcionario'
            ],
            [
                'nombre_columna'        => 'apellidos',
                'formato'               => $this->texto,
                'required'              => true,
                'descripcion'           => 'Apellidos de funcionario'
            ],
            [
                'nombre_columna'        => 'email',
                'formato'               => $this->texto,
                'required'              => false,
                'descripcion'           => 'Dirección de correo electrónico'
            ],
            [
                'nombre_columna'        => 'codestablecimiento',
                'formato'               => $this->numerico,
                'required'              => true,
                'descripcion'           => 'Código en SIRH'
            ],
            [
                'nombre_columna'        => 'codunidad',
                'formato'               => $this->numerico,
                'required'              => true,
                'descripcion'           => 'Código en SIRH'
            ],
            [
                'nombre_columna'        => 'planta',
                'formato'               => $this->texto,
                'required'              => true,
                'descripcion'           => 'Nombre en SIRH'
            ],
            [
                'nombre_columna'        => 'codcargo',
                'formato'               => $this->numerico,
                'required'              => true,
                'descripcion'           => 'Código en SIRH'
            ]
        ];
        return $columnas;
    }

    public function columnasImportGrupoUno()
    {
        $columnas   = [
            [
                'nombre_columna'        => 'rut',
                'formato'               => $this->numerico,
                'required'              => true,
                'descripcion'           => 'Rut de funcionario'
            ],
            [
                'nombre_columna'        => 'dv',
                'formato'               => $this->texto,
                'required'              => true,
                'descripcion'           => 'Dígito verificador'
            ],
            [
                'nombre_columna'        => 'nombretipoausentismo',
                'formato'               => $this->texto,
                'required'              => true,
                'descripcion'           => 'Nombre de tipo de ausentismo'
            ],
            [
                'nombre_columna'        => 'fechainicio',
                'formato'               => $this->fecha,
                'required'              => true,
                'descripcion'           => 'Fecha de inicio ausentimo'
            ],
            [
                'nombre_columna'        => 'fechatermino',
                'formato'               => $this->fecha,
                'required'              => true,
                'descripcion'           => 'Fecha de término ausentimo'
            ]
        ];
        return $columnas;
    }

    public function columnasImportTurnos()
    {
        $columnas   = [
            [
                'nombre_columna'        => 'rut',
                'formato'               => $this->numerico,
                'required'              => true,
                'descripcion'           => 'Rut de funcionario'
            ],
            [
                'nombre_columna'        => 'dv',
                'formato'               => $this->texto,
                'required'              => true,
                'descripcion'           => 'Dígito verificador'
            ],
            [
                'nombre_columna'        => 'proceso',
                'formato'               => $this->texto,
                'required'              => true,
                'descripcion'           => 'Tipo de pago (Normal, Retroactivo, etc.)'
            ],
            [
                'nombre_columna'        => 'ano pago',
                'formato'               => $this->numerico,
                'required'              => true,
                'descripcion'           => "Año de pago. (Ej: {$this->anio()})"
            ],
            [
                'nombre_columna'        => 'mes pago',
                'formato'               => $this->numerico,
                'required'              => true,
                'descripcion'           => "Mes de pago. (Ej: {$this->mes()})"
            ],
            [
                'nombre_columna'        => 'calidad juridica',
                'formato'               => $this->texto,
                'required'              => true,
                'descripcion'           => 'Calidad juridica del funcionario'
            ],
            [
                'nombre_columna'        => 'estab',
                'formato'               => $this->numerico,
                'required'              => true,
                'descripcion'           => 'Código de establecimiento pago'
            ],
            [
                'nombre_columna'        => 'unidad',
                'formato'               => $this->texto,
                'required'              => true,
                'descripcion'           => 'Nombre de unidad'
            ],
            [
                'nombre_columna'        => 'planta',
                'formato'               => $this->texto,
                'required'              => true,
                'descripcion'           => 'Nombre de planta de funcionario'
            ],
            [
                'nombre_columna'        => 'asignacion tercer turno',
                'formato'               => $this->numerico,
                'required'              => true,
                'descripcion'           => 'Asignación de tercer turno'
            ],
            [
                'nombre_columna'        => 'bonificacion asignacion turno',
                'formato'               => $this->numerico,
                'required'              => true,
                'descripcion'           => 'Bonificación de asignación de turno'
            ],
            [
                'nombre_columna'        => 'asignacion cuarto turno',
                'formato'               => $this->numerico,
                'required'              => true,
                'descripcion'           => 'Asignación de cuarto turno'
            ]
        ];
        return $columnas;
    }
}
