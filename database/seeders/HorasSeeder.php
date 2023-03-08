<?php

namespace Database\Seeders;

use App\Models\Hora;
use Illuminate\Database\Seeder;

class HorasSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Hora::truncate(); //evita duplicar datos
        $hora = new Hora();
        $hora->nombre = '11';
        $hora->save();

        $hora = new Hora();
        $hora->nombre = '22';
        $hora->save();

        $hora = new Hora();
        $hora->nombre = '33';
        $hora->save();

        $hora = new Hora();
        $hora->nombre = '44';
        $hora->save();
    }
}
