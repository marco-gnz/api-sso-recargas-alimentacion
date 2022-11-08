<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Imports\Grupos\GrupoUnoImport;
use App\Imports\Grupos\GrupoUnoImportStore;
use App\Imports\UsersImport;
use App\Imports\UsersImportStore;
use App\Models\Recarga;
use App\Models\SeguimientoRecarga;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class RecargasFilesController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth:sanctum']);
    }

    protected function successResponse($data, $title = null, $message = null, $code = 200)
    {
        return response()->json([
            'status'    => 'Success',
            'title'     => $title,
            'message'   => $message,
            'data'      => $data
        ], $code);
    }

    protected function errorResponse($message = null, $code)
    {
        return response()->json([
            'status'    => 'Error',
            'message'   => $message,
            'data'      => null
        ], $code);
    }

    public function loadFileFuncionarios(Request $request)
    {
        try {
            $new_columnas   = [];
            $file           = request()->file('file');
            $recarga        = Recarga::where('codigo', $request->codigo_recarga)->with('establecimiento')->first();

            $row_columnas   = $request->row_columnas;
            $columnas       = $request->columnas;
            $columnas       = json_decode($columnas, true);

            foreach ($columnas as $columna) {
                array_push($new_columnas, $columna['nombre_columna']);
            }

            $import = new UsersImport($recarga, $new_columnas, $row_columnas);
            Excel::import($import, $file);

            if (count($import->data)) {
                return $this->successResponse($import->data, null, null, 200);
            } else {
                return $this->errorResponse('No existen registros.', 404);
            }
        } catch (\Exception $error) {
            return response()->json(array($error->getMessage(), $error->failures()));
        }
    }

    public function storeAllFuncionarios(Request $request)
    {
        try {
            $new_columnas   = [];
            $file           = request()->file('file');
            $recarga        = Recarga::where('codigo', $request->codigo_recarga)->with('establecimiento', 'users')->first();

            $row_columnas   = $request->row_columnas;
            $columnas       = $request->columnas;
            $columnas       = json_decode($columnas, true);

            foreach ($columnas as $columna) {
                array_push($new_columnas, $columna['nombre_columna']);
            }

            $import     = new UsersImportStore($recarga, $new_columnas, $row_columnas);
            $save       = Excel::import($import, $file);

            if ($recarga) {
                $estado = SeguimientoRecarga::create([
                    'recarga_id'    => $recarga->id,
                    'estado_id'     => 3
                ]);
            }
            $message = "{$import->importados} funcionarios importados, {$import->editados} funcionarios actualizados y {$import->cargados_recarga} funcionarios añadidos al periodo.";

            return $this->successResponse($save, 'Operación realizada con éxito', $message, 200);
        } catch (\Exception $error) {
            return response()->json(array($error->getMessage(), $error->failures()));
        }
    }

    public function loadFileGrupoUno(Request $request)
    {
        try {
            $file   = request()->file('file');
            $recarga = Recarga::where('codigo', $request->codigo)->with('establecimiento')->first();
            $import = new GrupoUnoImport($recarga);
            Excel::import($import, $file);

            if (count($import->data)) {
                return $this->successResponse($import->data, null, null, 200);
            } else {
                return $this->errorResponse('No existen registros.', 404);
            }
        } catch (\Exception $error) {
            return response()->json(array($error->getMessage(), $error->failures()));
        }
    }

    public function storeFileGrupoUno(Request $request)
    {
        try {

            $file   = request()->file('file');
            $recarga = Recarga::where('codigo', $request->codigo)->with('establecimiento')->first();
            $import     = new GrupoUnoImportStore($recarga);
            $save       = Excel::import($import, $file);

            if ($recarga) {
                $estado = SeguimientoRecarga::create([
                    'recarga_id'    => $recarga->id,
                    'estado_id'     => 4
                ]);
            }

            $message = $import->importados . ' ausentismos importados';

            return $this->successResponse($save, 'Operación realizada con éxito', $message, 200);
        } catch (\Exception $error) {
            return response()->json(array($error->getMessage(), $error->failures()));
        }
    }
}
