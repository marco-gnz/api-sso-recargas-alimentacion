<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAsistenciasTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('asistencias', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('uuid')->unique();
            $table->string('dia')->nullable();
            $table->string('mes')->nullable();
            $table->year('anio')->nullable();
            $table->date('fecha')->nullable();
            $table->text('observacion')->nullable();

            $table->foreign('user_id')->references('id')->on('users');
            $table->unsignedBigInteger('user_id')->nullable();

            $table->foreign('recarga_id')->references('id')->on('recargas');
            $table->unsignedBigInteger('recarga_id')->nullable();

            $table->foreign('establecimiento_id')->references('id')->on('establecimientos');
            $table->unsignedBigInteger('establecimiento_id')->nullable();

            $table->foreign('tipo_asistencia_turno_id')->references('id')->on('tipo_asistencia_turnos');
            $table->unsignedBigInteger('tipo_asistencia_turno_id')->nullable();

            $table->foreign('user_created_by')->references('id')->on('users');
            $table->unsignedBigInteger('user_created_by')->nullable();
            $table->dateTime('date_created_user', 0)->nullable();

            $table->foreign('user_update_by')->references('id')->on('users');
            $table->unsignedBigInteger('user_update_by')->nullable();
            $table->dateTime('date_updated_user', 0)->nullable();

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
        Schema::dropIfExists('asistencias');
    }
}
