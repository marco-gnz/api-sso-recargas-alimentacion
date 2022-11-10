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
}
