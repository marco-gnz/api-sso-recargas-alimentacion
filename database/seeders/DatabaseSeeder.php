<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');

        $this->call(UserSeeder::class);
        $this->call(EstadosRecargaSeeder::class);
        $this->call(GruposAusentismosSeeder::class);
        $this->call(MeridianosSeeder::class);
        $this->call(PlantasSeeder::class);
        $this->call(HorasSeeder::class);
        $this->call(LeyesSeeder::class);
        $this->call(EstablecimientosSeeder::class);
        $this->call(RolesPermisosSeeder::class);
        $this->call(PermissionsMantenedoresSeeder::class);

        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

    }
}
