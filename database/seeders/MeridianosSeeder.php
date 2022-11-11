<?php

namespace Database\Seeders;

use App\Models\Meridiano;
use Illuminate\Database\Seeder;

class MeridianosSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Meridiano::truncate(); //evita duplicar datos
        $meridiano = new Meridiano();
        $meridiano->nombre = 'AM';
        $meridiano->save();

        $meridiano = new Meridiano();
        $meridiano->nombre = 'PM';
        $meridiano->save();

        $meridiano = new Meridiano();
        $meridiano->nombre = 'DC';
        $meridiano->save();
    }
}
