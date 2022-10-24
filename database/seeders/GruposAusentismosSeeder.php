<?php

namespace Database\Seeders;

use App\Models\GrupoAusentismo;
use Illuminate\Database\Seeder;

class GruposAusentismosSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        GrupoAusentismo::truncate(); //evita duplicar datos
        $grupo = new GrupoAusentismo();
        $grupo->nombre = 'GRUPO 1';
        $grupo->descripcion = 'Grupo de ausentismos que valida días enteros.';
        $grupo->save();

        $grupo = new GrupoAusentismo();
        $grupo->nombre = 'GRUPO 2';
        $grupo->descripcion = 'Grupo de ausentismos que valida días en formato de media jornada (0.5).';
        $grupo->save();

        $grupo = new GrupoAusentismo();
        $grupo->nombre = 'GRUPO 3';
        $grupo->descripcion = 'Grupo de ausentismos que valida descuentos en un rango de hora.';
        $grupo->save();
    }
}
