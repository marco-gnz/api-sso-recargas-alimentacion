<?php

namespace App\Http\Controllers\Admin\Modulos;

use App\Http\Controllers\Controller;
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

    public function columasImportarFuncionarios()
    {
        $columnas   = [
            [
                'nombre_columna'        => 'RUT',
                'formato'               => $this->numerico,
                'required'              => true,
                'descripcion'           => 'Rut de funcionario'
            ],
            [
                'nombre_columna'        => 'DV',
                'formato'               => $this->texto,
                'required'              => true,
                'descripcion'           => 'Dígito verificador'
            ],
            [
                'nombre_columna'        => 'NOMBRES',
                'formato'               => $this->texto,
                'required'              => true,
                'descripcion'           => 'Nombre de funcionario'
            ],
            [
                'nombre_columna'        => 'APELLIDOS',
                'formato'               => $this->texto,
                'required'              => true,
                'descripcion'           => 'Apellidos de funcionario'
            ],
            [
                'nombre_columna'        => 'EMAIL',
                'formato'               => $this->texto,
                'required'              => false,
                'descripcion'           => 'Dirección de correo electrónico'
            ],
            [
                'nombre_columna'        => 'CODESTABLECIMIENTO',
                'formato'               => $this->numerico,
                'required'              => true,
                'descripcion'           => 'Código en SIRH'
            ],
            [
                'nombre_columna'        => 'CODUNIDAD',
                'formato'               => $this->numerico,
                'required'              => true,
                'descripcion'           => 'Código en SIRH'
            ],
            [
                'nombre_columna'        => 'PLANTA',
                'formato'               => $this->texto,
                'required'              => true,
                'descripcion'           => 'Nombre en SIRH'
            ],
            [
                'nombre_columna'        => 'CODCARGO',
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
                'nombre_columna'        => 'RUT',
                'formato'               => $this->numerico,
                'required'              => true,
                'descripcion'           => 'Rut de funcionario'
            ],
            [
                'nombre_columna'        => 'DV',
                'formato'               => $this->texto,
                'required'              => true,
                'descripcion'           => 'Dígito verificador'
            ],
            [
                'nombre_columna'        => 'NOMBRETIPOAUSENTISMO',
                'formato'               => $this->texto,
                'required'              => true,
                'descripcion'           => 'Nombre de tipo de ausentismo'
            ],
            [
                'nombre_columna'        => 'FECHAINICIO',
                'formato'               => $this->fecha,
                'required'              => true,
                'descripcion'           => 'Fecha de inicio ausentimo'
            ],
            [
                'nombre_columna'        => 'FECHATERMINO',
                'formato'               => $this->fecha,
                'required'              => true,
                'descripcion'           => 'Fecha de término ausentimo'
            ]
        ];
        return $columnas;
    }
}
