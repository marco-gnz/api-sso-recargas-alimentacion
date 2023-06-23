<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use Spatie\Permission\Traits\HasRoles;
use Spatie\Permission\Models\Permission;
use Illuminate\Support\Str;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, HasRoles;

    public const ERROR_1            = 1;
    public const ERROR_2            = 2;

    public const ADVERTENCIA_1      = 1;
    public const ADVERTENCIA_2      = 2;
    public const ADVERTENCIA_3      = 3;
    public const ADVERTENCIA_4      = 4;
    public const ADVERTENCIA_5      = 5;


    public const ERROR_NOM = [
        self::ERROR_1               => 'SIN BENEFICIO CON AUSENTISMOS/VIÁTICOS/CONTRATOS CARGADOS',
        self::ERROR_2               => 'FECHAS DE AUSENTISMOS ENTRELAZADAS',
    ];

    public const ADVERTENCIA_NOM = [
        self::ADVERTENCIA_1         => 'CONTRATO CON FECHA DE ALEJAMIENTO',
        self::ADVERTENCIA_2         => 'DÍAS A CANCELAR EN 0',
        self::ADVERTENCIA_3         => 'MONTO TOTAL A CANCELAR MAYOR AL ESTIMADO',
        self::ADVERTENCIA_4         => 'PAGOS MAYOR AL ESTIMADO SIN AJUSTES',
        self::ADVERTENCIA_5         => 'TIENE MAS DÍAS A CANCELAR QUE CONTRATO'
    ];

    protected $table = "users";
    protected $primaryKey = 'id';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'uuid',
        'rut',
        'dv',
        'rut_completo',
        'nombres',
        'apellidos',
        'nombre_completo',
        'estado',
        'email',
        'password',
        'usuario_add_id',
        'fecha_add'
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];


    /* protected $guarded = ['id']; */

    public function ausentismos()
    {
        return $this->hasMany(Ausentismo::class);
    }

    public function asistencias()
    {
        return $this->hasMany(Asistencia::class);
    }

    public function reajustes()
    {
        return $this->hasMany(Reajuste::class)->orderBy('fecha_inicio', 'desc');
    }

    public function turnos()
    {
        return $this->hasMany(UserTurno::class);
    }

    public function viaticos()
    {
        return $this->hasMany(Viatico::class);
    }

    public function contratos()
    {
        return $this->hasMany(RecargaContrato::class);
    }

    public function recargas()
    {
        return $this->belongsToMany(Recarga::class)->withPivot('beneficio');
    }

    public function establecimientos()
    {
        return $this->belongsToMany(Establecimiento::class);
    }

    public function esquemas()
    {
        return $this->hasMany(Esquema::class);
    }

    public function loginHistorys()
    {
        return $this->hasMany(LoginHistory::class);
    }

    protected static function booted()
    {
        static::creating(function ($usuario) {
            $usuario->uuid                  = Str::uuid();
            $usuario->rut_completo          = $usuario->rut . '-' . $usuario->dv;
            $usuario->nombre_completo       = $usuario->nombres . ' ' . $usuario->apellidos;
            $usuario->password              = bcrypt($usuario->rut);
            $usuario->usuario_add_id        = optional(auth()->user())->id;
            $usuario->fecha_add             = Carbon::now()->toDateTimeString();
        });

        static::updating(function ($usuario) {
            $usuario->usuario_update_id         = Auth::user()->id;
            $usuario->fecha_update              = Carbon::now()->toDateTimeString();
        });
    }

    public function scopeInput($query, $input)
    {
        if ($input)
            return $query->where('rut_completo', 'like', '%' . $input . '%')
                ->orWhere('rut', 'like', '%' . $input . '%')
                ->orWhere('nombres', 'like', '%' . $input . '%')
                ->orWhere('apellidos', 'like', '%' . $input . '%')
                ->orWhere('nombre_completo', 'like', '%' . $input . '%')
                ->orWhere('email', 'like', '%' . $input . '%');
    }

    public function scopeTurno($query, $turno, $id_recarga)
    {
        if ($turno) {
            return $query->where(function ($q) use ($id_recarga, $turno) {
                if (in_array('si', $turno)) {
                    $q->where(function ($query) use ($id_recarga) {
                        $query->whereHas('turnos', function ($query) use ($id_recarga) {
                            $query->where('recarga_id', $id_recarga)->where(function ($q) {
                                $q->where('asignacion_tercer_turno', '>', 0)
                                    ->orWhere('asignacion_cuarto_turno', '>', 0);
                            });
                        })->whereHas('asistencias', function ($query) use ($id_recarga) {
                            $query->where('recarga_id', $id_recarga);
                        })->whereHas('contratos', function ($query) use ($id_recarga) {
                            $query->where('recarga_id', $id_recarga);
                        });
                    })->orWhere(function ($query) use ($id_recarga) {
                        $query->whereHas('asistencias', function ($query) use ($id_recarga) {
                            $query->where('recarga_id', $id_recarga);
                        })->whereHas('contratos', function ($query) use ($id_recarga) {
                            $query->where('recarga_id', $id_recarga);
                        });
                    });
                } else if (in_array('no', $turno)) {
                    $q->whereHas('contratos', function ($query) use ($id_recarga) {
                        $query->where('recarga_id', $id_recarga);
                    })->whereDoesntHave('asistencias', function ($query) use ($id_recarga) {
                        $query->where('recarga_id', $id_recarga);
                    })->whereDoesntHave('turnos', function ($query) use ($id_recarga) {
                        $query->where('recarga_id', $id_recarga)->where(function ($q) {
                            $q->where('asignacion_tercer_turno', '>', 0)
                                ->orWhere('asignacion_cuarto_turno', '>', 0);
                        });
                    });
                } else if (in_array('error', $turno)) {
                    $q->where(function ($query) use ($id_recarga) {
                        $query->whereDoesntHave('turnos', function ($query) use ($id_recarga) {
                            $query->where('recarga_id', $id_recarga)
                                ->where(function ($q) {
                                    $q->where('asignacion_tercer_turno', '>', 0)
                                        ->orWhere('asignacion_cuarto_turno', '>', 0);
                                });
                        })->whereHas('asistencias', function ($query) use ($id_recarga) {
                            $query->where('recarga_id', $id_recarga);
                        })->whereHas('contratos', function ($query) use ($id_recarga) {
                            $query->where('recarga_id', $id_recarga);
                        });
                    })->orWhere(function ($query) use ($id_recarga) {
                        $query->whereDoesntHave('asistencias', function ($query) use ($id_recarga) {
                            $query->where('recarga_id', $id_recarga);
                        })->whereHas('turnos', function ($query) use ($id_recarga) {
                            $query->where('recarga_id', $id_recarga)->where(function ($q) {
                                $q->where('asignacion_tercer_turno', '>', 0)
                                    ->orWhere('asignacion_cuarto_turno', '>', 0);
                            });
                        })->whereHas('contratos', function ($query) use ($id_recarga) {
                            $query->where('recarga_id', $id_recarga);
                        });
                    })->orWhere(function ($query) use ($id_recarga) {
                        $query->whereDoesntHave('contratos', function ($query) use ($id_recarga) {
                            $query->where('recarga_id', $id_recarga);
                        })->whereHas('turnos', function ($query) use ($id_recarga) {
                            $query->where('recarga_id', $id_recarga)->where(function ($q) {
                                $q->where('asignacion_tercer_turno', '>', 0)
                                    ->orWhere('asignacion_cuarto_turno', '>', 0);
                            });
                        })->whereHas('asistencias', function ($query) use ($id_recarga) {
                            $query->where('recarga_id', $id_recarga);
                        });
                    });
                }
            });
        }
    }

    public function scopeAjustesEnRecarga($query, $value, $id_recarga)
    {
        if ($value)
            return $query->whereHas('reajustes', function ($query) use ($value, $id_recarga) {
                $query->where('recarga_id', $id_recarga)->whereIn('tipo_reajuste', $value);
            });
    }

    public function scopeErrores($query, $value, $id_recarga)
    {
        if ($value) {
            return $query->where(function ($q) use ($id_recarga, $value) {
                if (in_array('advertencias', $value)) {
                        $q->whereHas('contratos', function ($query) use ($id_recarga) {
                            $query->where('recarga_id', $id_recarga)->where('alejamiento', true);
                        });
                }
            });
        }
    }
}
