<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateReglasTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('reglas', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->time('hora_inicio')->nullable();
            $table->time('hora_termino')->nullable();
            $table->boolean('active')->default(1);
            $table->boolean('turno_funcionario')->nullable(); //0=no turnante 1=turnante

            $table->foreign('grupo_id')->references('id')->on('grupo_ausentismos');
            $table->unsignedBigInteger('grupo_id')->nullable();

            $table->foreign('recarga_id')->references('id')->on('recargas')->onDelete('cascade');
            $table->unsignedBigInteger('recarga_id')->nullable();

            $table->foreign('tipo_ausentismo_id')->references('id')->on('tipo_ausentismos');
            $table->unsignedBigInteger('tipo_ausentismo_id')->nullable();

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
        Schema::dropIfExists('reglas');
    }
}
