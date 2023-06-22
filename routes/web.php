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

Route::get('email', function(){
    $uuids = ['bab92a3e-a6e4-4a72-8b69-b73cfabe50f6', '1e651efc-8117-42fb-b66f-1aff0d4b14fc'];
    $cartolas = App\Models\Esquema::whereIn('uuid', $uuids)->get();
    return new App\Mail\CartolaLinks($cartolas);
});
