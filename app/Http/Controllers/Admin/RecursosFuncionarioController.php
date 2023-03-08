<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Recarga;
use App\Models\User;
use Illuminate\Http\Request;

class RecursosFuncionarioController extends Controller
{
    public function recargasFuncionario($uuid, Request $request)
    {
        try {
            $recarga        = Recarga::where('codigo', $request->codigo_recarga)->firstOrFail();
            $funcionario    = User::where('uuid', $uuid)->firstOrFail();

            $recargas       = $funcionario->recargas()->where('recargas.id', '!=', $recarga->id)->with('establecimiento')->get();

            return response()->json(
                array(
                    'status'    => 'Success',
                    'title'     => null,
                    'message'   => null,
                    'user'      => $funcionario,
                    'recargas'  => $recargas
                )
            );
        } catch (\Exception $error) {
            return response()->json($error->getMessage());
        }
    }
}
