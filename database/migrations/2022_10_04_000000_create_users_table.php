<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateUsersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('users', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->uuid('uuid')->unique()->nullable();
            $table->integer('rut')->unique();
            $table->string('dv', 1);
            $table->string('rut_completo')->unique();
            $table->string('nombres');
            $table->string('apellidos');
            $table->string('nombre_completo');
            $table->boolean('estado')->default(1);
            $table->boolean('turno')->default(0);
            $table->string('email')->unique()->nullable();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password')->nullable();
            $table->rememberToken();

            $table->foreign('establecimiento_id')->references('id')->on('establecimientos');
            $table->unsignedBigInteger('establecimiento_id')->nullable();

            $table->foreign('unidad_id')->references('id')->on('unidads');
            $table->unsignedBigInteger('unidad_id')->nullable();

            $table->foreign('planta_id')->references('id')->on('plantas');
            $table->unsignedBigInteger('planta_id')->nullable();

            $table->foreign('cargo_id')->references('id')->on('cargos');
            $table->unsignedBigInteger('cargo_id')->nullable();

            $table->unsignedBigInteger('usuario_add_id')->nullable();
            $table->foreign('usuario_add_id')->references('id')->on('users');
            $table->dateTime('fecha_add', 0)->nullable();

            $table->unsignedBigInteger('usuario_update_id')->nullable();
            $table->foreign('usuario_update_id')->references('id')->on('users');
            $table->dateTime('fecha_update', 0)->nullable();
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
        Schema::dropIfExists('users');
    }
}
