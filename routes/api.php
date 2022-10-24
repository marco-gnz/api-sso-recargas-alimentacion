<?php

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

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

Route::post('/login', [App\Http\Controllers\Auth\LoginController::class, 'login']);
Route::post('/logout', [App\Http\Controllers\Auth\LogoutController::class, 'logout']);

//Rutas administrador - Autenticado
Route::group(
    [
        'namespace' => 'Admin',
        'middleware' => 'auth:sanctum'
    ],
    function(){
        //modulos
        Route::get('/admin/modulos/establecimientos/response', [App\Http\Controllers\Admin\Modulos\ModulosResponseController::class, 'returnEstablecimientos']);
        Route::get('/admin/modulos/tipos-ausentismos/response', [App\Http\Controllers\Admin\Modulos\ModulosResponseController::class, 'returnTiposAusentismos']);
        Route::get('/admin/modulos/grupos-ausentismos/response', [App\Http\Controllers\Admin\Modulos\ModulosResponseController::class, 'returnGruposAusentismos']);
        Route::get('/admin/modulos/meridianos/response', [App\Http\Controllers\Admin\Modulos\ModulosResponseController::class, 'returnMeridianos']);

        Route::get('/admin/recargas/response', [App\Http\Controllers\Admin\RecargasController::class, 'returnRecargas']);
        Route::post('/admin/recargas/add', [App\Http\Controllers\Admin\RecargasController::class, 'storeRecarga']);
        Route::put('/admin/recargas/recarga/status/{id}', [App\Http\Controllers\Admin\RecargasController::class, 'changeStatus']);
        Route::get('/admin/recargas/recarga/{codigo}', [App\Http\Controllers\Admin\RecargasController::class, 'returnFindRecarga']);
        Route::put('/admin/recargas/recarga/datos-principales/{id}', [App\Http\Controllers\Admin\RecargasController::class, 'updateDatosPrincipales']);

        //files
        Route::post('/admin/recargas/recarga/masivo/funcionarios', [App\Http\Controllers\Admin\RecargasFilesController::class, 'loadFileFuncionarios']);
        Route::post('/admin/recargas/recarga/masivo/funcionarios/import', [App\Http\Controllers\Admin\RecargasFilesController::class, 'storeAllFuncionarios']);

        //grupos-reglas
        Route::get('/admin/recargas/grupos-ausentismos/{codigo}', [App\Http\Controllers\Admin\RecargasReglasController::class, 'returnTiposAusentismos']);
        Route::post('/admin/recargas/recarga/masivo/reglas/store', [App\Http\Controllers\Admin\RecargasReglasController::class, 'storeReglas']);
        Route::get('/admin/recargas/grupo/reglas', [App\Http\Controllers\Admin\RecargasReglasController::class, 'returnReglasToGrupo']);
    }
);


/* Route::group(['middleware' => ['cors']], function () {
    Route::post('/admin/recargas/recarga/masivo/funcionarios/import', [App\Http\Controllers\Admin\RecargasFilesController::class, 'storeAllFuncionarios']);
}); */
