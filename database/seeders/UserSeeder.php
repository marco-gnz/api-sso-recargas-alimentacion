<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Support\Str;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        User::truncate(); //evita duplicar datos
        $user = new User();
        $user->rut = 19270290;
        $user->dv = '9';
        $user->rut_completo = '19270290-9';
        $user->nombres = 'MARCO IGNACIO';
        $user->apellidos = 'GONZALEZ AZOCAR';
        $user->nombre_completo = 'MARCO IGNACIO GONZALEZ AZOCAR';
        $user->email = 'marcoi.gonzalez@redsalud.gob.cl';
        $user->password = bcrypt('marcoi.gonzalez@redsalud.gob.cl');
        $user->save();

        $user->createToken('sa');
    }
}
