<?php

namespace App\Http\Controllers\ApiPublic;

use App\Http\Controllers\Controller;
use App\Models\Feriado;
use Illuminate\Http\Request;

class FeriadosController extends Controller
{
    public function getFeriados($year, $month = null)
    {
        try {
            $query = Feriado::query();

            if (!empty($year) && is_numeric($year)) {
                $query->whereYear('fecha', $year);
            }

            if (!empty($month) && is_numeric($month) && $month >= 1 && $month <= 12) {
                $query->whereMonth('fecha', $month);
            }

            $feriados = $query->orderBy('fecha', 'ASC')->get();

            if ($feriados->isEmpty()) {
                return response()->json([
                    'status'  => 'error',
                    'message' => 'No se encontraron feriados para los parámetros proporcionados.',
                    'data'    => []
                ], 404);
            }

            return response()->json([
                'status'  => 'success',
                'title'   => null,
                'message' => null,
                'data'    => $feriados
            ]);
        } catch (\Exception $error) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Ocurrió un error al obtener los feriados',
                'error'   => $error->getMessage()
            ], 500);
        }
    }
}
