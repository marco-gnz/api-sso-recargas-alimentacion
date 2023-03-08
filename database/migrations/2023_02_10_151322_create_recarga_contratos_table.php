<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateRecargaContratosTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('recarga_contratos', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->uuid('uuid')->unique();
            $table->date('fecha_inicio')->nullable();
            $table->date('fecha_termino')->nullable();
            $table->boolean('alejamiento')->default(0);
            $table->decimal('total_dias_contrato', 6, 1)->nullable();
            $table->date('fecha_inicio_periodo')->nullable();
            $table->date('fecha_termino_periodo')->nullable();
            $table->decimal('total_dias_contrato_periodo', 6, 1)->nullable();
            $table->decimal('total_dias_habiles_contrato_periodo', 6, 1)->nullable();

            $table->foreign('user_id')->references('id')->on('users');
            $table->unsignedBigInteger('user_id')->nullable();

            $table->foreign('establecimiento_id')->references('id')->on('establecimientos');
            $table->unsignedBigInteger('establecimiento_id')->nullable();

            $table->foreign('unidad_id')->references('id')->on('unidads');
            $table->unsignedBigInteger('unidad_id')->nullable();

            $table->foreign('planta_id')->references('id')->on('plantas');
            $table->unsignedBigInteger('planta_id')->nullable();

            $table->foreign('cargo_id')->references('id')->on('cargos');
            $table->unsignedBigInteger('cargo_id')->nullable();

            $table->foreign('ley_id')->references('id')->on('leys');
            $table->unsignedBigInteger('ley_id')->nullable();

            $table->foreign('hora_id')->references('id')->on('horas');
            $table->unsignedBigInteger('hora_id')->nullable();

            $table->foreign('recarga_id')->references('id')->on('recargas');
            $table->unsignedBigInteger('recarga_id')->nullable();

            $table->unsignedBigInteger('user_created_by')->nullable();
            $table->foreign('user_created_by')->references('id')->on('users');
            $table->dateTime('date_created_user', 0)->nullable();

            $table->unsignedBigInteger('user_update_by')->nullable();
            $table->foreign('user_update_by')->references('id')->on('users');
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
        Schema::dropIfExists('recarga_contratos');
    }
}
