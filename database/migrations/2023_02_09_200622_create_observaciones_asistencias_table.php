<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateObservacionesAsistenciasTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('observaciones_asistencias', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->date('fecha')->nullable();
            $table->text('observacion')->nullable();

            $table->foreign('tipo_asistencia_turno_id')->references('id')->on('tipo_asistencia_turnos');
            $table->unsignedBigInteger('tipo_asistencia_turno_id')->nullable();

            $table->foreign('asistencia_id')->references('id')->on('asistencias');
            $table->unsignedBigInteger('asistencia_id')->nullable();

            $table->foreign('user_created_by')->references('id')->on('users');
            $table->unsignedBigInteger('user_created_by')->nullable();
            $table->dateTime('date_created_user', 0)->nullable();

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
        Schema::dropIfExists('observaciones_asistencias');
    }
}
