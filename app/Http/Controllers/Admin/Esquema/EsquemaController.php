<?php

namespace App\Http\Controllers\Admin\Esquema;

use App\Http\Controllers\Controller;
use App\Http\Resources\AsistenciaUniqueRecargaResource;
use App\Http\Resources\Esquema\EsquemaResource;
use App\Http\Resources\FuncionarioAusentismosResource;
use App\Http\Resources\FuncionarioContratosResource;
use App\Http\Resources\FuncionarioReajustesResource;
use App\Http\Resources\FuncionarioTurnosResource;
use App\Http\Resources\FuncionarioViaticosResource;
use App\Models\Esquema;
use App\Models\GrupoAusentismo;
use App\Models\TipoAsistenciaTurno;
use Carbon\Carbon;
use Illuminate\Http\Request;

class EsquemaController extends Controller
{
    public function returnEsquemaOrCreate($user_id, $recarga_id)
    {
        try {
            $esquema = Esquema::where('user_id', $user_id)->where('recarga_id', $recarga_id)
                ->whereHas('recarga', function ($query) {
                    $query->where('active', true);
                })
                ->first();

            return $esquema;
        } catch (\Exception $error) {
            //throw $th;
        }
    }

    public function returnEsquema($user_id, $recarga_id)
    {
        try {
            $esquema = Esquema::where('user_id', $user_id)->where('recarga_id', $recarga_id)
                ->whereHas('recarga', function ($query) {
                    $query->where('active', true);
                })
                ->first();

            if ($esquema) {
                return $esquema;
            } else {
                return null;
            }
        } catch (\Exception $error) {
            //throw $th;
        }
    }

    public function esquemaDetalle($uuid)
    {
        try {
            $esquema = $this->findEsquema($uuid);
            return response()->json(
                array(
                    'status'    => 'Success',
                    'title'     => null,
                    'message'   => null,
                    'esquema'   => EsquemaResource::make($esquema)
                )
            );
        } catch (\Exception $error) {
            return response()->json($error->getMessage());
        }
    }

    public function esquemaAsignaciones($uuid)
    {
        try {
            $esquema        = $this->findEsquema($uuid);
            $asignaciones   = $esquema->turnos()->get();

            return response()->json(
                array(
                    'status'        => 'Success',
                    'title'         => null,
                    'message'       => null,
                    'esquema'       => EsquemaResource::make($esquema),
                    'asignaciones'  => FuncionarioTurnosResource::collection($asignaciones)
                )
            );
        } catch (\Exception $error) {
            return response()->json($error->getMessage());
        }
    }

    public function esquemaContratos($uuid)
    {
        try {
            $esquema        = $this->findEsquema($uuid);
            $contratos      = $esquema->contratos()->get();

            return response()->json(
                array(
                    'status'        => 'Success',
                    'title'         => null,
                    'message'       => null,
                    'esquema'       => EsquemaResource::make($esquema),
                    'contratos'     => FuncionarioContratosResource::collection($contratos)
                )
            );
        } catch (\Exception $error) {
            return response()->json($error->getMessage());
        }
    }

    public function esquemaTurnos($uuid)
    {
        try {
            $esquema        = $this->findEsquema($uuid);
            $turnos         = $esquema->asistencias()->get();
            $columnas       = [];
            $tz             = 'America/Santiago';
            $inicio         = Carbon::createFromDate($esquema->recarga->anio_beneficio, $esquema->recarga->mes_beneficio, '01', $tz);
            $termino        = Carbon::createFromDate($esquema->recarga->anio_beneficio, $esquema->recarga->mes_beneficio, '01', $tz)->endOfMonth();
            $tipos_turnos   = TipoAsistenciaTurno::orderBy('nombre', 'ASC')->get();

            $inicio         = $inicio->format('Y-m-d');
            $termino        = $termino->format('Y-m-d');
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

            return response()->json(
                array(
                    'status'        => 'Success',
                    'title'         => null,
                    'message'       => null,
                    'esquema'       => EsquemaResource::make($esquema),
                    'calendario'    => $columnas,
                    'turnos'        => AsistenciaUniqueRecargaResource::collection($turnos),
                    'tipos_turnos'  => $tipos_turnos
                )
            );
        } catch (\Exception $error) {
            return response()->json($error->getMessage());
        }
    }

    public function esquemaAusentismos($uuid, $n_grupo)
    {
        try {
            $esquema        = $this->findEsquema($uuid);
            $grupos         = GrupoAusentismo::orderBy('n_grupo', 'ASC')
                ->withCount(['ausentismos' => function ($query) use ($esquema) {
                    $query->where('esquema_id', $esquema->id);
                }])
                ->get();
            $grupo          = GrupoAusentismo::where('n_grupo', $n_grupo)->first();
            $ausentismos    = $esquema->ausentismos()->where('grupo_id', $grupo->id)->get();

            return response()->json(
                array(
                    'status'        => 'Success',
                    'title'         => null,
                    'message'       => null,
                    'esquema'       => EsquemaResource::make($esquema),
                    'grupos'        => $grupos,
                    'ausentismos'   => FuncionarioAusentismosResource::collection($ausentismos)
                )
            );
        } catch (\Exception $error) {
            return response()->json($error->getMessage());
        }
    }

    public function esquemaViaticos($uuid)
    {
        try {
            $esquema        = $this->findEsquema($uuid);
            $viaticos      = $esquema->viaticos()->get();

            return response()->json(
                array(
                    'status'        => 'Success',
                    'title'         => null,
                    'message'       => null,
                    'esquema'       => EsquemaResource::make($esquema),
                    'viaticos'      => FuncionarioViaticosResource::collection($viaticos)
                )
            );
        } catch (\Exception $error) {
            return response()->json($error->getMessage());
        }
    }

    public function esquemaAjustes($uuid)
    {
        try {
            $esquema        = $this->findEsquema($uuid);
            $ajustes        = $esquema->reajustes()->get();

            return response()->json(
                array(
                    'status'        => 'Success',
                    'title'         => null,
                    'message'       => null,
                    'esquema'       => EsquemaResource::make($esquema),
                    'ajustes'       => FuncionarioReajustesResource::collection($ajustes)
                )
            );
        } catch (\Exception $error) {
            return response()->json($error->getMessage());
        }
    }

    private function findEsquema($uuid)
    {
        $esquema = Esquema::where('uuid', $uuid)
            ->withCount('contratos')
            ->withCount('turnos')
            ->withCount('asistencias')
            ->withCount('ausentismos')
            ->withCount('viaticos')
            ->withCount('reajustes')
            ->firstOrFail();

        return $esquema;
    }
}
