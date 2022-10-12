<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Recargas\StoreRecargaRequest;
use App\Http\Requests\Admin\Recargas\UpdateDatosPrincipalesRecargaRequest;
use App\Http\Resources\RecargaResource;
use App\Models\Recarga;
use Carbon\Carbon;
use Illuminate\Http\Request;

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
            'establecimiento',
            'userCreatedBy',
            'userUpdateBy'
        ];

        return $with;
    }

    public function returnRecargas()
    {
        try {
            $recargas = Recarga::orderBy('anio', 'asc')->orderBy('mes', 'asc')->get();

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
            $recarga = Recarga::where('codigo', $codigo)->with($with)->first();

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


    public function storeRecarga(StoreRecargaRequest $request)
    {
        try {
            $existe             = $this->validateDuplicateRecarga($request);
            $max_dias_habiles   = $this->validateDiasHabiles($request);

            if ($existe) {
                return response()->json([
                    'errors' => ['data' => ['No es posible ingresar la recarga, existe un registro idéntico.']]
                ], 422);
            } else if ($max_dias_habiles[0]) {
                return response()->json([
                    'errors' => ['total_dias_habiles' => ['Días habiles debe ser igual o menor a ' . $max_dias_habiles[1] . ' días']]
                ], 422);
            } else {
                $with = $this->withRecarga();
                $form = ['anio', 'mes', 'total_dias_mes', 'total_dias_habiles', 'monto_dia', 'establecimiento_id'];

                $recarga = Recarga::create($request->only($form));

                if ($recarga) {
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
                $max_dias_habiles   = $this->validateDiasHabiles($request);

                if ($max_dias_habiles[0]) {
                    return response()->json([
                        'errors' => ['total_dias_habiles' => ['Días habiles debe ser igual o menor a ' . $max_dias_habiles[1] . ' días']]
                    ], 422);
                } else {
                    $with = $this->withRecarga();
                    $form = ['total_dias_habiles', 'monto_dia'];
                    $update = $recarga->update($request->only($form));

                    if ($update) {
                        $with       = $this->withRecarga();
                        $recarga    = $recarga->fresh($with);
                        return $this->successResponse(RecargaResource::make($recarga), 'Recarga con código #' . $recarga->codigo . ' editada con éxito.', null, 200);
                    } else {
                        return $this->errorResponse('Error de servidor', 500);
                    }
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
            $existe = false;
            $recarga = Recarga::where('active', true)
                ->where('establecimiento_id', $request->establecimiento_id)
                ->where('anio', $request->anio)
                ->where('mes', $request->mes)
                ->first();

            if ($recarga) {
                $existe = true;
            }

            return $existe;
        } catch (\Exception $error) {
            return $error->getMessage();
        }
    }

    private function validateDiasHabiles($request)
    {
        try {
            $max            = false;
            $tz             = 'America/Santiago';
            $days_in_month  = Carbon::createFromDate($request->anio, $request->mes, '01', $tz)->daysInMonth;

            if ((int)$request->total_dias_habiles > $days_in_month) {
                $max = true;
            }

            return array($max, $days_in_month);
        } catch (\Exception $error) {
            return $error->getMessage();
        }
    }
}
