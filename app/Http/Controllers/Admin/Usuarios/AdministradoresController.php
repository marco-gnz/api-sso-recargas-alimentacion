<?php

namespace App\Http\Controllers\Admin\Usuarios;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Usuarios\Administradores\StoreAdministradorRequest;
use App\Http\Requests\Admin\Usuarios\Administradores\UpdateAdministradorRequest;
use App\Http\Requests\Auth\UpdatePasswordUserRequest;
use App\Http\Resources\Usuarios\AdministradoresResource;
use App\Models\User;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

class AdministradoresController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth:sanctum']);
    }

    public function verifyUsuario(Request $request)
    {
        try {
            $response = null;
            $is_admin = null;
            $usuario  = User::with('roles')->where('rut', $request->rut)->first();

            if (!$usuario) {
                $response = null;
            } else if (($usuario) && (count($usuario->roles) <= 0)) {
                $response = $usuario;
                $is_admin = false;
            } else if (($usuario) && (count($usuario->roles) > 0)) {
                $response = $usuario;
                $is_admin = true;
            }

            return response()->json(
                array(
                    'status'        => 'Success',
                    'title'         => null,
                    'message'       => null,
                    'usuario'       => $response ? AdministradoresResource::make($response) : null,
                    'is_admin'      => $is_admin
                )
            );
        } catch (\Exception $error) {
            return response()->json($error->getMessage());
        }
    }

    public function getAdministradores(Request $request)
    {
        try {
            $roles      = Role::all()->pluck('name')->toArray();
            $usuarios   = User::role($roles)
                ->input($request->input)
                ->orderBy('apellidos', 'DESC')
                ->paginate(50);

            return response()->json(
                array(
                    'status'        => 'Success',
                    'title'         => null,
                    'message'       => null,
                    'pagination' => [
                        'total'         => $usuarios->total(),
                        'current_page'  => $usuarios->currentPage(),
                        'per_page'      => $usuarios->perPage(),
                        'last_page'     => $usuarios->lastPage(),
                        'from'          => $usuarios->firstItem(),
                        'to'            => $usuarios->lastPage()
                    ],
                    'usuarios'  => AdministradoresResource::collection($usuarios),
                )
            );
        } catch (\Exception $error) {
            return response()->json($error->getMessage());
        }
    }

    public function getAdministrador($uuid)
    {
        try {
            $usuario = User::where('uuid', $uuid)->firstOrFail();

            return response()->json(
                array(
                    'status'        => 'Success',
                    'title'         => null,
                    'message'       => null,
                    'usuario'       => AdministradoresResource::make($usuario),
                )
            );
        } catch (\Exception $error) {
            return response()->json($error->getMessage());
        }
    }

    public function addAdministrador(StoreAdministradorRequest $request)
    {
        try {
            $form = ['rut', 'dv', 'nombres', 'apellidos', 'email'];
            $usuario = User::create($request->only($form));
            if ($usuario) {
                $usuario->syncRoles($request->roles_id);

                if ($request->filled('establecimientos_id')) {
                    $usuario->establecimientos()->sync($request->establecimientos_id);
                }
                return response()->json(
                    array(
                        'status'        => 'Success',
                        'title'         => 'Usuario ingresado con éxito.',
                        'message'       => null,
                        'usuario'       => AdministradoresResource::make($usuario),
                    )
                );
            }
        } catch (\Exception $error) {
            return response()->json($error->getMessage());
        }
    }

    public function editAdministrador($uuid, UpdateAdministradorRequest $request)
    {
        try {
            $usuario = User::where('uuid', $uuid)->firstOrFail();
            if ($usuario) {
                $update = $usuario->update([
                    'rut'           => $request->rut,
                    'dv'            => $request->dv,
                    'nombres'       => $request->nombres,
                    'apellidos'     => $request->apellidos,
                    'email'         => $request->email,
                ]);

                $usuario->syncRoles($request->roles_id);
                if ($request->filled('establecimientos_id')) {
                    $usuario->establecimientos()->sync($request->establecimientos_id);
                }

                $usuario->syncPermissions($request->permisos_id);

                return response()->json(
                    array(
                        'status'        => 'Success',
                        'title'         => 'Usuario editado con éxito.',
                        'message'       => null,
                        'usuario'       => AdministradoresResource::make($usuario),
                    )
                );
            }
        } catch (\Exception $error) {
            return response()->json($error->getMessage());
        }
    }

    public function editAdministradorStatus($uuid)
    {
        try {
            $usuario = User::where('uuid', $uuid)->firstOrFail();
            if ($usuario) {
                $update = $usuario->update([
                    'estado'           => !$usuario->estado,
                ]);

                $status = $usuario->estado ? 'habilitado' : 'deshabilitado';

                return response()->json(
                    array(
                        'status'        => 'Success',
                        'title'         => "Usuario {$status} con éxito.",
                        'message'       => null,
                        'usuario'       => AdministradoresResource::make($usuario),
                    )
                );
            }
        } catch (\Exception $error) {
            return response()->json($error->getMessage());
        }
    }

    public function refreshPasswordAdministrador($uuid)
    {
        try {
            $usuario = User::where('uuid', $uuid)->firstOrFail();

            if ($usuario) {
                $update = $usuario->update([
                    'password'           => bcrypt($usuario->rut)
                ]);

                return response()->json(
                    array(
                        'status'        => 'Success',
                        'title'         => "Contraseña restablecida con éxito.",
                        'message'       => null,
                        'usuario'       => AdministradoresResource::make($usuario),
                    )
                );
            }
        } catch (\Exception $error) {
            return response()->json($error->getMessage());
        }
    }

    public function changePasswordAdministrador($uuid, UpdatePasswordUserRequest $request)
    {
        try {
            if (Hash::check($request->password, $request->user()->password)) {
                $usuario = User::where('uuid', $uuid)->firstOrFail();
                $usuario->password = Hash::make($request->new_password);

                $update = $usuario->save();

                if ($update) {
                    return response()->json(
                        array(
                            'status'        => 'Success',
                            'title'         => "Contraseña modificada con éxito.",
                            'message'       => null,
                        )
                    );
                }
            } else {
                return response(["errors" => ["password" => ["La contraseña actual es inconrrecta"]]], 422);
            }
        } catch (\Exception $error) {
            return response()->json($error->getMessage());
        }
    }

    public function getPermissionsAditional($uuid, Request $request)
    {
        try {
            $permissions    = [];
            $roles          = Role::whereIn('id', $request->roles_id)->get();

            foreach ($roles as $role) {
                foreach ($role->permissions as $permission) {
                    if (!in_array($permission->id, $permissions)) {
                        array_push($permissions, $permission->id);
                    }
                }
            }
            $permissions_aditional = Permission::whereNotIn('id', $permissions)->get();

            return response()->json(
                array(
                    'status'                => 'Success',
                    'title'                 => null,
                    'message'               => null,
                    'permissions_aditional' => $permissions_aditional,
                )
            );
        } catch (\Exception $error) {
            return response()->json($error->getMessage());
        }
    }
}
