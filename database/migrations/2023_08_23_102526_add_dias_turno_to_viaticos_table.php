<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddDiasTurnoToViaticosTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('viaticos', function (Blueprint $table) {
            $table->integer('total_dias_periodo_turno')->default(0)->after('total_dias_habiles_periodo');
            $table->integer('total_dias_habiles_periodo_turno')->default(0)->after('total_dias_periodo_turno');
            $table->boolean('descuento_turno_libre')->default(0)->after('total_dias_habiles_periodo_turno');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('viaticos', function (Blueprint $table) {
            $table->dropColumn('total_dias_periodo_turno');
            $table->dropColumn('total_dias_habiles_periodo_turno');
            $table->dropColumn('descuento_turno_libre');
        });
    }
}
