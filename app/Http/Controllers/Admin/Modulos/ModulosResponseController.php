<?php

namespace App\Http\Controllers\Admin\Modulos;

use App\Http\Controllers\Controller;
use App\Models\Establecimiento;
use App\Models\GrupoAusentismo;
use App\Models\Meridiano;
use App\Models\TipoAsistenciaTurno;
use App\Models\TipoAusentismo;
use Illuminate\Http\Request;

class ModulosResponseController extends Controller
{
    public function returnEstablecimientos()
    {
        try {
            $establecimientos = Establecimiento::orderBy('nombre', 'asc')->get();

            return response()->json($establecimientos, 200);
        } catch (\Exception $error) {
            return $error->getMessage();
        }
    }

    public function returnTiposAusentismos()
    {
        try {
            $tipos_ausentismos = TipoAusentismo::orderBy('nombre', 'asc')->get();

            return response()->json($tipos_ausentismos, 200);
        } catch (\Exception $error) {
            return $error->getMessage();
        }
    }

    public function returnMeridianos()
    {
        try {
            $meridianos = Meridiano::orderBy('id', 'asc')->get();

            return response()->json($meridianos, 200);
        } catch (\Exception $error) {
            return $error->getMessage();
        }
    }

    public function returnGruposAusentismos()
    {
        try {
            $grupos_ausentismos = GrupoAusentismo::where('estado', true)->orderBy('nombre', 'asc')->get();

            return response()->json($grupos_ausentismos, 200);
        } catch (\Exception $error) {
            return $error->getMessage();
        }
    }

    public function returnTipoAsistenciaTurno()
    {
        try {
            $tipos_asistencia_turnos = TipoAsistenciaTurno::orderBy('nombre', 'asc')->get();

            return response()->json($tipos_asistencia_turnos, 200);
        } catch (\Exception $error) {
            return $error->getMessage();
        }
    }
}
