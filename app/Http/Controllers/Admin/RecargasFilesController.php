<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Imports\AsistenciaImport;
use App\Imports\AsistenciaImportStore;
use App\Imports\Grupos\GrupoDosImport;
use App\Imports\Grupos\GrupoDosImportStore;
use App\Imports\Grupos\GrupoTresImport;
use App\Imports\Grupos\GrupoTresImportStore;
use App\Imports\Grupos\GrupoUnoImport;
use App\Imports\Grupos\GrupoUnoImportStore;
use App\Imports\UsersImport;
use App\Imports\UsersImportStore;
use App\Imports\UserTurnoImport;
use App\Imports\UserTurnoImportStore;
use App\Imports\ViaticosImport;
use App\Imports\ViaticosImportStore;
use App\Models\Recarga;
use App\Models\SeguimientoRecarga;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use Maatwebsite\Excel\HeadingRowImport;

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

            $row_columnas   = $request->row_columnas != null ? $request->row_columnas : 0;
            $columnas       = $request->columnas;
            $columnas       = json_decode($columnas, true);

            foreach ($columnas as $columna) {
                array_push($new_columnas, str_replace(' ', '_', strtolower($columna['nombre_columna'])));
            }

            $headings_file      = (new HeadingRowImport($row_columnas))->toArray($file);
            $validate_columns   = $this->validateColumns($new_columnas, $headings_file[0][0]);

            if (!$validate_columns[0]) {
                $message = "No se localizó el nombre de columna '{$validate_columns[1]}' en la posición {$row_columnas} para el archivo {$file->getClientOriginalName()}.";
                return $this->errorResponse($message, 404);
            } else {
                $import = new UsersImport($recarga, $new_columnas, $row_columnas);
                Excel::import($import, $file);


                if (count($import->data)) {
                    return $this->successResponse($import->data, null, null, 200);
                } else {
                    return $this->errorResponse('No existen registros.', 404);
                }
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

            $row_columnas   = $request->row_columnas != null ? $request->row_columnas : 0;
            $columnas       = $request->columnas;
            $columnas       = json_decode($columnas, true);

            foreach ($columnas as $columna) {
                array_push($new_columnas, str_replace(' ', '_', strtolower($columna['nombre_columna'])));
            }

            $headings_file      = (new HeadingRowImport($row_columnas))->toArray($file);
            $validate_columns   = $this->validateColumns($new_columnas, $headings_file[0][0]);

            if (!$validate_columns[0]) {
                $message = "No se localizó el nombre de columna '{$validate_columns[1]}' en la posición {$row_columnas} para el archivo {$file->getClientOriginalName()}.";
                return $this->errorResponse($message, 404);
            } else {
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
            }
        } catch (\Exception $error) {
            return response()->json(array($error->getMessage(), $error->failures()));
        }
    }

    public function validateColumns($new_columnas, $columnas_archivo)
    {
        $validate = true;

        $new_columnas_archivo = [];

        foreach ($columnas_archivo as $co) {
            array_push($new_columnas_archivo, strtolower($co));
        }

        foreach ($new_columnas as $columna) {
            if (!in_array($columna, $new_columnas_archivo)) {
                $validate = false;
                return [$validate, $columna];
            }
        }
        return [$validate, null];
    }

    public function loadFileGrupoUno(Request $request)
    {
        try {
            $new_columnas   = [];
            $file           = request()->file('file');
            $recarga        = Recarga::where('codigo', $request->codigo)->with('establecimiento')->first();

            $row_columnas   = $request->row_columnas != null ? $request->row_columnas : 0;
            $columnas       = $request->columnas;
            $columnas       = json_decode($columnas, true);

            foreach ($columnas as $columna) {
                array_push($new_columnas, str_replace(' ', '_', strtolower($columna['nombre_columna'])));
            }

            $headings_file      = (new HeadingRowImport($row_columnas))->toArray($file);
            $validate_columns   = $this->validateColumns($new_columnas, $headings_file[0][0]);

            if (!$validate_columns[0]) {
                $message = "No se localizó el nombre de columna '{$validate_columns[1]}' en la posición {$row_columnas} para el archivo {$file->getClientOriginalName()}.";
                return $this->errorResponse($message, 404);
            } else {
                $import = new GrupoUnoImport($recarga, $new_columnas, $row_columnas);
                Excel::import($import, $file);

                if (count($import->data)) {
                    return $this->successResponse($import->data, null, null, 200);
                } else {
                    return $this->errorResponse('No existen registros.', 404);
                }
            }
        } catch (\Exception $error) {
            return response()->json(array($error->getMessage(), $error->failures()));
        }
    }

    public function storeFileGrupoUno(Request $request)
    {
        try {
            $new_columnas   = [];
            $file           = request()->file('file');
            $recarga        = Recarga::where('codigo', $request->codigo)->with('establecimiento')->first();
            $row_columnas   = $request->row_columnas != null ? $request->row_columnas : 0;
            $columnas       = $request->columnas;
            $columnas       = json_decode($columnas, true);

            foreach ($columnas as $columna) {
                array_push($new_columnas, str_replace(' ', '_', strtolower($columna['nombre_columna'])));
            }

            $headings_file      = (new HeadingRowImport($row_columnas))->toArray($file);
            $validate_columns   = $this->validateColumns($new_columnas, $headings_file[0][0]);

            if (!$validate_columns[0]) {
                $message = "No se localizó el nombre de columna '{$validate_columns[1]}' en la posición {$row_columnas} para el archivo {$file->getClientOriginalName()}.";
                return $this->errorResponse($message, 404);
            } else {
                $import     = new GrupoUnoImportStore($recarga, $new_columnas, $row_columnas);
                $save       = Excel::import($import, $file);

                if ($recarga) {
                    $estado = SeguimientoRecarga::create([
                        'recarga_id'    => $recarga->id,
                        'estado_id'     => 4
                    ]);
                }
                $message = $import->importados . ' ausentismos importados';
                return $this->successResponse($save, 'Operación realizada con éxito', $message, 200);
            }
        } catch (\Exception $error) {
            return response()->json(array($error->getMessage(), $error->failures()));
        }
    }

    public function loadFileGrupoDos(Request $request)
    {
        try {
            $new_columnas   = [];
            $file           = request()->file('file');
            $recarga        = Recarga::where('codigo', $request->codigo)->with('establecimiento')->first();

            $row_columnas   = $request->row_columnas != null ? $request->row_columnas : 0;
            $columnas       = $request->columnas;
            $columnas       = json_decode($columnas, true);

            foreach ($columnas as $columna) {
                array_push($new_columnas, str_replace(' ', '_', strtolower($columna['nombre_columna'])));
            }

            $headings_file      = (new HeadingRowImport($row_columnas))->toArray($file);
            $validate_columns   = $this->validateColumns($new_columnas, $headings_file[0][0]);

            if (!$validate_columns[0]) {
                $message = "No se localizó el nombre de columna '{$validate_columns[1]}' en la posición {$row_columnas} para el archivo {$file->getClientOriginalName()}.";
                return $this->errorResponse($message, 404);
            } else {
                $import = new GrupoDosImport($recarga, $new_columnas, $row_columnas);

                Excel::import($import, $file);

                if (count($import->data)) {
                    return $this->successResponse($import->data, null, null, 200);
                } else {
                    return $this->errorResponse('No existen registros.', 404);
                }
            }
        } catch (\Exception $error) {
            return response()->json(array($error->getMessage(), $error->failures()));
        }
    }

    public function storeFileGrupoDos(Request $request)
    {
        try {
            $new_columnas   = [];
            $file           = request()->file('file');
            $recarga        = Recarga::where('codigo', $request->codigo)->with('establecimiento')->first();
            $row_columnas   = $request->row_columnas != null ? $request->row_columnas : 0;
            $columnas       = $request->columnas;
            $columnas       = json_decode($columnas, true);

            foreach ($columnas as $columna) {
                array_push($new_columnas, str_replace(' ', '_', strtolower($columna['nombre_columna'])));
            }

            $headings_file      = (new HeadingRowImport($row_columnas))->toArray($file);
            $validate_columns   = $this->validateColumns($new_columnas, $headings_file[0][0]);

            if (!$validate_columns[0]) {
                $message = "No se localizó el nombre de columna '{$validate_columns[1]}' en la posición {$row_columnas} para el archivo {$file->getClientOriginalName()}.";
                return $this->errorResponse($message, 404);
            } else {
                $import     = new GrupoDosImportStore($recarga, $new_columnas, $row_columnas);
                $save       = Excel::import($import, $file);

                $message = $import->importados . ' ausentismos importados';
                return $this->successResponse($save, 'Operación realizada con éxito', $message, 200);
            }
        } catch (\Exception $error) {
            return response()->json(array($error->getMessage(), $error->failures()));
        }
    }

    public function loadFileGrupoTres(Request $request)
    {
        try {
            $new_columnas   = [];
            $file           = request()->file('file');
            $recarga        = Recarga::where('codigo', $request->codigo)->with('establecimiento')->first();
            $row_columnas   = $request->row_columnas != null ? $request->row_columnas : 0;
            $columnas       = $request->columnas;
            $columnas       = json_decode($columnas, true);

            foreach ($columnas as $columna) {
                array_push($new_columnas, str_replace(' ', '_', strtolower($columna['nombre_columna'])));
            }

            $headings_file      = (new HeadingRowImport($row_columnas))->toArray($file);
            $validate_columns   = $this->validateColumns($new_columnas, $headings_file[0][0]);

            if (!$validate_columns[0]) {
                $message = "No se localizó el nombre de columna '{$validate_columns[1]}' en la posición {$row_columnas} para el archivo {$file->getClientOriginalName()}.";
                return $this->errorResponse($message, 404);
            } else {
                $import     = new GrupoTresImport($recarga, $new_columnas, $row_columnas);
                $save       = Excel::import($import, $file);

                if (count($import->data)) {
                    return $this->successResponse($import->data, null, null, 200);
                } else {
                    return $this->errorResponse('No existen registros.', 404);
                }
            }
        } catch (\Exception $error) {
            return response()->json(array($error->getMessage(), $error->failures()));
        }
    }

    public function storeFileGrupoTres(Request $request)
    {
        try {
            $new_columnas   = [];
            $file           = request()->file('file');
            $recarga        = Recarga::where('codigo', $request->codigo)->with('establecimiento')->first();
            $row_columnas   = $request->row_columnas != null ? $request->row_columnas : 0;
            $columnas       = $request->columnas;
            $columnas       = json_decode($columnas, true);

            foreach ($columnas as $columna) {
                array_push($new_columnas, str_replace(' ', '_', strtolower($columna['nombre_columna'])));
            }

            $headings_file      = (new HeadingRowImport($row_columnas))->toArray($file);
            $validate_columns   = $this->validateColumns($new_columnas, $headings_file[0][0]);

            if (!$validate_columns[0]) {
                $message = "No se localizó el nombre de columna '{$validate_columns[1]}' en la posición {$row_columnas} para el archivo {$file->getClientOriginalName()}.";
                return $this->errorResponse($message, 404);
            } else {
                $import     = new GrupoTresImportStore($recarga, $new_columnas, $row_columnas);
                $save       = Excel::import($import, $file);

                $message = $import->importados . ' ausentismos importados';
                return $this->successResponse($save, 'Operación realizada con éxito', $message, 200);
            }
        } catch (\Exception $error) {
            return response()->json(array($error->getMessage(), $error->failures()));
        }
    }

    public function loadFileTurnos(Request $request)
    {
        try {
            $new_columnas   = [];
            $file           = request()->file('file');
            $recarga        = Recarga::where('codigo', $request->codigo)->with('establecimiento')->first();
            $row_columnas   = $request->row_columnas != null ? $request->row_columnas : 0;
            $columnas       = $request->columnas;
            $columnas       = json_decode($columnas, true);

            foreach ($columnas as $columna) {
                $nom_column = mb_convert_encoding($columna['nombre_columna'], 'UTF-8', 'auto');
                array_push($new_columnas, str_replace(' ', '_', strtolower($nom_column)));
            }

            $headings_file      = (new HeadingRowImport($row_columnas))->toArray($file);
            $validate_columns   = $this->validateColumns($new_columnas, $headings_file[0][0]);

            if (!$validate_columns[0]) {
                $message = "No se localizó el nombre de columna '{$validate_columns[1]}' en la posición {$row_columnas} para el archivo {$file->getClientOriginalName()}.";
                return $this->errorResponse($message, 404);
            } else {
                $import = new UserTurnoImport($recarga, $new_columnas, $row_columnas);

                Excel::import($import, $file);

                if (count($import->data)) {
                    return $this->successResponse($import->data, null, null, 200);
                } else {
                    return $this->errorResponse('No existen registros.', 404);
                }
            }
        } catch (\Exception $error) {
            return response()->json(array($error->getMessage(), $error->failures()));
        }
    }

    public function storeFileTurnos(Request $request)
    {
        try {
            $new_columnas   = [];
            $file           = request()->file('file');
            $recarga        = Recarga::where('codigo', $request->codigo)->with('establecimiento')->first();

            $row_columnas   = $request->row_columnas != null ? $request->row_columnas : 0;
            $columnas       = $request->columnas;
            $columnas       = json_decode($columnas, true);

            foreach ($columnas as $columna) {

                $name = mb_convert_encoding($columna['nombre_columna'], 'UTF-8', 'UTF-8');
                $name = strtolower($name);
                $name = str_replace(' ', '_', $name);
                array_push($new_columnas, $name);
                /* array_push($new_columnas, str_replace(' ', '_', strtolower($columna['nombre_columna']))); */
            }

            $headings_file      = (new HeadingRowImport($row_columnas))->toArray($file);
            $validate_columns   = $this->validateColumns($new_columnas, $headings_file[0][0]);

            if (!$validate_columns[0]) {
                $message    = "No se localizó el nombre de columna '{$validate_columns[1]}' en la posición {$row_columnas} para el archivo {$file->getClientOriginalName()}.";
                return $this->errorResponse($message, 404);
            } else {
                $import     = new UserTurnoImportStore($recarga, $new_columnas, $row_columnas);
                $save       = Excel::import($import, $file);
                if ($recarga) {
                    $estado = SeguimientoRecarga::create([
                        'recarga_id'    => $recarga->id,
                        'estado_id'     => 4
                    ]);
                }
                $message = $import->importados . ' turnos importados';
                return $this->successResponse($save, 'Operación realizada con éxito', $message, 200);
            }
        } catch (\Exception $error) {
            return response()->json(array($error->getMessage(), $error->failures()));
        }
    }

    public function transformDate($value, $format = 'Y-m-d')
    {
        try {
            return Carbon::instance(\PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($value));
        } catch (\ErrorException $e) {
            return Carbon::createFromFormat($format, $value);
        }
    }

    public function transformDateExcel($number)
    {
        $format = Carbon::parse($number)->format('Y-m-d');
        $str_date = strtotime($format);
        $excel_date = floatval(25569 + $str_date / 86400);

        return $excel_date;
    }

    public function loadFileAsistencia(Request $request)
    {
        try {
            $new_columnas   = [];
            $file           = request()->file('file');
            $recarga        = Recarga::where('codigo', $request->codigo)->with('establecimiento')->first();

            $row_columnas   = $request->row_columnas != null ? $request->row_columnas : 0;
            $columnas       = $request->columnas;
            $columnas       = json_decode($columnas, true);

            foreach ($columnas as $columna) {
                if (!$columna['formato_excel']) {
                    array_push($new_columnas, str_replace(' ', '_', strtolower($columna['nombre_columna'])));
                } else {
                    array_push($new_columnas, $columna['formato_excel']);
                }
            }

            $headings_file      = (new HeadingRowImport($row_columnas))->toArray($file);
            $validate_columns   = $this->validateColumns($new_columnas, $headings_file[0][0]);

            if (!$validate_columns[0]) {
                $name_column_error = null;

                if (is_numeric($validate_columns[1])) {
                    $name_column_error = $this->transformDate($validate_columns[1]);
                    $name_column_error = Carbon::parse($name_column_error)->format('d-m-Y');
                } else {
                    $name_column_error = $validate_columns[1];
                }
                $message = "No se localizó el nombre de columna '{$name_column_error}' en la posición {$row_columnas} para el archivo {$file->getClientOriginalName()}.";
                return $this->errorResponse($message, 404);
            } else {
                $import = new AsistenciaImport($recarga, $new_columnas, $row_columnas);
                Excel::import($import, $file);

                if (count($import->data)) {
                    return $this->successResponse($import->data, null, null, 200);
                } else {
                    return $this->errorResponse('No existen registros.', 404);
                }
            }
        } catch (\Exception $error) {
            return response()->json(array($error->getMessage(), $error->failures()));
        }
    }

    public function storeFileAsistencia(Request $request)
    {
        try {
            $new_columnas   = [];
            $file           = request()->file('file');
            $recarga        = Recarga::where('codigo', $request->codigo)->with('establecimiento')->first();

            $row_columnas   = $request->row_columnas != null ? $request->row_columnas : 0;
            $columnas       = $request->columnas;
            $columnas       = json_decode($columnas, true);

            foreach ($columnas as $columna) {
                if (!$columna['formato_excel']) {
                    array_push($new_columnas, str_replace(' ', '_', strtolower($columna['nombre_columna'])));
                } else {
                    array_push($new_columnas, $columna['formato_excel']);
                }
            }

            $headings_file      = (new HeadingRowImport($row_columnas))->toArray($file);
            $validate_columns   = $this->validateColumns($new_columnas, $headings_file[0][0]);

            if (!$validate_columns[0]) {
                $name_column_error = null;

                if (is_numeric($validate_columns[1])) {
                    $name_column_error = $this->transformDate($validate_columns[1]);
                    $name_column_error = Carbon::parse($name_column_error)->format('d-m-Y');
                } else {
                    $name_column_error = $validate_columns[1];
                }
                $message = "No se localizó el nombre de columna '{$name_column_error}' en la posición {$row_columnas} para el archivo {$file->getClientOriginalName()}.";
                return $this->errorResponse($message, 404);
            } else {
                $import     = new AsistenciaImportStore($recarga, $new_columnas, $row_columnas);
                $save       = Excel::import($import, $file);

                $message    = "{$import->importados} registros importados y {$import->actualizados} registros fueron actualizados.";
                return $this->successResponse($save, 'Operación realizada con éxito', $message, 200);
            }
        } catch (\Exception $error) {
            return response()->json(array($error->getMessage(), $error->failures()));
        }
    }

    public function loadFileViaticos(Request $request)
    {
        try {
            $new_columnas   = [];
            $file           = request()->file('file');
            $recarga        = Recarga::where('codigo', $request->codigo)->with('establecimiento')->first();

            $row_columnas   = $request->row_columnas != null ? $request->row_columnas : 0;
            $columnas       = $request->columnas;
            $columnas       = json_decode($columnas, true);

            foreach ($columnas as $columna) {
                array_push($new_columnas, str_replace(' ', '_', strtolower($columna['nombre_columna'])));
            }

            $headings_file      = (new HeadingRowImport($row_columnas))->toArray($file);
            $validate_columns   = $this->validateColumns($new_columnas, $headings_file[0][0]);

            if (!$validate_columns[0]) {
                $name_column_error = null;

                if (is_numeric($validate_columns[1])) {
                    $name_column_error = $this->transformDate($validate_columns[1]);
                    $name_column_error = Carbon::parse($name_column_error)->format('d-m-Y');
                } else {
                    $name_column_error = $validate_columns[1];
                }
                $message = "No se localizó el nombre de columna '{$name_column_error}' en la posición {$row_columnas} para el archivo {$file->getClientOriginalName()}.";
                return $this->errorResponse($message, 404);
            } else {
                $import     = new ViaticosImport($recarga, $new_columnas, $row_columnas);
                Excel::import($import, $file);

                if (count($import->data)) {
                    return $this->successResponse($import->data, null, null, 200);
                } else {
                    return $this->errorResponse('No existen registros.', 404);
                }
            }
        } catch (\Exception $error) {
            return response()->json(array($error->getMessage(), $error->failures()));
        }
    }

    public function storeFileViaticos(Request $request)
    {
        try {
            $new_columnas   = [];
            $file           = request()->file('file');
            $recarga        = Recarga::where('codigo', $request->codigo)->with('establecimiento')->first();

            $row_columnas   = $request->row_columnas != null ? $request->row_columnas : 0;
            $columnas       = $request->columnas;
            $columnas       = json_decode($columnas, true);

            foreach ($columnas as $columna) {
                array_push($new_columnas, str_replace(' ', '_', strtolower($columna['nombre_columna'])));
            }

            $headings_file      = (new HeadingRowImport($row_columnas))->toArray($file);
            $validate_columns   = $this->validateColumns($new_columnas, $headings_file[0][0]);

            if (!$validate_columns[0]) {
                $name_column_error = null;

                if (is_numeric($validate_columns[1])) {
                    $name_column_error = $this->transformDate($validate_columns[1]);
                    $name_column_error = Carbon::parse($name_column_error)->format('d-m-Y');
                } else {
                    $name_column_error = $validate_columns[1];
                }
                $message = "No se localizó el nombre de columna '{$name_column_error}' en la posición {$row_columnas} para el archivo {$file->getClientOriginalName()}.";
                return $this->errorResponse($message, 404);
            } else {
                $import     = new ViaticosImportStore($recarga, $new_columnas, $row_columnas);
                $save       = Excel::import($import, $file);

                $message    = "{$import->importados} registros importados.";
                return $this->successResponse($save, 'Operación realizada con éxito', $message, 200);
            }
        } catch (\Exception $error) {
            return $error->getMessage();
            return response()->json(array($error->getMessage(), $error->failures()));
        }
    }
}
