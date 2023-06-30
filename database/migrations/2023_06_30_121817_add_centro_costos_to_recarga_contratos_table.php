<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddCentroCostosToRecargaContratosTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('recarga_contratos', function (Blueprint $table) {
            $table->string('centro_costo')->default(0)->after('total_dias_habiles_contrato_periodo');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('recarga_contratos', function (Blueprint $table) {
            $table->dropColumn('centro_costo');
        });
    }
}
