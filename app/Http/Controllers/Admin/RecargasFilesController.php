<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
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
            $file   = request()->file('file');
            $import = new UsersImport;
            Excel::import($import, $file);

            if (count($import->data)) {
                return $this->successResponse($import->data, null, null, 200);
            } else {
                return $this->errorResponse('No existen registros.', 404);
            }

            return $import->data;
        } catch (\Exception $error) {
            return response()->json(array($error->getMessage(), $error->failures()));
        }
    }

    public function storeAllFuncionarios(Request $request)
    {
        try {
            $recarga    = Recarga::where('codigo', $request->codigo_recarga)->first();

            $file       = request()->file('file');
            $import     = new UsersImportStore;
            $save       = Excel::import($import, $file);

            if ($recarga) {
                $estado = SeguimientoRecarga::create([
                    'recarga_id'    => $recarga->id,
                    'estado_id'     => 3
                ]);
            }

            $message = $import->importados . ' funcionarios importados y ' . $import->editados . ' funcionarios fueron actualizados.';

            return $this->successResponse($save, 'Operación realizada con éxito', $message, 200);
        } catch (\Exception $error) {
            return response()->json(array($error->getMessage(), $error->failures()));
        }
    }
}
