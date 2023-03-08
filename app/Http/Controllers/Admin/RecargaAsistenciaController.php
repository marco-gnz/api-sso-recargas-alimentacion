<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Asistencias\UpdateAsistenciaResumenRequest;
use App\Http\Resources\AsistenciaFindResource;
use App\Http\Resources\AsistenciaRecargaResource;
use App\Models\Asistencia;
use App\Models\Recarga;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;

class RecargaAsistenciaController extends Controller
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

    private function withAsistencia()
    {
        $with = [
            'proceso',
            'calidad',
            'establecimiento',
            'unidad',
            'planta',
            'userBy',
            'userByUpdate'
        ];
        return $with;
    }

    public function findAsistencia($uuid)
    {
        $asistencia = Asistencia::where('uuid', $uuid)->firstOrFail();

        if ($asistencia) {
            return $this->successResponse(AsistenciaFindResource::make($asistencia));
        }
    }

    private function withFnContratos($recarga)
    {
        $function = ['contratos' => function ($query) use ($recarga) {
            $query->where('recarga_id', $recarga->id)->get();
        }];
        return $function;
    }

    public function withFnAsistencias($recarga)
    {
        $function = ['asistencias' => function ($query) use ($recarga) {
            $query->where('recarga_id', $recarga->id)->get();
        }];
        return $function;
    }

    private function getColumnsAsistenciaRecarga($recarga)
    {
        $columnas   = [];
        if ($recarga) {
            $tz         = 'America/Santiago';
            $inicio     = Carbon::createFromDate($recarga->anio_beneficio, $recarga->mes_beneficio, '01', $tz);
            $termino    = Carbon::createFromDate($recarga->anio_beneficio, $recarga->mes_beneficio, '01', $tz)->endOfMonth();

            $inicio     = $inicio->format('Y-m-d');
            $termino    = $termino->format('Y-m-d');
            for ($i = $inicio; $i <= $termino; $i++) {
                $i_format       = Carbon::parse($i)->format('d-m-Y');
                $data =
                    [
                        'nombre_columna'        => Carbon::parse($i_format)->format('d'),
                        'descripcion'           => "{$i_format}",
                        'is_week_day'           => Carbon::parse($i_format)->isWeekend()
                    ];

                array_push($columnas, $data);
            }
        }
        return $columnas;
    }

    public function asistenciasRecarga($codigo, Request $request)
    {
        try {
            $recarga = Recarga::where('codigo', $codigo)->first();

            if ($recarga) {
                $withFnContratos        = $this->withFnContratos($recarga);
                $withFnAsistencias      = $this->withFnAsistencias($recarga);
                $users_id               = $recarga->asistencias()->pluck('user_id')->toArray();

                $users_id               = array_unique($users_id);
                $columnas               = $this->getColumnsAsistenciaRecarga($recarga);

                $users = User::with($withFnContratos)->with($withFnAsistencias)->input($request->input)->whereIn('id', $users_id)->orderBy('apellidos', 'asc')->paginate(20);

                return response()->json(
                    array(
                        'status'    => 'Success',
                        'title'     => null,
                        'message'   => null,
                        'pagination' => [
                            'total'         => $users->total(),
                            'current_page'  => $users->currentPage(),
                            'per_page'      => $users->perPage(),
                            'last_page'     => $users->lastPage(),
                            'from'          => $users->firstItem(),
                            'to'            => $users->lastPage()
                        ],
                        'users'     => AsistenciaRecargaResource::collection($users),
                        'columnas'  => $columnas
                    )
                );
            }
        } catch (\Exception $error) {
            return response()->json($error->getMessage());
        }
    }

    public function updateAsistencia($id, UpdateAsistenciaResumenRequest $request)
    {
        try {
            $asistencia = Asistencia::find($id);

            $form = ['tipo_asistencia_turno_id'];

            $update = $asistencia->update($request->only($form));

            $user = $asistencia->funcionario;

            if ($update) {
                $asistencia->createobservaciones([
                    'fecha'                     => $asistencia->fecha,
                    'observacion'               => $request->observacion,
                    'asistencia_id'             => $asistencia->id,
                    'tipo_asistencia_turno_id'  => $asistencia->tipo_asistencia_turno_id
                ]);
                return $this->successResponse(AsistenciaRecargaResource::make($user), "Asistencia modificada con éxito.", "Modificado a {$asistencia->tipoAsistenciaTurno->descripcion}");
            }
        } catch (\Exception $error) {
            return response()->json($error->getMessage());
        }
    }
}
