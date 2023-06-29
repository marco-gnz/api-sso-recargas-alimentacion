<?php

namespace App\Exports\Esquemas;

use App\Models\Ausentismo;
use App\Models\Esquema;
use App\Models\Recarga;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\WithMapping;
use PhpOffice\PhpSpreadsheet\Shared\Date;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;

class EsquemasPlanillaExport implements FromQuery, WithMapping, WithHeadings, WithColumnFormatting, WithTitle
{
    use Exportable;

    private $id_recarga;
    private $fields;
    private $campos_slug;
    private $campos_nombre;

    public function __construct($id_recarga, $campos_id, $campos_slug, $campos_nombre)
    {
        $this->id_recarga    = $id_recarga;
        $this->campos_id     = $campos_id;
        $this->campos_slug   = $campos_slug;
        $this->campos_nombre = $campos_nombre;
    }

    public function title(): string
    {
        return 'planilla_resumen';
    }

    public function columnFormats(): array
    {
        return [
            /* 'M' => NumberFormat::NEW_FORMAT, */];
    }

    private function reglasRecarga()
    {
        $recarga = Recarga::with('reglas')->find($this->id_recarga);

        $reglas = $recarga->reglas()->with('tipoAusentismo')->orderBy('id', 'desc')->get()->unique('tipo_ausentismo_id');

        return $reglas;
    }

    public function map($esquema): array
    {
        $recarga = Recarga::with('reglas')->find($this->id_recarga);
        $data = [];
        foreach ($this->campos_id as $key => $field) {
            $name = $esquema[$field] ? $esquema[$field] : null;
            if (($name) && ($field === 'es_turnante')) {
                $name = Esquema::TURNANTE_NOM[$esquema[$field]];
            }
            if (($name) && ($field === 'fecha_alejamiento')) {
                $name = $esquema[$field] ? 'Si' : 'No';
            }
            array_push($data, $name);
        }


        $reglas = $this->reglasRecarga();

        foreach ($reglas as $regla) {
            if ((in_array('ausentismos_grupo_uno', $this->campos_id)) && ($regla->grupo_id === 1)) {
                if ($esquema['es_turnante'] === 2) {
                    $total = DB::table('ausentismos')->where('esquema_id', $esquema['id'])->where('user_id', $esquema['user_id'])->where('tipo_ausentismo_id', $regla->tipoAusentismo->id)->sum('total_dias_habiles_ausentismo_periodo');
                    $total = $total - $esquema['total_dias_feriados_grupo_uno'];

                    $total = $total > 0 ? $total : 0;
                } else {
                    $total = DB::table('ausentismos')->where('esquema_id', $esquema['id'])->where('user_id', $esquema['user_id'])->where('tipo_ausentismo_id', $regla->tipoAusentismo->id)->sum('total_dias_ausentismo');
                }
                array_push($data, $total);
            }

            if ((in_array('ausentismos_grupo_dos', $this->campos_id)) && ($regla->grupo_id === 2)) {
                if ($esquema['es_turnante'] === 2) {
                    $total = DB::table('ausentismos')->where('esquema_id', $esquema['id'])->where('user_id', $esquema['user_id'])->where('tipo_ausentismo_id', $regla->tipoAusentismo->id)->whereIn('meridiano_id', [2, 3])->sum('total_dias_habiles_ausentismo_periodo');
                    $total = $total - $esquema['total_dias_feriados_grupo_dos'];

                    $total = $total > 0 ? $total : 0;
                } else {
                    $total = DB::table('ausentismos')->where('esquema_id', $esquema['id'])->where('user_id', $esquema['user_id'])->where('tipo_ausentismo_id', $regla->tipoAusentismo->id)->whereIn('meridiano_id', [2, 3])->sum('total_dias_ausentismo');
                }

                array_push($data, $total);
            }

            if ((in_array('ausentismos_grupo_tres', $this->campos_id)) && ($regla->grupo_id === 3)) {
                if ($esquema['es_turnante'] === 2) {
                    $total = DB::table('ausentismos')->where('esquema_id', $esquema['id'])->where('user_id', $esquema['user_id'])->where('tipo_ausentismo_id', $regla->tipoAusentismo->id)
                        ->whereNotNull('regla_id')
                        ->sum('total_dias_habiles_ausentismo_periodo');
                    $total = $total - $esquema['total_dias_feriados_grupo_dos'];

                    $total = $total > 0 ? $total : 0;
                } else {
                    $total = DB::table('ausentismos')->where('esquema_id', $esquema['id'])->where('user_id', $esquema['user_id'])->where('tipo_ausentismo_id', $regla->tipoAusentismo->id)
                        ->whereNotNull('regla_id')
                        ->sum('total_dias_ausentismo');
                }
                array_push($data, $total);
            }
        }
        return $data;
    }

    public function headings(): array
    {
        $recarga = Recarga::with('reglas')->find($this->id_recarga);

        foreach ($this->campos_nombre as $key => $value) {
            if ($value === 'Ausentismos grupo 1') {
                unset($this->campos_nombre[$key]);
                $tipos_de_ausentismo = $recarga->reglas()->with('tipoAusentismo')->where('grupo_id', 1)->orderBy('id', 'desc')->get()->unique('tipo_ausentismo_id');
                foreach ($tipos_de_ausentismo as $tipo) {
                    array_push($this->campos_nombre, $tipo->tipoAusentismo->nombre);
                }
            }
            if ($value === 'Ausentismos grupo 2') {
                unset($this->campos_nombre[$key]);

                $tipos_de_ausentismo = $recarga->reglas()->with('tipoAusentismo')->where('grupo_id', 2)->orderBy('id', 'desc')->get()->unique('tipo_ausentismo_id');
                foreach ($tipos_de_ausentismo as $tipo) {
                    array_push($this->campos_nombre, $tipo->tipoAusentismo->nombre);
                }
            }
            if ($value === 'Ausentismos grupo 3') {
                unset($this->campos_nombre[$key]);

                $tipos_de_ausentismo = $recarga->reglas()->with('tipoAusentismo')->where('grupo_id', 3)->orderBy('id', 'desc')->get()->unique('tipo_ausentismo_id');
                foreach ($tipos_de_ausentismo as $tipo) {
                    array_push($this->campos_nombre, $tipo->tipoAusentismo->nombre);
                }
            }
        }

        return $this->campos_nombre;
    }

    public function query()
    {
        foreach ($this->campos_slug as $key => $value) {
            if ($value === 'esquemas.ausentismos_grupo_uno') {
                unset($this->campos_slug[$key]);
            }
            if ($value === 'esquemas.ausentismos_grupo_dos') {
                unset($this->campos_slug[$key]);
            }
            if ($value === 'esquemas.ausentismos_grupo_tres') {
                unset($this->campos_slug[$key]);
            }
        }

        array_push($this->campos_slug, 'esquemas.id');
        array_push($this->campos_slug, 'esquemas.user_id');
        array_push($this->campos_slug, 'esquemas.total_dias_feriados_grupo_dos');
        array_push($this->campos_slug, 'esquemas.es_turnante');


        return Esquema::query()
            ->with('ausentismos')
            ->join('users', 'esquemas.user_id', '=', 'users.id')
            ->where('esquemas.recarga_id', $this->id_recarga)
            ->where('esquemas.active', true)
            ->where('esquemas.monto_total_cancelar', '>', 0)
            ->select($this->campos_slug)
            ->orderBy('monto_total_cancelar', 'ASC');
    }
}
