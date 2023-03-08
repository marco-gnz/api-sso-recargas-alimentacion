<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateReajusteEstadosTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('reajuste_estados', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedSmallInteger('status')->nullable();
            $table->text('observacion')->nullable();

            $table->foreign('user_id')->references('id')->on('users');
            $table->unsignedBigInteger('user_id')->nullable();

            $table->foreign('reajuste_id')->references('id')->on('reajustes')->onDelete('cascade');
            $table->unsignedBigInteger('reajuste_id')->nullable();

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
        Schema::dropIfExists('reajuste_estados');
    }
}
