<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateRecargasTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('recargas', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('codigo')->unique()->nullable();
            $table->year('anio_beneficio');
            $table->string('mes_beneficio');
            $table->integer('total_dias_mes_beneficio');
            $table->integer('total_dias_laborales_beneficio');
            $table->integer('total_dias_habiles_beneficio');
            $table->year('anio_calculo');
            $table->string('mes_calculo');
            $table->integer('total_dias_mes_calculo');
            $table->integer('total_dias_laborales_calculo');
            $table->integer('total_dias_habiles_calculo');
            $table->bigInteger('monto_dia')->nullable();
            $table->bigInteger('monto_estimado')->nullable();
            $table->boolean('active')->default(1);
            $table->unsignedSmallInteger('last_status')->nullable();

            $table->foreign('establecimiento_id')->references('id')->on('establecimientos');
            $table->unsignedBigInteger('establecimiento_id')->nullable();

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
        Schema::dropIfExists('recargas');
    }
}
