<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSeguimientoRecargasTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('seguimiento_recargas', function (Blueprint $table) {
            $table->bigIncrements('id');

            $table->foreign('recarga_id')->references('id')->on('recargas');
            $table->unsignedBigInteger('recarga_id')->nullable();

            $table->foreign('estado_id')->references('id')->on('estado_recargas');
            $table->unsignedBigInteger('estado_id')->nullable();

            $table->foreign('user_id')->references('id')->on('users');
            $table->unsignedBigInteger('user_id')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('seguimiento_recargas');
    }
}
