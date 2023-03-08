<?php

namespace Database\Seeders;

use App\Models\Establecimiento;
use Illuminate\Database\Seeder;

class EstablecimientosSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Establecimiento::truncate(); //evita duplicar datos

        $establecimiento = new Establecimiento();
        $establecimiento->cod_sirh = '1025';
        $establecimiento->sigla = 'DSSO';
        $establecimiento->nombre = 'DIRECCIÓN DE SERVICIO DE SALUD OSORNO';
        $establecimiento->save();

        $establecimiento = new Establecimiento();
        $establecimiento->cod_sirh = '1041';
        $establecimiento->sigla = 'HPO';
        $establecimiento->nombre = 'HOSPITAL PUERTO OCTAY';
        $establecimiento->save();

        $establecimiento = new Establecimiento();
        $establecimiento->cod_sirh = '1040';
        $establecimiento->sigla = 'HPU';
        $establecimiento->nombre = 'HOSPITAL PURRANQUE';
        $establecimiento->save();

        $establecimiento = new Establecimiento();
        $establecimiento->cod_sirh = '1042';
        $establecimiento->sigla = 'HRN';
        $establecimiento->nombre = 'HOSPITAL RIO NEGRO';
        $establecimiento->save();

        $establecimiento = new Establecimiento();
        $establecimiento->cod_sirh = '1043';
        $establecimiento->sigla = 'HFSLKMM';
        $establecimiento->nombre = 'HOSPITAL FUTA SRUKA LAWENCHE';
        $establecimiento->save();

        $establecimiento = new Establecimiento();
        $establecimiento->cod_sirh = '1044';
        $establecimiento->sigla = 'HPMULEN';
        $establecimiento->nombre = 'HOSPITAL PU MULEN';
        $establecimiento->save();

        $establecimiento = new Establecimiento();
        $establecimiento->cod_sirh = '1027';
        $establecimiento->sigla = 'HBSJO';
        $establecimiento->nombre = 'HOSPITAL BASE SAN JOSÉ OSORNO';
        $establecimiento->save();
    }
}
