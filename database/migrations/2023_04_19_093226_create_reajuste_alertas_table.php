<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateReajusteAlertasTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('reajuste_alertas', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->text('observacion')->nullable();
            $table->unsignedSmallInteger('tipo')->nullable();

            $table->foreign('reajuste_id')->references('id')->on('reajustes')->onDelete('cascade');
            $table->unsignedBigInteger('reajuste_id')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('reajuste_alertas');
    }
}
