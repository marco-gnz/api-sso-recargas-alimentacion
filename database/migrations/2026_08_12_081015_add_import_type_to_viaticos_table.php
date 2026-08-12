<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddImportTypeToViaticosTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('viaticos', function (Blueprint $table) {
            $table->unsignedSmallInteger('import_type')->default(0)->after('valor_viatico');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('viaticos', function (Blueprint $table) {
            $table->dropColumn('import_type');
        });
    }
}
