<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateEsquemasTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('esquemas', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->uuid('uuid')->unique();
            $table->boolean('tipo_ingreso')->nullable()->default(0);
            $table->boolean('active')->nullable()->default(1);
            $table->boolean('es_remplazo')->nullable()->default(0);

            $table->unsignedSmallInteger('es_turnante')->nullable();
            $table->boolean('es_turnante_value')->default(0);
            $table->boolean('turno_asignacion')->nullable()->default(0);

            $table->bigInteger('total_dias_contrato')->nullable()->default(0);
            $table->bigInteger('total_dias_habiles_contrato')->nullable()->default(0);
            $table->bigInteger('total_dias_feriados_contrato')->nullable()->default(0);
            $table->boolean('fecha_alejamiento')->nullable()->default(0);
            $table->bigInteger('contrato_n_registros')->nullable()->default(0);
            $table->bigInteger('calculo_contrato')->nullable()->default(0);

            $table->bigInteger('total_dias_turno_largo')->nullable()->default(0);
            $table->bigInteger('total_dias_turno_nocturno')->nullable()->default(0);
            $table->bigInteger('total_dias_libres')->nullable()->default(0);
            $table->bigInteger('total_dias_feriados_turno')->nullable()->default(0);

            $table->bigInteger('total_dias_turno_largo_en_periodo_contrato')->nullable()->default(0);
            $table->bigInteger('total_dias_turno_nocturno_en_periodo_contrato')->nullable()->default(0);
            $table->bigInteger('total_dias_libres_en_periodo_contrato')->nullable()->default(0);
            $table->bigInteger('total_dias_feriados_turno_en_periodo_contrato')->nullable()->default(0);
            $table->bigInteger('calculo_turno')->nullable()->default(0);
            $table->bigInteger('total_turno')->nullable()->default(0);

            $table->bigInteger('total_dias_grupo_uno')->nullable()->default(0);
            $table->bigInteger('total_dias_habiles_grupo_uno')->nullable()->default(0);
            $table->bigInteger('total_dias_feriados_grupo_uno')->nullable()->default(0);
            $table->bigInteger('grupo_uno_n_registros')->nullable()->default(0);
            $table->bigInteger('calculo_grupo_uno')->nullable()->default(0);

            $table->bigInteger('total_dias_grupo_dos')->nullable()->default(0);
            $table->bigInteger('total_dias_habiles_grupo_dos')->nullable()->default(0);
            $table->bigInteger('total_dias_feriados_grupo_dos')->nullable()->default(0);
            $table->bigInteger('grupo_dos_n_registros')->nullable()->default(0);
            $table->bigInteger('calculo_grupo_dos')->nullable()->default(0);

            $table->bigInteger('total_dias_grupo_tres')->nullable()->default(0);
            $table->bigInteger('total_dias_habiles_grupo_tres')->nullable()->default(0);
            $table->bigInteger('total_dias_feriados_grupo_tres')->nullable()->default(0);
            $table->bigInteger('grupo_tres_n_registros')->nullable()->default(0);
            $table->bigInteger('calculo_grupo_tres')->nullable()->default(0);

            $table->boolean('fechas_cruzadas')->default(0);

            $table->bigInteger('total_dias_viaticos')->nullable()->default(0);
            $table->bigInteger('total_dias_habiles_viaticos')->nullable()->default(0);
            $table->bigInteger('total_dias_feriados_viaticos')->nullable()->default(0);
            $table->bigInteger('viaticos_n_registros')->nullable()->default(0);
            $table->bigInteger('calculo_viaticos')->nullable()->default(0);

            $table->bigInteger('total_dias_ajustes')->nullable()->default(0);
            $table->bigInteger('total_dias_habiles_ajustes')->nullable()->default(0);
            $table->bigInteger('total_dias_feriados_ajustes')->nullable()->default(0);
            $table->bigInteger('ajustes_dias_n_registros')->nullable()->default(0);
            $table->bigInteger('calculo_dias_ajustes')->nullable()->default(0);

            $table->bigInteger('total_monto_ajuste')->nullable()->default(0);
            $table->bigInteger('ajustes_monto_n_registros')->nullable()->default(0);

            $table->bigInteger('total_dias_cancelar')->nullable()->default(0);
            $table->bigInteger('monto_total_cancelar')->nullable()->default(0);

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
        Schema::dropIfExists('esquemas');
    }
}
