<?php

namespace App\Http\Controllers\Admin\Modulos;

use App\Http\Controllers\Controller;
use App\Models\Hora;
use App\Models\Ley;
use App\Models\Meridiano;
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
    public $fecha_2     = 'Formato fecha excel (Ej: dd-mm-yyyy)';
    public $hora        = 'H:m:s';

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
        $first_ley  = Ley::first();
        $first_hora = Hora::first();
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
            ],
            [
                'nombre_columna'        => 'ley',
                'formato'               => $this->texto,
                'required'              => true,
                'descripcion'           => $first_ley != null ? "(Ej: {$first_ley->nombre})" : 'Sin datos'
            ],
            [
                'nombre_columna'        => 'horas',
                'formato'               => $this->numerico,
                'required'              => true,
                'descripcion'           => $first_hora != null ? "(Ej: {$first_hora->nombre})" : 'Sin datos'
            ],
            [
                'nombre_columna'        => 'fecha inicio contrato',
                'formato'               => $this->fecha_2,
                'required'              => true,
                'descripcion'           => "Fecha de inicio contrato"
            ],
            [
                'nombre_columna'        => 'fecha termino contrato',
                'formato'               => $this->fecha_2,
                'required'              => true,
                'descripcion'           => 'Fecha de término contrato'
            ],
            [
                'nombre_columna'        => 'fecha de alejamiento',
                'formato'               => $this->fecha_2,
                'required'              => true,
                'descripcion'           => 'Fecha de alejamiento de contrato'
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
                'nombre_columna'        => 'TIPO DE AUSENTISMO',
                'formato'               => $this->texto,
                'required'              => true,
                'descripcion'           => 'Nombre de tipo de ausentismo'
            ],
            [
                'nombre_columna'        => 'F.INICIO',
                'formato'               => $this->fecha,
                'required'              => true,
                'descripcion'           => 'Fecha de inicio ausentimo'
            ],
            [
                'nombre_columna'        => 'F.TERMINO',
                'formato'               => $this->fecha,
                'required'              => true,
                'descripcion'           => 'Fecha de término ausentimo'
            ]
        ];
        return $columnas;
    }

    public function columnasImportGrupoDos()
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
                'nombre_columna'        => 'TIPO DE AUSENTISMO',
                'formato'               => $this->texto,
                'required'              => true,
                'descripcion'           => 'Nombre de tipo de ausentismo'
            ],
            [
                'nombre_columna'        => 'F.INICIO',
                'formato'               => $this->fecha,
                'required'              => true,
                'descripcion'           => 'Fecha de inicio ausentimo'
            ],
            [
                'nombre_columna'        => 'F.TERMINO',
                'formato'               => $this->fecha,
                'required'              => true,
                'descripcion'           => 'Fecha de término ausentimo'
            ],
            [
                'nombre_columna'        => 'TOTAL AUSENTISMO',
                'formato'               => $this->numerico,
                'required'              => false,
                'descripcion'           => 'Ej. -0.5, -1,  -1.5, -2, etc.'
            ],
            [
                'nombre_columna'        => 'MERIDIANO AUSENTISMO',
                'formato'               => $this->texto,
                'required'              => true,
                'descripcion'           => Meridiano::all()->pluck('codigo')->implode(' - ')
            ]
        ];
        return $columnas;
    }

    public function columnasImportGrupoTres()
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
                'nombre_columna'        => 'TIPO DE AUSENTISMO',
                'formato'               => $this->texto,
                'required'              => true,
                'descripcion'           => 'Nombre de tipo de ausentismo'
            ],
            [
                'nombre_columna'        => 'F.INICIO',
                'formato'               => $this->fecha,
                'required'              => true,
                'descripcion'           => 'Fecha de inicio ausentimo'
            ],
            [
                'nombre_columna'        => 'F.TERMINO',
                'formato'               => $this->fecha,
                'required'              => true,
                'descripcion'           => 'Fecha de término ausentimo'
            ],
            [
                'nombre_columna'        => 'H.INICIO',
                'formato'               => $this->hora,
                'required'              => true,
                'descripcion'           => 'Hora de inicio ausentimo. Ej. 11:00'
            ],
            [
                'nombre_columna'        => 'H.TERMINO',
                'formato'               => $this->hora,
                'required'              => true,
                'descripcion'           => 'Hora de término ausentimo. Ej. 16:00'
            ],
            /* [
                'nombre_columna'        => 'total horas',
                'formato'               => $this->numerico,
                'required'              => true,
                'descripcion'           => 'Ej. 1, 5, 9, etc.'
            ] */
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
                'nombre_columna'        => 'folio',
                'formato'               => $this->texto,
                'required'              => false,
                'descripcion'           => 'N° de folio'
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
                'descripcion'           => "Año de pago."
            ],
            [
                'nombre_columna'        => 'mes pago',
                'formato'               => $this->numerico,
                'required'              => true,
                'descripcion'           => "Mes de pago"
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
            $inicio     = Carbon::createFromDate($recarga->anio_beneficio, $recarga->mes_beneficio, '01', $tz);
            $termino    = Carbon::createFromDate($recarga->anio_beneficio, $recarga->mes_beneficio, '01', $tz)->endOfMonth();

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
            $inicio     = Carbon::createFromDate($recarga->anio_beneficio, $recarga->mes_beneficio, '01', $tz);
            $termino    = Carbon::createFromDate($recarga->anio_beneficio, $recarga->mes_beneficio, '01', $tz)->endOfMonth();

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
                        'disabled'              => true,
                        'is_week_day'           => Carbon::parse($i_format)->isWeekend()
                    ];

                array_push($columnas, $data);
            }
        }
        return $columnas;
    }

    public function columnasImportViaticos()
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
                'nombre_columna'        => 'fecha inicio',
                'formato'               => $this->fecha,
                'required'              => true,
                'descripcion'           => 'Fecha inicio de viático'
            ],
            [
                'nombre_columna'        => 'fecha termino',
                'formato'               => $this->fecha,
                'required'              => true,
                'descripcion'           => 'Fecha término de viático'
            ],
            [
                'nombre_columna'        => 'jornada',
                'formato'               => $this->texto,
                'required'              => true,
                'descripcion'           => 'Jornada (MAÑANA, TODO EL DÍA, NOCHE, ETC.)'
            ],
            [
                'nombre_columna'        => 'tipo resolucion',
                'formato'               => $this->texto,
                'required'              => true,
                'descripcion'           => 'Tipo de resolución (EXENTA - DECRETO)'
            ],
            [
                'nombre_columna'        => 'numero resolucion',
                'formato'               => $this->numerico,
                'required'              => true,
                'descripcion'           => "Número de resolución"
            ],
            [
                'nombre_columna'        => 'fecha resolucion',
                'formato'               => $this->fecha,
                'required'              => true,
                'descripcion'           => 'Fecha de resolución'
            ],
            [
                'nombre_columna'        => 'tipo comision',
                'formato'               => $this->texto,
                'required'              => true,
                'descripcion'           => 'Tipo de comisión (Ej. Servicios, Capacitación, etc.)'
            ],
            [
                'nombre_columna'        => 'motivo',
                'formato'               => $this->texto,
                'required'              => true,
                'descripcion'           => 'Motivo de viático (Ej. TRASLADO DE PACIENTE, ATENCION POSTA SALUD RURAL, ETC.)'
            ],
            [
                'nombre_columna'        => 'valor viatico',
                'formato'               => $this->numerico,
                'required'              => true,
                'descripcion'           => "Valor de viático"
            ],
        ];
        return $columnas;
    }
}
