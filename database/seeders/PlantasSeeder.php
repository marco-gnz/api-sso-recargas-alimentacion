<?php

namespace Database\Seeders;

use App\Models\Planta;
use Illuminate\Database\Seeder;

class PlantasSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Planta::truncate(); //evita duplicar datos
        $planta = new Planta();
        $planta->nombre = 'ADMINISTRATIVOS';
        $planta->save();

        $planta = new Planta();
        $planta->nombre = 'AUXILIARES';
        $planta->save();

        $planta = new Planta();
        $planta->nombre = 'MÉDICOS';
        $planta->save();

        $planta = new Planta();
        $planta->nombre = 'ODONTÓLOGOS';
        $planta->save();

        $planta = new Planta();
        $planta->nombre = 'PROFESIONALES';
        $planta->save();

        $planta = new Planta();
        $planta->nombre = 'QUÍMICOS';
        $planta->save();

        $planta = new Planta();
        $planta->nombre = 'TÉCNICOS';
        $planta->save();

        $planta = new Planta();
        $planta->nombre = 'DIRECTIVOS';
        $planta->save();
    }
}
