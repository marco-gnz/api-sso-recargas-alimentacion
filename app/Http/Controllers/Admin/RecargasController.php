<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Recargas\StoreRecargaRequest;
use App\Http\Requests\Admin\Recargas\UpdateDatosPrincipalesRecargaRequest;
use App\Http\Resources\RecargaCargaDatosResource;
use App\Http\Resources\RecargaResource;
use App\Models\Recarga;
use App\Models\SeguimientoRecarga;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class RecargasController extends Controller
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

    protected function errorClientResponse($title = null, $message = null, $code = 400)
    {
        return response()->json([
            'status'    => 'Success',
            'title'     => $title,
            'message'   => $message,
            'data'      => null
        ], $code);
    }

    protected function errorResponse($message = null, $code)
    {
        return response()->json([
            'status'    => 'Error',
            'message'   => $message,
            'data'      => null
        ], $code);

        /* return $this->errorClientResponse('No es posible', 'Ya existe otro',100); */
    }

    protected function unauthorizedResponse()
    {
        return response()->json([
            'status'    => 'Error',
            'message'   => 'Unauthorized',
            'data'      => null
        ], 401);
    }

    private function withRecarga()
    {
        $with = [
            'seguimiento',
            'feriados',
            'reglas.grupoAusentismo',
            'reglas.tipoAusentismo',
            'reglas.meridianos',
            'establecimiento',
            'userCreatedBy',
            'userUpdateBy',
            'users',
            'reajustes'
        ];

        return $with;
    }

    public function returnRecargas()
    {
        try {
            $usuario_auth       = Auth::user();
            $establecimientos   = $usuario_auth->establecimientos;
            $recargas           = Recarga::where(function($query) use($establecimientos)  {
                $query->whereIn('establecimiento_id', $establecimientos->pluck('id'));
            })
            ->withCount('users')
                ->withCount('ausentismos')
                ->withCount('asignaciones')
                ->withCount('reajustes')
                ->withCount('contratos')
                ->withCount('viaticos')
                ->withCount('esquemas')
                ->orderBy('anio_beneficio', 'DESC')
                ->orderBy('mes_beneficio', 'DESC')
                ->get();

            return $this->successResponse(RecargaResource::collection($recargas), null, null, 200);
        } catch (\Exception $error) {
            return $error->getMessage();
            /* return $this->errorResponse($error->getMessage(), 500); */
        }
    }

    public function returnFindRecarga($codigo)
    {
        try {
            $with = $this->withRecarga();
            $recarga = Recarga::where('codigo', $codigo)
                ->with($with)
                ->withCount('users')
                ->withCount('ausentismos')
                ->withCount('asignaciones')
                ->withCount('reajustes')
                ->withCount('contratos')
                ->withCount('viaticos')
                ->withCount('esquemas')
                ->first();

            if ($recarga) {
                return $this->successResponse(RecargaResource::make($recarga), null, null, 200);
            } else {
                return $this->errorResponse('No existen registros.', 404);
            }
        } catch (\Exception $error) {
            return $error->getMessage();
            /* return $this->errorResponse($error->getMessage(), 500); */
        }
    }

    public function returnFindRecargaCargaDatos($codigo)
    {
        try {
            $with = $this->withRecarga();
            $recarga = Recarga::where('codigo', $codigo)
                ->with($with)
                ->withCount('users')
                ->withCount('ausentismos')
                ->withCount('asignaciones')
                ->withCount('reajustes')
                ->withCount('contratos')
                ->withCount('viaticos')
                ->withCount('esquemas')
                ->first();

            if ($recarga) {
                return $this->successResponse(RecargaCargaDatosResource::make($recarga), null, null, 200);
            } else {
                return $this->errorResponse('No existen registros.', 404);
            }
        } catch (\Exception $error) {
            return $error->getMessage();
            /* return $this->errorResponse($error->getMessage(), 500); */
        }
    }


    public function storeRecarga(StoreRecargaRequest $request)
    {
        try {
            $existe = $this->validateDuplicateRecarga($request);
            if ($existe) {
                return response()->json([
                    'errors' => ['data' => ['No es posible ingresar la recarga, existe un registro idéntico.']]
                ], 422);
            } else {
                $with = $this->withRecarga();
                $form = ['anio_beneficio', 'mes_beneficio', 'anio_calculo', 'mes_calculo', 'monto_dia', 'establecimiento_id'];
                $recarga = Recarga::create($request->only($form));

                if ($recarga) {
                    $estado = SeguimientoRecarga::create([
                        'recarga_id'    => $recarga->id,
                        'estado_id'     => 1
                    ]);
                    $recarga    = $recarga->fresh($with);
                    return $this->successResponse(RecargaResource::make($recarga), 'Recarga con código ' . $recarga->codigo . ' ingresada con éxito', null, 200);
                } else {
                    return $this->errorResponse('Error de servidor', 500);
                }
            }
        } catch (\Exception $error) {
            return $error->getMessage();
        }
    }

    public function updateDatosPrincipales(UpdateDatosPrincipalesRecargaRequest $request, $id)
    {
        try {
            $recarga = Recarga::find($id);

            if ($recarga) {
                $with = $this->withRecarga();
                $form = ['monto_dia'];
                $update = $recarga->update($request->only($form));

                if ($update) {
                    $estado = SeguimientoRecarga::create([
                        'recarga_id'    => $recarga->id,
                        'estado_id'     => 7
                    ]);
                    $with       = $this->withRecarga();
                    $recarga    = $recarga->fresh($with);
                    return $this->successResponse(RecargaResource::make($recarga), 'Recarga con código #' . $recarga->codigo . ' editada con éxito.', null, 200);
                } else {
                    return $this->errorResponse('Error de servidor', 500);
                }
            } else {
                return $this->errorResponse('Error de servidor', 500);
            }
        } catch (\Exception $error) {
            return $error->getMessage();
        }
    }

    public function changeStatus($id)
    {
        try {
            $recarga = Recarga::find($id);

            if ($recarga) {
                $update = $recarga->update([
                    'active' => !$recarga->active
                ]);

                if ($update) {
                    $with       = $this->withRecarga();
                    $recarga    = $recarga->fresh($with);
                    $status     = $recarga->active ? 'habilitada' : 'deshabilitada';
                    return $this->successResponse(RecargaResource::make($recarga), 'Recarga ' . $status . ' con éxito.', null, 200);
                } else {
                    return $this->errorResponse('Error de servidor', 500);
                }
            } else {
                return $this->errorResponse('Error de servidor', 500);
            }
        } catch (\Exception $error) {
            return $error->getMessage();
        }
    }

    private function validateDuplicateRecarga($request)
    {
        try {
            $existe              = false;
            $tz                  = 'America/Santiago';
            $fecha_beneficio     = Carbon::createFromDate($request->anio_beneficio, $request->mes_beneficio, '01', $tz);

            $recarga = Recarga::where('active', true)
                ->where('establecimiento_id', $request->establecimiento_id)
                ->where('anio_beneficio', $request->anio_beneficio)
                ->where('mes_beneficio', $request->mes_beneficio)
                ->first();

            if ($recarga) {
                $existe = true;
            }

            return $existe;
        } catch (\Exception $error) {
            return $error->getMessage();
        }
    }
}
