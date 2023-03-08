<?php

namespace Database\Seeders;

use App\Models\Ley;
use Illuminate\Database\Seeder;

class LeyesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Ley::truncate(); //evita duplicar datos
        $ley = new Ley();
        $ley->nombre = '15.076';
        $ley->save();

        $ley = new Ley();
        $ley->nombre = '18.834';
        $ley->save();

        $ley = new Ley();
        $ley->nombre = '18.835';
        $ley->save();

        $ley = new Ley();
        $ley->nombre = '19.664';
        $ley->save();
    }
}
