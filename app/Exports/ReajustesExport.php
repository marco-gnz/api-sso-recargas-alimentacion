<?php

namespace App\Exports;

use App\Models\Reajuste;
use App\Models\ReajusteEstado;
use Carbon\Carbon;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\WithMapping;
use PhpOffice\PhpSpreadsheet\Shared\Date;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;

class ReajustesExport implements FromQuery, WithMapping, WithHeadings, WithTitle, ShouldAutoSize, WithStyles
{
    use Exportable;

    public function __construct($reajustes_id)
    {
        $this->reajustes_id = $reajustes_id;
    }

    public function styles(Worksheet $sheet)
    {
        $lastRow    = $sheet->getHighestRow();
        $lastColumn = $sheet->getHighestColumn();

        $styleArray = [
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                ],
            ],
            'alignment' => [
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
        ];

        $sheet->getStyle('A1:' . $lastColumn . $lastRow)->applyFromArray($styleArray);

        $sheet->getStyle('A1:' . $lastColumn . '1')->applyFromArray([
            'font' => [
                'bold' => true
            ],
        ]);

        return [];
    }

    public function title(): string
    {
        return 'Ajustes';
    }

    public function map($ajuste): array {
        return [
            $ajuste->funcionario->rut_completo,
            $ajuste->funcionario->apellidos,
            $ajuste->funcionario->nombres,
            Reajuste::TYPE_NOM[$ajuste->tipo_reajuste],
            $ajuste->tipoAusentismo ? $ajuste->tipoAusentismo->nombre : '',
            $ajuste->tipoIncremento ? $ajuste->tipoIncremento->nombre : '',
            $ajuste->incremento ? 'Incremento' : 'Rebaja',
            $ajuste->fecha_inicio ? Date::stringToExcel($ajuste->fecha_inicio) : '',
            $ajuste->fecha_termino ? Date::stringToExcel($ajuste->fecha_termino) : '',
            $ajuste->tipo_dias ? 'Hábiles' : 'Naturales',
            $ajuste->dias_periodo,
            $ajuste->dias_periodo_habiles,
            $ajuste->total_dias,
            $ajuste->valor_dia,
            $ajuste->monto_ajuste,
            $ajuste->tipo_carga ? 'Masivo' : 'Manual',
            $ajuste->observacion,
            ReajusteEstado::STATUS_NOM[$ajuste->last_status],
            Date::dateTimeToExcel($ajuste->created_at),
            $ajuste->userCreatedBy->abreNombres3(),

            $ajuste->recarga->codigo,
            $ajuste->recarga->monto_dia,
            $ajuste->recarga->monto_estimado,

            $ajuste->esquema->total_dias_cancelar,
            $ajuste->esquema->monto_total_cancelar,
        ];
    }

    public function columnFormats(): array
    {
        return [
            'H' => NumberFormat::FORMAT_DATE_XLSX15,
            'I' => NumberFormat::FORMAT_DATE_XLSX15,
            'S' => NumberFormat::FORMAT_DATE_DATETIME
        ];
    }

    public function headings(): array
    {
        return [
            'RUT',
            'APELLIDOS',
            'NOMBRES',
            'TIPO REAJUSTE',
            'TIPO AUSENTISMO',
            'TIPO INCREMENTO',
            'INCREMENTO/REBAJA',
            'FECHA INICIO',
            'FECHA TERMINO',
            'TIPO DIAS',
            'DIAS PERIODO',
            'DIAS PERIODO (HABILES)',
            'TOTAL DIAS',
            'VALOR DIA',
            'MONTO AJUSTE',
            'TIPO CARGA',
            'OBSERVACION',
            'ESTADO',
            'FECHA INGRESO',
            'USUARIO INGRESO',

            'CODIGO RECARGA',
            'MONTO DIA',
            'MONTO ESTIMADO',

            'DC CARTOLA',
            'MC CARTOLA'
        ];
    }

    public function query()
    {
        return Reajuste::query()
            ->whereIn('id', $this->reajustes_id);
    }
}
