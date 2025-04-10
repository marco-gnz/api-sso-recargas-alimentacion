<?php

use App\Models\Cartola;
use App\Models\Esquema;
use App\Models\Recarga;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\Calculos\ActualizarEsquemaController;
use App\Http\Controllers\ApiPublic\FeriadosController;
use App\Http\Controllers\Recarga\StatusRecargaController;
use App\Http\Resources\ApiUserResource;
use App\Models\Feriado;

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
    return response()->json(ApiUserResource::make(User::with(['roles.permissions', 'permissions'])->find(request()->user()->id)));
});

Route::post('/login', [App\Http\Controllers\Auth\LoginController::class, 'login']);
Route::post('/logout', [App\Http\Controllers\Auth\LogoutController::class, 'logout']);

//Rutas administrador - Autenticado
Route::group(
    [
        'namespace'     => 'Admin',
        'middleware'    => 'auth:sanctum'
    ],
    function () {
        Route::get('/admin/days-in-date', [App\Http\Controllers\Admin\Modulos\ModulosResponseController::class, 'returnDaysInDate']);
        Route::get('/admin/monto-in-days', [App\Http\Controllers\Admin\Modulos\ModulosResponseController::class, 'getMontoInDays']);
        Route::get('/admin/feriados/{codigo}', [App\Http\Controllers\Admin\Modulos\ModulosResponseController::class, 'returnFeriados']);

        //modulos
        Route::get('/admin/modulos/establecimientos/response', [App\Http\Controllers\Admin\Modulos\ModulosResponseController::class, 'returnEstablecimientos']);
        Route::get('/admin/modulos/leyes/response', [App\Http\Controllers\Admin\Modulos\ModulosResponseController::class, 'returnLeyes']);
        Route::get('/admin/modulos/unidades/response', [App\Http\Controllers\Admin\Modulos\ModulosResponseController::class, 'returnUnidades']);
        Route::get('/admin/modulos/unidades/response/recarga/{codigo_recarga}', [App\Http\Controllers\Admin\Modulos\ModulosResponseController::class, 'returnUnidadesRecarga']);
        Route::get('/admin/modulos/centro-costos/response/recarga/{codigo_recarga}', [App\Http\Controllers\Admin\Modulos\ModulosResponseController::class, 'returnCentroCostosRecarga']);
        Route::get('/admin/modulos/tipos-ausentismos/response', [App\Http\Controllers\Admin\Modulos\ModulosResponseController::class, 'returnTiposAusentismos']);
        Route::get('/admin/modulos/tipos-incrementos/response', [App\Http\Controllers\Admin\Modulos\ModulosResponseController::class, 'returnTiposIncrementos']);
        Route::get('/admin/modulos/grupos-ausentismos/response', [App\Http\Controllers\Admin\Modulos\ModulosResponseController::class, 'returnGruposAusentismos']);
        Route::get('/admin/modulos/grupos-ausentismos/response/recarga/{codigo_recarga}', [App\Http\Controllers\Admin\Modulos\ModulosResponseController::class, 'returnGruposAusentismosRecarga']);
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
        Route::get('/admin/modulos/columnas/ajustes', [App\Http\Controllers\Admin\Modulos\ColumnasImportController::class, 'columnasImportAjustes']);
        Route::get('/admin/modulos/roles/response', [App\Http\Controllers\Admin\Modulos\ModulosResponseController::class, 'getRoles']);
        Route::get('/admin/modulos/horas/response', [App\Http\Controllers\Admin\Modulos\ModulosResponseController::class, 'getHoras']);

        //mantenedores
        Route::get('/admin/mantenedores/contractual', [App\Http\Controllers\Admin\Mantenedores\ContractualesController::class, 'getDatoContractual']);
        Route::get('/admin/mantenedores/contractuales', [App\Http\Controllers\Admin\Mantenedores\ContractualesController::class, 'getDatosContractuales']);
        Route::post('/admin/mantenedores/contractuales/cargo', [App\Http\Controllers\Admin\Mantenedores\ContractualesController::class, 'storeCargo']);
        Route::post('/admin/mantenedores/contractuales/unidad', [App\Http\Controllers\Admin\Mantenedores\ContractualesController::class, 'storeUnidad']);
        Route::post('/admin/mantenedores/contractuales/asignacion', [App\Http\Controllers\Admin\Mantenedores\ContractualesController::class, 'storeAsignacion']);
        Route::put('/admin/mantenedores/contractuales/{id}/cargo', [App\Http\Controllers\Admin\Mantenedores\ContractualesController::class, 'updateCargo']);
        Route::put('/admin/mantenedores/contractuales/{id}/unidad', [App\Http\Controllers\Admin\Mantenedores\ContractualesController::class, 'updateUnidad']);
        Route::put('/admin/mantenedores/contractuales/{id}/asignacion', [App\Http\Controllers\Admin\Mantenedores\ContractualesController::class, 'updateAsignacion']);

        Route::get('/admin/mantenedores/variacion', [App\Http\Controllers\Admin\Mantenedores\VariacionesController::class, 'getVariacion']);
        Route::get('/admin/mantenedores/variaciones', [App\Http\Controllers\Admin\Mantenedores\VariacionesController::class, 'getVariaciones']);
        Route::post('/admin/mantenedores/variaciones/ausentismo', [App\Http\Controllers\Admin\Mantenedores\VariacionesController::class, 'storeAusentismo']);
        Route::post('/admin/mantenedores/variaciones/incremento', [App\Http\Controllers\Admin\Mantenedores\VariacionesController::class, 'storeIncremento']);
        Route::put('/admin/mantenedores/variaciones/{id}/ausentismo', [App\Http\Controllers\Admin\Mantenedores\VariacionesController::class, 'updateAusentismo']);
        Route::put('/admin/mantenedores/variaciones/{id}/incremento', [App\Http\Controllers\Admin\Mantenedores\VariacionesController::class, 'updateIncremento']);

        Route::get('/admin/recargas/response', [App\Http\Controllers\Admin\RecargasController::class, 'returnRecargas']);
        Route::post('/admin/recargas/add', [App\Http\Controllers\Admin\RecargasController::class, 'storeRecarga']);
        Route::put('/admin/recargas/recarga/status/{id}', [App\Http\Controllers\Admin\RecargasController::class, 'changeStatus']);
        Route::put('/admin/recargas/recarga/datos-principales/{id}', [App\Http\Controllers\Admin\RecargasController::class, 'updateDatosPrincipales']);

        //files
        Route::post('/admin/recargas/recarga/masivo/funcionarios', [App\Http\Controllers\Admin\RecargasFilesController::class, 'loadData']);
        Route::post('/admin/recargas/recarga/masivo/funcionarios/import', [App\Http\Controllers\Admin\RecargasFilesController::class, 'storeData']);
        Route::post('/admin/recargas/recarga/masivo/turnos', [App\Http\Controllers\Admin\RecargasFilesController::class, 'loadData']);
        Route::post('/admin/recargas/recarga/masivo/turnos/import', [App\Http\Controllers\Admin\RecargasFilesController::class, 'storeData']);
        //files-grupos
        Route::post('/admin/recargas/recarga/masivo/grupo/uno', [App\Http\Controllers\Admin\RecargasFilesController::class, 'loadData']);
        Route::post('/admin/recargas/recarga/masivo/grupo/uno/import', [App\Http\Controllers\Admin\RecargasFilesController::class, 'storeData']);
        Route::post('/admin/recargas/recarga/masivo/grupo/dos', [App\Http\Controllers\Admin\RecargasFilesController::class, 'loadData']);
        Route::post('/admin/recargas/recarga/masivo/grupo/dos/import', [App\Http\Controllers\Admin\RecargasFilesController::class, 'storeData']);
        Route::post('/admin/recargas/recarga/masivo/grupo/tres', [App\Http\Controllers\Admin\RecargasFilesController::class, 'loadData']);
        Route::post('/admin/recargas/recarga/masivo/grupo/tres/import', [App\Http\Controllers\Admin\RecargasFilesController::class, 'storeData']);

        //recarga-asistencia
        Route::post('/admin/recargas/recarga/masivo/asistencia', [App\Http\Controllers\Admin\RecargasFilesController::class, 'loadData']);
        Route::post('/admin/recargas/recarga/masivo/asistencia/import', [App\Http\Controllers\Admin\RecargasFilesController::class, 'storeData']);

        //recarga-viaticos
        Route::post('/admin/recargas/recarga/masivo/viaticos', [App\Http\Controllers\Admin\RecargasFilesController::class, 'loadData']);
        Route::post('/admin/recargas/recarga/masivo/viaticos/import', [App\Http\Controllers\Admin\RecargasFilesController::class, 'storeData']);

        //recarga-ajustes
        Route::post('/admin/recargas/recarga/masivo/ajustes', [App\Http\Controllers\Admin\RecargasFilesController::class, 'loadData']);
        Route::post('/admin/recargas/recarga/masivo/ajustes/import', [App\Http\Controllers\Admin\RecargasFilesController::class, 'storeData']);

        //recarga-feriados
        Route::post('/admin/recargas/feriados', [App\Http\Controllers\Admin\RecargaFeriadosController::class, 'storeFeriados']);
        Route::put('/admin/recargas/feriados/eliminar/{id_recarga}/{codigo_recarga}', [App\Http\Controllers\Admin\RecargaFeriadosController::class, 'deleteFeriadoInRecarga']);

        //grupos-reglas
        Route::get('/admin/recargas/grupos-ausentismos/{codigo}', [App\Http\Controllers\Admin\RecargasReglasController::class, 'returnTiposAusentismos']);
        Route::post('/admin/recargas/recarga/masivo/reglas/store', [App\Http\Controllers\Admin\RecargasReglasController::class, 'storeReglas']);
        Route::get('/admin/recargas/grupo/reglas', [App\Http\Controllers\Admin\RecargasReglasController::class, 'returnReglasToGrupo']);
        Route::delete('/admin/recargas/regla/eliminar/{id}', [App\Http\Controllers\Admin\RecargasReglasController::class, 'deleteReglaInRecarga']);
        Route::get('/admin/recargas/regla/{id}', [App\Http\Controllers\Admin\RecargasReglasController::class, 'getRegla']);
        Route::put('/admin/recargas/regla/{id}', [App\Http\Controllers\Admin\RecargasReglasController::class, 'updateRegla']);

        //recarga-asistencia-resumen
        Route::get('/admin/recargas/recarga/{codigo}/asistencias', [App\Http\Controllers\Admin\RecargaAsistenciaController::class, 'asistenciasRecarga']);
        Route::put('/admin/asistencias/{id}', [App\Http\Controllers\Admin\RecargaAsistenciaController::class, 'updateAsistencia']);
        Route::get('/admin/asistencias/{uuid}', [App\Http\Controllers\Admin\RecargaAsistenciaController::class, 'findAsistencia']);

        //recarga-reajustes-resumen
        Route::get('/admin/recargas/recarga/{codigo}', [App\Http\Controllers\Admin\RecargasController::class, 'returnFindRecarga']);
        Route::get('/admin/recargas/recarga/{codigo}/carga-datos', [App\Http\Controllers\Admin\RecargasController::class, 'returnFindRecargaCargaDatos']);
        Route::get('/admin/recargas/recarga/{codigo}/contratos', [App\Http\Controllers\Admin\RecargaContratosController::class, 'returnContratosRecarga']);
        Route::get('/admin/recargas/recarga/{codigo}/asignaciones', [App\Http\Controllers\Admin\RecargaAsignacionesController::class, 'returnAsignacionesRecarga']);
        Route::get('/admin/recargas/recarga/{codigo}/ausentismos', [App\Http\Controllers\Admin\RecargaAusentismosController::class, 'returnAusentismosRecarga']);
        Route::get('/admin/recargas/recarga/{codigo}/resumen', [App\Http\Controllers\Admin\RecargaResumenController::class, 'returnFindRecarga']);
        Route::get('/admin/recargas/recarga/{codigo}/viaticos', [App\Http\Controllers\Admin\RecargaViaticosController::class, 'returnViaticosRecarga']);
        Route::get('/admin/recargas/recarga/{codigo}/reajustes', [App\Http\Controllers\Admin\Recarga\ModulosController::class, 'getAjustes']);
        Route::get('/admin/recargas/recarga/{codigo}/not-funcionarios', [App\Http\Controllers\Admin\RecargaResumenController::class, 'searchNotFuncionarios']);

        Route::get('/admin/recargas/recarga/{codigo}/funcionarios', [App\Http\Controllers\Admin\RecargaResumenController::class, 'returnFuncionariosToRecarga']);
        Route::post('/admin/recargas/recarga/funcionario/beneficio', [App\Http\Controllers\Admin\RecargaResumenController::class, 'changeBeneficioToUser']);
        Route::post('/admin/recargas/recarga/funcionario/turno', [App\Http\Controllers\Admin\RecargaResumenController::class, 'changeTurnoToUser']);
        Route::post('/admin/recargas/recarga/funcionario/reemplazo', [App\Http\Controllers\Admin\RecargaResumenController::class, 'changeReemplazoToUser']);
        Route::post('/admin/recargas/recarga/funcionario/reajuste', [App\Http\Controllers\Admin\Reajustes\ReajustesController::class, 'storeReajusteResumen']);
        Route::post('/admin/recargas/recarga/funcionario/store-manual', [App\Http\Controllers\Admin\RecargaResumenController::class, 'storeManualEsquema']);
        Route::post('/admin/recargas/recarga/{codigo}/resumen/status-esquemas', [App\Http\Controllers\Admin\RecargaResumenController::class, 'updateEsquemasStatus']);
        Route::post('/admin/recargas/recarga/{codigo}/resumen/delete/{modulo}', [App\Http\Controllers\Admin\RecargaResumenController::class, 'deleteDataRecarga']);
        Route::get('/admin/recargas/recarga/{codigos_recarga}/funcionario/{id}', [App\Http\Controllers\Admin\RecargaResumenController::class, 'getDatosContractualesFuncionario']);
        Route::get('/admin/recargas/recarga/{codigo_recarga}/funcionario/{id}/recargas-adicional', [App\Http\Controllers\Admin\RecargaResumenController::class, 'getRecargasFuncionarioAdicional']);
        Route::delete('/admin/recargas/recarga/funcionario/{esquema_id}/delete', [App\Http\Controllers\Admin\RecargaResumenController::class, 'deleteEsquema']);

        //reajustes
        Route::get('/admin/recargas/reajuste/{uuid}', [App\Http\Controllers\Admin\RecargaReajustesController::class, 'returnFindReajuste']);
        Route::put('/admin/recargas/reajuste/{uuid}', [App\Http\Controllers\Admin\Reajustes\ReajustesController::class, 'validateReajuste']);
        Route::put('/admin/recargas/reajuste/{uuid}/resumen', [App\Http\Controllers\Admin\Reajustes\ReajustesController::class, 'validateReajusteResumen']);
        Route::post('/admin/recargas/reajuste', [App\Http\Controllers\Admin\Reajustes\ReajustesController::class, 'storeReajuste']);
        Route::get('/admin/recargas/reajuste/{uuid}/contratos/{periodo}', [App\Http\Controllers\Admin\RecargaReajustesController::class, 'getContratosFiltro']);

        //recarga-funcionario
        Route::get('/admin/recargas/recarga/{codigo}/funcionario/{uuid}', [App\Http\Controllers\Admin\RecargaFuncionarioController::class, 'returnFuncionario']);
        Route::get('/admin/recargas/recarga/{codigo}/funcionario/{uuid}/esquema', [App\Http\Controllers\Admin\RecargaFuncionarioController::class, 'returnEsquemaFuncionario']);
        Route::get('/admin/recargas/recarga/{codigo}/funcionario/{uuid}/turnos', [App\Http\Controllers\Admin\RecargaFuncionarioController::class, 'returnTurnosFuncionario']);
        Route::get('/admin/recargas/recarga/{codigo}/funcionario/{uuid}/contratos', [App\Http\Controllers\Admin\RecargaFuncionarioController::class, 'returnContratosFuncionario']);
        Route::get('/admin/recargas/recarga/{codigo}/funcionario/{uuid}/ausentismos/{grupo}', [App\Http\Controllers\Admin\RecargaFuncionarioController::class, 'returnAusentismosFuncionario']);
        Route::get('/admin/recargas/recarga/{codigo}/funcionario/{uuid}/asistencias', [App\Http\Controllers\Admin\RecargaFuncionarioController::class, 'returnAsistenciasFuncionario']);
        Route::get('/admin/recargas/recarga/{codigo}/funcionario/{uuid}/reajustes', [App\Http\Controllers\Admin\RecargaFuncionarioController::class, 'returnReajustesFuncionario']);
        Route::get('/admin/recargas/recarga/{codigo}/funcionario/{uuid}/viaticos', [App\Http\Controllers\Admin\RecargaFuncionarioController::class, 'returnViaticosFuncionario']);

        Route::get('/admin/recargas/funcionario/{uuid}', [App\Http\Controllers\Admin\RecursosFuncionarioController::class, 'recargasFuncionario']);

        //esquema-funcionario
        Route::get('/admin/esquema/{uuid}/detalle', [App\Http\Controllers\Admin\Esquema\EsquemaController::class, 'esquemaDetalle']);
        Route::get('/admin/esquema/{uuid}/asignaciones', [App\Http\Controllers\Admin\Esquema\EsquemaController::class, 'esquemaAsignaciones']);
        Route::get('/admin/esquema/{uuid}/contratos', [App\Http\Controllers\Admin\Esquema\EsquemaController::class, 'esquemaContratos']);
        Route::get('/admin/esquema/{uuid}/turnos', [App\Http\Controllers\Admin\Esquema\EsquemaController::class, 'esquemaTurnos']);
        Route::get('/admin/esquema/{uuid}/ausentismos/{n_grupo}', [App\Http\Controllers\Admin\Esquema\EsquemaController::class, 'esquemaAusentismos']);
        Route::get('/admin/esquema/{uuid}/viaticos', [App\Http\Controllers\Admin\Esquema\EsquemaController::class, 'esquemaViaticos']);
        Route::get('/admin/esquema/{uuid}/ajustes', [App\Http\Controllers\Admin\Esquema\EsquemaController::class, 'esquemaAjustes']);

        Route::get('/admin/usuarios/funcionarios', [App\Http\Controllers\Admin\Usuarios\FuncionariosController::class, 'getFuncionarios']);
        Route::get('/admin/usuarios/funcionario/{uuid}', [App\Http\Controllers\Admin\Usuarios\FuncionariosController::class, 'getFuncionario']);
        Route::put('/admin/usuarios/funcionario/{uuid}', [App\Http\Controllers\Admin\Usuarios\FuncionariosController::class, 'editFuncionario']);
        Route::post('/admin/usuarios/funcionario', [App\Http\Controllers\Admin\Usuarios\FuncionariosController::class, 'addFuncionario']);

        Route::get('/admin/usuarios/administradores', [App\Http\Controllers\Admin\Usuarios\AdministradoresController::class, 'getAdministradores']);
        Route::get('/admin/usuarios/administradores/verify', [App\Http\Controllers\Admin\Usuarios\AdministradoresController::class, 'verifyUsuario']);
        Route::post('/admin/usuarios/administrador', [App\Http\Controllers\Admin\Usuarios\AdministradoresController::class, 'addAdministrador']);
        Route::put('/admin/usuarios/administrador/{uuid}', [App\Http\Controllers\Admin\Usuarios\AdministradoresController::class, 'editAdministrador']);
        Route::get('/admin/usuarios/administradores/{uuid}', [App\Http\Controllers\Admin\Usuarios\AdministradoresController::class, 'getAdministrador']);
        Route::put('/admin/usuarios/administrador/{uuid}/status', [App\Http\Controllers\Admin\Usuarios\AdministradoresController::class, 'editAdministradorStatus']);
        Route::put('/admin/usuarios/administrador/{uuid}/refresh-password', [App\Http\Controllers\Admin\Usuarios\AdministradoresController::class, 'refreshPasswordAdministrador']);
        Route::put('/admin/usuarios/administrador/{uuid}/change-password', [App\Http\Controllers\Admin\Usuarios\AdministradoresController::class, 'changePasswordAdministrador']);
        Route::get('/admin/usuarios/administradores/permissions/{uuid}', [App\Http\Controllers\Admin\Usuarios\AdministradoresController::class, 'getPermissionsAditional']);

        Route::get('/public/funcionarios', [App\Http\Controllers\Enviar\EsquemaController::class, 'getFuncionarios']);
        Route::get('/public/funcionario/{uuid}/esquemas', [App\Http\Controllers\Enviar\EsquemaController::class, 'getEsquemasFuncionario']);
        Route::post('/public/enviar/cartola', [App\Http\Controllers\Enviar\EsquemaController::class, 'enviarCartola']);
    }
);

Route::group(
    [
        'namespace'     => 'Recarga',
        'middleware'    => 'auth:sanctum'
    ],
    function () {
        Route::get('/admin/recarga/{uuid}/resumen/publicar', [StatusRecargaController::class, 'publicarRecarga']);
        Route::post('/admin/recarga/{uuid}/resumen/publicar', [StatusRecargaController::class, 'publicarRecargaAction']);

        Route::get('/admin/recarga/{uuid}/resumen/generar', [StatusRecargaController::class, 'generarPlanilla']);
        Route::post('/admin/recarga/{uuid}/resumen/generar', [StatusRecargaController::class, 'generarPlanillaAction']);

        Route::post('/admin/recarga/{uuid}/emails/send', [StatusRecargaController::class, 'sendEmailsCartola']);
    }
);

Route::get('/debug-sentry', function () {
    throw new Exception('Prueba Alimentación API - Error');
});

Route::get('/admin/eliminar-cargas/{codigo}', [StatusRecargaController::class, 'eliminarCarga']);

Route::get('/feriados/{year?}/{month?}', [FeriadosController::class, 'getFeriados']);
