<?php

namespace App\Observers;

use App\Models\Ausentismo;

class AusentismoObserver
{
    private static $bulkProcessing      = true;
    private static $ausentismosCreados  = [];

    public function created(Ausentismo $ausentismo)
    {
        if ($ausentismo->grupo_id === 1) {
            if (!self::$bulkProcessing) {
                $this->actualizarEsquemaGrupoUno($ausentismo);
                \Log::info('AusentismoObserver: created method executed.');
            } else {
                \Log::info('AusentismoObserver: ejecución masiva. .');
                self::$ausentismosCreados[] = $ausentismo;
            }
        }
    }

    /**
     * Handle the Ausentismo "updated" event.
     *
     * @param  \App\Models\Ausentismo  $ausentismo
     * @return void
     */
    public function updated(Ausentismo $ausentismo)
    {
        //
    }

    private function actualizarEsquemaGrupoUno(Ausentismo $ausentismo)
    {
        $this->actualizarEsquemaUno($ausentismo->esquema);
    }

    public function creating(Ausentismo $ausentismo)
    {
        self::$bulkProcessing = true;
    }

    public function createdBulk()
    {
        if (self::$bulkProcessing) {
            $this->actualizarEsquemasGrupoUno(static::$ausentismosCreados);
            self::$ausentismosCreados = [];
            self::$bulkProcessing = false;
        }
    }

    private function actualizarEsquemasGrupoUno($ausentismos)
    {
        $esquemas = collect($ausentismos)->pluck('esquema')->unique();

        $esquemas->each(function ($esquema) {
            $this->actualizarEsquemaUno($esquema);
        });
    }

    private function totalDiasAusentismoGrupo($esquema, $id_grupo)
    {
        $total_ausentismos  = 0;
        switch ($esquema->es_turnante) {
            case 1:
            case 3:
                switch ($id_grupo) {
                    case 1:
                        $total_ausentismos = $esquema->total_dias_grupo_uno;
                        break;
                    case 2:
                        $total_ausentismos = $esquema->total_dias_grupo_dos;
                        break;
                    case 3:
                        $total_ausentismos = $esquema->total_dias_grupo_tres;
                        break;
                }
                break;
            case 2:
                switch ($id_grupo) {
                    case 1:
                        $total              = $esquema->total_dias_habiles_grupo_uno - $esquema->total_dias_feriados_grupo_uno;
                        $total_ausentismos  = $total;
                        break;
                    case 2:
                        $total              = $esquema->total_dias_habiles_grupo_dos - $esquema->total_dias_feriados_grupo_dos;
                        $total_ausentismos  = $total;
                        break;
                    case 3:
                        $total              = $esquema->total_dias_habiles_grupo_tres - $esquema->total_dias_feriados_grupo_tres;
                        $total_ausentismos  = $total;
                        break;
                }
                break;
        }
        return $total_ausentismos;
    }

    private function actualizarEsquemaUno($esquema)
    {
        $ausentismos = $esquema->ausentismos()->where('grupo_id', 1)->get();
        $data = [
            'total_dias_grupo_uno'           => $ausentismos->sum('total_dias_ausentismo_periodo'),
            'total_dias_habiles_grupo_uno'   => $ausentismos->sum('total_dias_habiles_ausentismo_periodo'),
            'total_dias_feriados_grupo_uno'  => $this->contarFeriadosEnAusentismos($esquema->funcionario, $esquema->recarga, 1),
            'grupo_uno_n_registros'          => count($ausentismos),
        ];

        $update         = $esquema->update($data);
        $esquema        = $esquema->fresh();

        $total_grupo    = $this->totalDiasAusentismoGrupo($esquema, 1);

        $esquema->update([
            'calculo_grupo_uno'              => $total_grupo
        ]);
    }

    private function contarFeriadosEnAusentismos($funcionario, $recarga, $id_grupo)
    {
        $feriados_count = 0;
        $ausentismos = $funcionario->ausentismos()->where('recarga_id', $recarga->id)->where('grupo_id', $id_grupo)->get();

        foreach ($ausentismos as $ausentismo) {
            $feriados_count += $recarga->feriados()->where('active', true)->whereBetween('fecha', [$ausentismo->fecha_inicio_periodo, $ausentismo->fecha_termino_periodo])->count();
        }

        return $feriados_count;
    }
}
