<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateViaticosTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('viaticos', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->uuid('uuid')->unique();
            $table->date('fecha_inicio')->nullable();
            $table->date('fecha_termino')->nullable();
            $table->date('fecha_inicio_periodo')->nullable();
            $table->date('fecha_termino_periodo')->nullable();
            $table->integer('total_dias')->nullable();
            $table->integer('total_dias_periodo')->nullable();
            $table->integer('total_dias_habiles_periodo')->nullable();
            $table->string('jornada')->nullable();
            $table->string('tipo_resolucion')->nullable();
            $table->string('n_resolucion')->nullable();
            $table->date('fecha_resolucion')->nullable();
            $table->string('tipo_comision')->nullable();
            $table->string('motivo_viatico')->nullable();
            $table->integer('valor_viatico')->nullable();

            $table->foreign('user_id')->references('id')->on('users');
            $table->unsignedBigInteger('user_id')->nullable();

            $table->foreign('recarga_id')->references('id')->on('recargas')->onDelete('cascade');
            $table->unsignedBigInteger('recarga_id')->nullable();

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
        Schema::dropIfExists('viaticos');
    }
}
