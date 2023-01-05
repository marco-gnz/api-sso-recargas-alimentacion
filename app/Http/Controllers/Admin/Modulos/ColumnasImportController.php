<?php

namespace App\Http\Controllers\Admin\Modulos;

use App\Http\Controllers\Controller;
use App\Models\Recarga;
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
    public $fecha_2     = 'formato fecha excel (Ej: dd-mm-yyyy)';

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

    public function transformDateExcel($number)
    {
        $format     = Carbon::parse($number)->format('Y-m-d');
        $str_date   = strtotime($format);
        $excel_date = floatval(25569 + $str_date / 86400);

        $excel_date = floor($excel_date);

        return $excel_date;
    }

    public function columnasImportAsistencia($codigo)
    {
        $recarga    = Recarga::where('codigo', $codigo)->first();
        $columnas   = [];

        if ($recarga) {
            $tz         = 'America/Santiago';
            $inicio     = Carbon::createFromDate($recarga->anio, $recarga->mes, '01', $tz);
            $termino    = Carbon::createFromDate($recarga->anio, $recarga->mes, '01', $tz)->endOfMonth();

            $inicio     = $inicio->format('Y-m-d');
            $termino    = $termino->format('Y-m-d');
            $columnas   = [
                [
                    'nombre_columna'        => 'rut',
                    'formato'               => $this->numerico,
                    'formato_excel'         => null,
                    'required'              => true,
                    'descripcion'           => 'Rut de funcionario',
                    'disabled'              => false
                ],
                [
                    'nombre_columna'        => 'dv',
                    'formato'               => $this->texto,
                    'formato_excel'         => null,
                    'required'              => true,
                    'descripcion'           => 'Dígito verificador',
                    'disabled'              => false
                ],
                [
                    'nombre_columna'        => 'codestablecimiento',
                    'formato'               => $this->numerico,
                    'formato_excel'         => null,
                    'required'              => true,
                    'descripcion'           => 'Código de establecimiento funcionario',
                    'disabled'              => false
                ],
            ];
            for ($i = $inicio; $i <= $termino; $i++) {
                $i_format       = Carbon::parse($i)->format('d-m-Y');
                $format_excel   = $this->transformDateExcel($i_format);
                $data =
                    [
                        'nombre_columna'        => $i_format,
                        'formato'               => $this->fecha_2,
                        'formato_excel'         => (int)$format_excel,
                        'required'              => true,
                        'descripcion'           => "Corresponde a {$i_format}",
                        'disabled'              => true
                    ];

                array_push($columnas, $data);
            }
        }
        return $columnas;
    }

    public function columnasResumenAsistencia($codigo)
    {
        $recarga    = Recarga::where('codigo', $codigo)->first();
        $columnas   = [];

        if ($recarga) {
            $tz         = 'America/Santiago';
            $inicio     = Carbon::createFromDate($recarga->anio, $recarga->mes, '01', $tz);
            $termino    = Carbon::createFromDate($recarga->anio, $recarga->mes, '01', $tz)->endOfMonth();

            $inicio     = $inicio->format('Y-m-d');
            $termino    = $termino->format('Y-m-d');
            for ($i = $inicio; $i <= $termino; $i++) {
                $i_format       = Carbon::parse($i)->format('d-m-Y');
                $format_excel   = $this->transformDateExcel($i_format);
                $data =
                    [
                        'nombre_columna'        => Carbon::parse($i_format)->format('d'),
                        'formato'               => $this->fecha_2,
                        'formato_excel'         => (int)$format_excel,
                        'required'              => true,
                        'descripcion'           => "Corresponde a {$i_format}",
                        'disabled'              => true
                    ];

                array_push($columnas, $data);
            }
        }
        return $columnas;
    }
}
