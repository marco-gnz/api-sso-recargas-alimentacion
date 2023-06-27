<?php

namespace App\Http\Controllers\Admin\Usuarios;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Usuarios\Funcionarios\StoreFuncionarioRequest;
use App\Http\Requests\Admin\Usuarios\Funcionarios\UpdateFuncionarioRequest;
use App\Http\Resources\Usuarios\FuncionariosResource;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class FuncionariosController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth:sanctum']);
    }

    private function withContratos()
    {
        $function = ['contratos' => function ($query) {
            $query->orderBy('fecha_termino_periodo', 'DESC');
        }];
        return $function;
    }

    private function withEsquemas()
    {
        $function = ['esquemas' => function ($query) {
            $query->orderBy('date_created_user', 'DESC');
        }];
        return $function;
    }

    public function getFuncionarios(Request $request)
    {
        try {
            $usuario_auth       = Auth::user();
            $establecimientos   = $usuario_auth->establecimientos;
            $withContratos      = $this->withContratos();
            $withEsquemas       = $this->withEsquemas();

            if ($usuario_auth->hasRole(['ADMIN.JEFE-PERSONAL'])) {
                $funcionarios = User::whereHas('contratos', function ($q) use ($establecimientos) {
                    $q->whereIn('establecimiento_id', $establecimientos->pluck('id'));
                })
                    ->with($withContratos)
                    ->with($withEsquemas);
            } else {
                $funcionarios = User::with($withContratos)
                    ->with($withEsquemas);
            }

            $funcionarios = $funcionarios
                ->input($request->input)
                ->orderBy('apellidos', 'asc')
                ->paginate(100);

            $pagination = [
                'total'         => $funcionarios->total(),
                'current_page'  => $funcionarios->currentPage(),
                'per_page'      => $funcionarios->perPage(),
                'last_page'     => $funcionarios->lastPage(),
                'from'          => $funcionarios->firstItem(),
                'to'            => $funcionarios->lastPage()
            ];

            $response = [
                'status'        => 'Success',
                'title'         => null,
                'message'       => null,
                'pagination'    => $pagination,
                'funcionarios'  => FuncionariosResource::collection($funcionarios),
            ];

            return response()->json($response);
        } catch (\Exception $error) {
            return response()->json($error->getMessage());
        }
    }


    public function getFuncionario($uuid)
    {
        try {
            $funcionario = User::where('uuid', $uuid)->firstOrFail();

            return response()->json(
                array(
                    'status'        => 'Success',
                    'title'         => null,
                    'message'       => null,
                    'funcionario'  => FuncionariosResource::make($funcionario),
                )
            );
        } catch (\Exception $error) {
            return response()->json($error->getMessage());
        }
    }

    public function addFuncionario(StoreFuncionarioRequest $request)
    {
        try {
            $form = ['rut', 'dv', 'nombres', 'apellidos', 'email'];
            $funcionario = User::create($request->only($form));

            if ($funcionario) {
                return response()->json(
                    array(
                        'status'        => 'Success',
                        'title'         => 'Funcionario ingresado con éxito.',
                        'message'       => null,
                        'funcionario'  => FuncionariosResource::make($funcionario),
                    )
                );
            }
        } catch (\Exception $error) {
            return response()->json($error->getMessage());
        }
    }

    public function editFuncionario($uuid, UpdateFuncionarioRequest $request)
    {
        try {
            $funcionario = User::where('uuid', $uuid)->firstOrFail();

            if ($funcionario) {
                $update = $funcionario->update([
                    'rut'           => $request->rut,
                    'dv'            => $request->dv,
                    'nombres'       => $request->nombres,
                    'apellidos'     => $request->apellidos,
                    'email'         => $request->email,
                ]);

                if ($update) {
                    return response()->json(
                        array(
                            'status'        => 'Success',
                            'title'         => 'Funcionario modificado con éxito.',
                            'message'       => null,
                            'funcionario'  => FuncionariosResource::make($funcionario),
                        )
                    );
                }
            }
        } catch (\Exception $error) {
            return response()->json($error->getMessage());
        }
    }
}
