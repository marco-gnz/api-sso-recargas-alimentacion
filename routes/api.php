<?php

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which1
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::get('/user-in-recarga', function(){

    $recarga = App\Models\Recarga::first();
    $user = App\Models\User::find(3908);

    $existe = $recarga->whereHas('users', function($query) use($user){
        $query->where('recarga_user.user_id', $user->id);
    })->get();

    return $existe;
});

Route::get('/count', function(){

    $days = 0;
    $startDate = Carbon::parse("2023-01-22")->format('Y-m-d');
    $endDate = Carbon::parse("2023-01-28")->format('Y-m-d');

    for ($i = $startDate; $i <= $endDate; $i++) {
        $i_format       = Carbon::parse($i)->isWeekend();
        if($i_format){
            $days++;
        }
    }



    /* $days = $startDate->diffInDays(function (Carbon $date){
        return $date->isWeekend();
    }, $endDate); */

    /* $days = $startDate->diffInWeekdays($endDate); */

    return $days;
});


Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

Route::post('/login', [App\Http\Controllers\Auth\LoginController::class, 'login']);
Route::post('/logout', [App\Http\Controllers\Auth\LogoutController::class, 'logout']);

//Rutas administrador - Autenticado
Route::group(
    [
        'namespace' => 'Admin',
        /* 'middleware' => 'auth:sanctum' */
    ],
    function(){
        Route::get('/admin/days-in-date', [App\Http\Controllers\Admin\Modulos\ModulosResponseController::class, 'returnDaysInDate']);
        Route::get('/admin/feriados/{codigo}', [App\Http\Controllers\Admin\Modulos\ModulosResponseController::class, 'returnFeriados']);

        //modulos
        Route::get('/admin/modulos/establecimientos/response', [App\Http\Controllers\Admin\Modulos\ModulosResponseController::class, 'returnEstablecimientos']);
        Route::get('/admin/modulos/tipos-ausentismos/response', [App\Http\Controllers\Admin\Modulos\ModulosResponseController::class, 'returnTiposAusentismos']);
        Route::get('/admin/modulos/tipos-incrementos/response', [App\Http\Controllers\Admin\Modulos\ModulosResponseController::class, 'returnTiposIncrementos']);
        Route::get('/admin/modulos/grupos-ausentismos/response', [App\Http\Controllers\Admin\Modulos\ModulosResponseController::class, 'returnGruposAusentismos']);
        Route::get('/admin/modulos/meridianos/response', [App\Http\Controllers\Admin\Modulos\ModulosResponseController::class, 'returnMeridianos']);
        Route::get('/admin/modulos/columnas/funcionarios', [App\Http\Controllers\Admin\Modulos\ColumnasImportController::class, 'columasImportarFuncionarios']);
        Route::get('/admin/modulos/columnas/grupo-uno', [App\Http\Controllers\Admin\Modulos\ColumnasImportController::class, 'columnasImportGrupoUno']);
        Route::get('/admin/modulos/columnas/grupo-dos', [App\Http\Controllers\Admin\Modulos\ColumnasImportController::class, 'columnasImportGrupoDos']);
        Route::get('/admin/modulos/columnas/grupo-tres', [App\Http\Controllers\Admin\Modulos\ColumnasImportController::class, 'columnasImportGrupoTres']);
        Route::get('/admin/modulos/columnas/turnantes', [App\Http\Controllers\Admin\Modulos\ColumnasImportController::class, 'columnasImportTurnos']);
        Route::get('/admin/modulos/columnas/asistencia/{codigo}', [App\Http\Controllers\Admin\Modulos\ColumnasImportController::class, 'columnasImportAsistencia']);
        Route::get('/admin/modulos/columnas/asistencia/{codigo}/resumen', [App\Http\Controllers\Admin\Modulos\ColumnasImportController::class, 'columnasResumenAsistencia']);
        Route::get('/admin/modulos/tipos-asistencia-turnos/response', [App\Http\Controllers\Admin\Modulos\ModulosResponseController::class, 'returnTipoAsistenciaTurno']);
        Route::get('/admin/modulos/columnas/viaticos', [App\Http\Controllers\Admin\Modulos\ColumnasImportController::class, 'columnasImportViaticos']);

        Route::get('/admin/recargas/response', [App\Http\Controllers\Admin\RecargasController::class, 'returnRecargas']);
        Route::post('/admin/recargas/add', [App\Http\Controllers\Admin\RecargasController::class, 'storeRecarga']);
        Route::put('/admin/recargas/recarga/status/{id}', [App\Http\Controllers\Admin\RecargasController::class, 'changeStatus']);
        Route::get('/admin/recargas/recarga/{codigo}', [App\Http\Controllers\Admin\RecargasController::class, 'returnFindRecarga']);
        Route::put('/admin/recargas/recarga/datos-principales/{id}', [App\Http\Controllers\Admin\RecargasController::class, 'updateDatosPrincipales']);

        //files
        Route::post('/admin/recargas/recarga/masivo/funcionarios', [App\Http\Controllers\Admin\RecargasFilesController::class, 'loadFileFuncionarios']);
        Route::post('/admin/recargas/recarga/masivo/funcionarios/import', [App\Http\Controllers\Admin\RecargasFilesController::class, 'storeAllFuncionarios']);
        Route::post('/admin/recargas/recarga/masivo/turnos', [App\Http\Controllers\Admin\RecargasFilesController::class, 'loadFileTurnos']);
        Route::post('/admin/recargas/recarga/masivo/turnos/import', [App\Http\Controllers\Admin\RecargasFilesController::class, 'storeFileTurnos']);
        //files-grupos
        Route::post('/admin/recargas/recarga/masivo/grupo/uno', [App\Http\Controllers\Admin\RecargasFilesController::class, 'loadFileGrupoUno']);
        Route::post('/admin/recargas/recarga/masivo/grupo/uno/import', [App\Http\Controllers\Admin\RecargasFilesController::class, 'storeFileGrupoUno']);
        Route::post('/admin/recargas/recarga/masivo/grupo/dos', [App\Http\Controllers\Admin\RecargasFilesController::class, 'loadFileGrupoDos']);
        Route::post('/admin/recargas/recarga/masivo/grupo/dos/import', [App\Http\Controllers\Admin\RecargasFilesController::class, 'storeFileGrupoDos']);
        Route::post('/admin/recargas/recarga/masivo/grupo/tres', [App\Http\Controllers\Admin\RecargasFilesController::class, 'loadFileGrupoTres']);
        Route::post('/admin/recargas/recarga/masivo/grupo/tres/import', [App\Http\Controllers\Admin\RecargasFilesController::class, 'storeFileGrupoTres']);

        //recarga-asistencia
        Route::post('/admin/recargas/recarga/masivo/asistencia', [App\Http\Controllers\Admin\RecargasFilesController::class, 'loadFileAsistencia']);
        Route::post('/admin/recargas/recarga/masivo/asistencia/import', [App\Http\Controllers\Admin\RecargasFilesController::class, 'storeFileAsistencia']);

        //recarga-viaticos
        Route::post('/admin/recargas/recarga/masivo/viaticos', [App\Http\Controllers\Admin\RecargasFilesController::class, 'loadFileViaticos']);
        Route::post('/admin/recargas/recarga/masivo/viaticos/import', [App\Http\Controllers\Admin\RecargasFilesController::class, 'storeFileViaticos']);

        //recarga-feriados
        Route::post('/admin/recargas/feriados', [App\Http\Controllers\Admin\RecargaFeriadosController::class, 'storeFeriados']);
        Route::put('/admin/recargas/feriados/eliminar/{id_recarga}/{codigo_recarga}', [App\Http\Controllers\Admin\RecargaFeriadosController::class, 'deleteFeriadoInRecarga']);

        //grupos-reglas
        Route::get('/admin/recargas/grupos-ausentismos/{codigo}', [App\Http\Controllers\Admin\RecargasReglasController::class, 'returnTiposAusentismos']);
        Route::post('/admin/recargas/recarga/masivo/reglas/store', [App\Http\Controllers\Admin\RecargasReglasController::class, 'storeReglas']);
        Route::get('/admin/recargas/grupo/reglas', [App\Http\Controllers\Admin\RecargasReglasController::class, 'returnReglasToGrupo']);
        Route::delete('/admin/recargas/regla/eliminar/{id}', [App\Http\Controllers\Admin\RecargasReglasController::class, 'deleteReglaInRecarga']);

        //recarga-asistencia-resumen
        Route::get('/admin/recargas/recarga/{codigo}/asistencias', [App\Http\Controllers\Admin\RecargaAsistenciaController::class, 'asistenciasRecarga']);
        Route::put('/admin/asistencias/{id}', [App\Http\Controllers\Admin\RecargaAsistenciaController::class, 'updateAsistencia']);
        Route::get('/admin/asistencias/{uuid}', [App\Http\Controllers\Admin\RecargaAsistenciaController::class, 'findAsistencia']);

        //recarga-reajustes-resumen
        Route::get('/admin/recargas/recarga/{codigo}/reajustes', [App\Http\Controllers\Admin\RecargaReajustesController::class, 'returnReajustesRecarga']);

        //recarga-contratos-resumen
        Route::get('/admin/recargas/recarga/{codigo}/contratos', [App\Http\Controllers\Admin\RecargaContratosController::class, 'returnContratosRecarga']);

        //recarga-viaticos-resumen
        Route::get('/admin/recargas/recarga/{codigo}/viaticos', [App\Http\Controllers\Admin\RecargaViaticosController::class, 'returnViaticosRecarga']);

        //regla-resumen
        Route::get('/admin/recargas/recarga/{codigo}/resumen', [App\Http\Controllers\Admin\RecargaResumenController::class, 'returnFindRecarga']);
        Route::get('/admin/recargas/recarga/{codigo}/funcionarios', [App\Http\Controllers\Admin\RecargaResumenController::class, 'returnFuncionariosToRecarga']);
        Route::post('/admin/recargas/recarga/funcionario/beneficio', [App\Http\Controllers\Admin\RecargaResumenController::class, 'changeBeneficioToUser']);
        Route::post('/admin/recargas/recarga/funcionario/reajuste', [App\Http\Controllers\Admin\RecargaReajustesController::class, 'storeReajusteFuncionario']);

        //reajustes
        Route::get('/admin/recargas/reajuste/{uuid}', [App\Http\Controllers\Admin\RecargaReajustesController::class, 'returnFindReajuste']);
        Route::put('/admin/recargas/reajuste/{uuid}', [App\Http\Controllers\Admin\RecargaReajustesController::class, 'validateReajuste']);
        Route::post('/admin/recargas/reajuste', [App\Http\Controllers\Admin\RecargaReajustesController::class, 'storeReajuste']);

        //recarga-funcionario
        Route::get('/admin/recargas/recarga/{codigo}/funcionario/{uuid}', [App\Http\Controllers\Admin\RecargaFuncionarioController::class, 'returnFuncionario']);
        Route::get('/admin/recargas/recarga/{codigo}/funcionario/{uuid}/turnos', [App\Http\Controllers\Admin\RecargaFuncionarioController::class, 'returnTurnosFuncionario']);
        Route::get('/admin/recargas/recarga/{codigo}/funcionario/{uuid}/contratos', [App\Http\Controllers\Admin\RecargaFuncionarioController::class, 'returnContratosFuncionario']);
        Route::get('/admin/recargas/recarga/{codigo}/funcionario/{uuid}/ausentismos/{grupo}', [App\Http\Controllers\Admin\RecargaFuncionarioController::class, 'returnAusentismosFuncionario']);
        Route::get('/admin/recargas/recarga/{codigo}/funcionario/{uuid}/asistencias', [App\Http\Controllers\Admin\RecargaFuncionarioController::class, 'returnAsistenciasFuncionario']);
        Route::get('/admin/recargas/recarga/{codigo}/funcionario/{uuid}/reajustes', [App\Http\Controllers\Admin\RecargaFuncionarioController::class, 'returnReajustesFuncionario']);
        Route::get('/admin/recargas/recarga/{codigo}/funcionario/{uuid}/viaticos', [App\Http\Controllers\Admin\RecargaFuncionarioController::class, 'returnViaticosFuncionario']);

        Route::get('/admin/recargas/funcionario/{uuid}', [App\Http\Controllers\Admin\RecursosFuncionarioController::class, 'recargasFuncionario']);
    }
);


/* Route::group(['middleware' => ['cors']], function () {
    Route::post('/admin/recargas/recarga/masivo/funcionarios/import', [App\Http\Controllers\Admin\RecargasFilesController::class, 'storeAllFuncionarios']);
}); */

Route::get('/debug-sentry', function () {
    throw new Exception('Prueba Alimentación API - Error');
});
