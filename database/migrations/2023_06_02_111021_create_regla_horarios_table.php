<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateReglaHorariosTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('regla_horarios', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->time('hora_inicio')->nullable();
            $table->time('hora_termino')->nullable();

            $table->foreign('regla_id')->references('id')->on('reglas')->onDelete('cascade');
            $table->unsignedBigInteger('regla_id')->nullable();

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
        Schema::dropIfExists('regla_horarios');
    }
}
