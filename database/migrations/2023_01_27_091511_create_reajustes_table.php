<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateReajustesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('reajustes', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('uuid')->unique();
            $table->date('fecha_inicio')->nullable();
            $table->date('fecha_termino')->nullable();
            $table->boolean('incremento')->default(0); //0=rebaja 1=incremento
            $table->decimal('dias_periodo', 6, 1)->nullable();
            $table->bigInteger('valor_dia')->nullable();
            $table->decimal('dias', 6, 1)->nullable();
            $table->bigInteger('monto_ajuste')->nullable();
            $table->unsignedSmallInteger('tipo_reajuste');
            $table->text('observacion')->nullable();
            $table->unsignedSmallInteger('last_status')->nullable();

            $table->foreign('user_id')->references('id')->on('users');
            $table->unsignedBigInteger('user_id')->nullable();

            $table->foreign('tipo_ausentismo_id')->references('id')->on('tipo_ausentismos');
            $table->unsignedBigInteger('tipo_ausentismo_id')->nullable();

            $table->foreign('tipo_incremento_id')->references('id')->on('tipo_incrementos');
            $table->unsignedBigInteger('tipo_incremento_id')->nullable();

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
        Schema::dropIfExists('reajustes');
    }
}
