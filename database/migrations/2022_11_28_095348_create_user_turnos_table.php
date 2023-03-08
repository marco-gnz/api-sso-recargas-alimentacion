<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateUserTurnosTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('user_turnos', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('uuid')->unique();
            $table->string('folio')->nullable();
            $table->year('anio');
            $table->string('mes');
            $table->string('asignacion_tercer_turno')->nullable();
            $table->string('bonificacion_asignacion_turno')->nullable();
            $table->string('asignacion_cuarto_turno')->nullable();
            $table->boolean('es_turnante')->default(0);

            $table->foreign('user_id')->references('id')->on('users');
            $table->unsignedBigInteger('user_id')->nullable();

            $table->foreign('recarga_id')->references('id')->on('recargas');
            $table->unsignedBigInteger('recarga_id')->nullable();

            $table->foreign('proceso_id')->references('id')->on('proceso_turnos');
            $table->unsignedBigInteger('proceso_id')->nullable();

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
        Schema::dropIfExists('user_turnos');
    }
}
