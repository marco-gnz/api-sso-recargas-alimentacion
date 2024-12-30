<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', function () {
    return view('welcome');
});

Route::get('/funcionario/cartola/{uuid_esquema}', [App\Http\Controllers\Funcionario\PdfController::class, 'showCartolaRecarga']);

Route::get('email', function () {
    $esquemas = App\Models\Esquema::select('esquemas.*')
    ->join('recargas', 'recargas.id', '=', 'esquemas.recarga_id')
    ->where('esquemas.user_id', 3)
    ->orderBy('recargas.anio_beneficio', 'DESC')
    ->orderBy('recargas.mes_beneficio', 'DESC')
    ->take(5)
    ->get();
    return new App\Mail\CartolaLinks($esquemas);
});
