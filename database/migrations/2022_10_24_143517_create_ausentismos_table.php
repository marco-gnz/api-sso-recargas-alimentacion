<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAusentismosTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('ausentismos', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('uuid')->unique();
            $table->boolean('turno')->default(1);
            $table->date('fecha_inicio')->nullable();
            $table->date('fecha_termino')->nullable();
            $table->date('fecha_inicio_periodo')->nullable();
            $table->date('fecha_termino_periodo')->nullable();
            $table->decimal('total_dias_ausentismo', 2, 0)->nullable();
            $table->decimal('total_dias_ausentismo_periodo', 2, 0)->nullable();
            $table->time('hora_inicio')->nullable();
            $table->time('hora_termino')->nullable();

            $table->foreign('user_id')->references('id')->on('users');
            $table->unsignedBigInteger('user_id')->nullable();

            $table->foreign('tipo_ausentismo_id')->references('id')->on('tipo_ausentismos');
            $table->unsignedBigInteger('tipo_ausentismo_id')->nullable();

            $table->foreign('recarga_id')->references('id')->on('recargas')->onDelete('cascade');
            $table->unsignedBigInteger('recarga_id')->nullable();

            $table->foreign('regla_id')->references('id')->on('reglas')->onDelete('cascade');
            $table->unsignedBigInteger('regla_id')->nullable();

            $table->foreign('grupo_id')->references('id')->on('grupo_ausentismos')->onDelete('cascade');
            $table->unsignedBigInteger('grupo_id')->nullable();

            $table->foreign('establecimiento_id')->references('id')->on('establecimientos');
            $table->unsignedBigInteger('establecimiento_id')->nullable();

            $table->foreign('unidad_id')->references('id')->on('unidads');
            $table->unsignedBigInteger('unidad_id')->nullable();

            $table->foreign('planta_id')->references('id')->on('plantas');
            $table->unsignedBigInteger('planta_id')->nullable();

            $table->foreign('cargo_id')->references('id')->on('cargos');
            $table->unsignedBigInteger('cargo_id')->nullable();

            $table->foreign('meridiano_id')->references('id')->on('meridianos');
            $table->unsignedBigInteger('meridiano_id')->nullable();

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
        Schema::dropIfExists('ausentismos');
    }
}
