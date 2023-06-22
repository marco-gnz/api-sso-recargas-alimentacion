<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCartolasTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('cartolas', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->uuid('uuid')->unique();
            $table->boolean('active')->nullable()->default(1);

            $table->boolean('es_turnante')->nullable();

            $table->bigInteger('total_dias_contrato')->nullable()->default(0);
            $table->bigInteger('total_dias_habiles_contrato')->nullable()->default(0);
            $table->boolean('fecha_alejamiento')->nullable()->default(0);

            $table->bigInteger('total_dias_turno_largo')->nullable()->default(0);
            $table->bigInteger('total_dias_turno_nocturno')->nullable()->default(0);
            $table->bigInteger('total_dias_libres')->nullable()->default(0);

            $table->bigInteger('total_dias_grupo_uno')->nullable()->default(0);
            $table->bigInteger('total_dias_habiles_grupo_uno')->nullable()->default(0);

            $table->bigInteger('total_dias_grupo_dos')->nullable()->default(0);
            $table->bigInteger('total_dias_habiles_grupo_dos')->nullable()->default(0);

            $table->bigInteger('total_dias_grupo_tres')->nullable()->default(0);
            $table->bigInteger('total_dias_habiles_grupo_tres')->nullable()->default(0);

            $table->bigInteger('total_dias_viaticos')->nullable()->default(0);
            $table->bigInteger('total_dias_habiles_viaticos')->nullable()->default(0);

            $table->decimal('total_dias_ajustes', 6, 1)->nullable()->default(0);

            $table->bigInteger('total_monto_ajuste')->nullable()->default(0);

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
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('cartolas');
    }
}
