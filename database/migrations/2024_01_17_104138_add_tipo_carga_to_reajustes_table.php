<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddTipoCargaToReajustesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('reajustes', function (Blueprint $table) {
            $table->integer('tipo_carga')->default(0)->after('last_status');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('reajustes', function (Blueprint $table) {
            $table->dropColumn('tipo_carga');
        });
    }
}
