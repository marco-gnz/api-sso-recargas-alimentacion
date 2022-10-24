<?php

namespace Database\Seeders;

use App\Models\EstadoRecarga;
use Illuminate\Database\Seeder;

class EstadosRecargaSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        EstadoRecarga::truncate(); //evita duplicar datos
        $estado = new EstadoRecarga();
        $estado->nombre = 'INGRESADA';
        $estado->descripcion = 'Recarga ingresada al sistema';
        $estado->save();

        $estado = new EstadoRecarga();
        $estado->nombre = 'AJUSTES DE AUSENTISMOS';
        $estado->descripcion = 'Configuración de reglas de ausentismos';
        $estado->save();

        $estado = new EstadoRecarga();
        $estado->nombre = 'CARGA DE FUNCIONARIOS';
        $estado->descripcion = 'Carga masiva de funcionarios';
        $estado->save();

        $estado = new EstadoRecarga();
        $estado->nombre = 'CARGA GRUPO 1';
        $estado->descripcion = 'Carga de ausentismos grupo 1';
        $estado->save();

        $estado = new EstadoRecarga();
        $estado->nombre = 'CARGA GRUPO 2';
        $estado->descripcion = 'Carga de ausentismos grupo 2';
        $estado->save();

        $estado = new EstadoRecarga();
        $estado->nombre = 'CARGA GRUPO 3';
        $estado->descripcion = 'Carga de ausentismos grupo 3';
        $estado->save();

        $estado = new EstadoRecarga();
        $estado->nombre = 'AJUSTES DE DATOS PRINCIPALES';
        $estado->descripcion = 'Ajustes en datos principales de la recarga';
        $estado->save();
    }
}
