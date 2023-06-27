<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class Recarga extends Model
{
    public const STATUS_PENDIENTE               = 0;
    public const STATUS_PUBLICADA_EJECUTIVO     = 1;
    public const STATUS_PUBLICADA_SUPER_ADMIN   = 2;
    public const STATUS_CANCELADA               = 3;

    public const NOM_STATUS = [
        self::STATUS_PENDIENTE                  => 'PENDIENTE',
        self::STATUS_PUBLICADA_EJECUTIVO        => 'PUBLICADA POR EJECUTIVO',
        self::STATUS_PUBLICADA_SUPER_ADMIN      => 'PUBLICADA POR S. ADMIN',
        self::STATUS_CANCELADA                  => 'CANCELADA',
    ];

    protected $table = "recargas";
    protected $primaryKey = 'id';

    protected $fillable = [
        'codigo',
        'anio_beneficio',
        'mes_beneficio',
        'total_dias_mes_beneficio',
        'total_dias_laborales_beneficio',
        'total_dias_habiles',
        'anio_calculo',
        'mes_calculo',
        'total_dias_mes_calculo',
        'total_dias_laborales_calculo',
        'monto_dia',
        'monto_estimado',
        'active',
        'last_status',
        'establecimiento_id',
        'user_created_by',
        'date_created_user',
        'user_update_by',
        'date_updated_user'
    ];


    public function seguimiento()
    {
        return $this->hasMany(SeguimientoRecarga::class);
    }

    public function ausentismos()
    {
        return $this->hasMany(Ausentismo::class);
    }

    public function viaticos()
    {
        return $this->hasMany(Viatico::class);
    }

    public function asignaciones()
    {
        return $this->hasMany(UserTurno::class);
    }

    public function reglas()
    {
        return $this->hasMany(Regla::class);
    }

    public function reajustes()
    {
        return $this->hasMany(Reajuste::class)->orderBy('fecha_inicio', 'desc');
    }

    public function asistencias()
    {
        return $this->hasMany(Asistencia::class);
    }

    public function contratos()
    {
        return $this->hasMany(RecargaContrato::class);
    }

    public function establecimiento()
    {
        return $this->belongsTo(Establecimiento::class, 'establecimiento_id');
    }

    public function users()
    {
        return $this->belongsToMany(User::class)->withPivot('beneficio');
    }

    public function feriados()
    {
        return $this->belongsToMany(Feriado::class)->withPivot('active');
    }

    public function esquemas()
    {
        return $this->hasMany(Esquema::class);
    }

    public function estados()
    {
        return $this->hasMany(RecargaEstado::class);
    }

    public function userCreatedBy()
    {
        return $this->belongsTo(User::class, 'user_created_by');
    }

    public function userUpdateBy()
    {
        return $this->belongsTo(User::class, 'user_update_by');
    }

    public function totalDaysLaborales($fecha)
    {
        $days = 0;
        $inicio     = Carbon::parse($fecha);
        $termino    = Carbon::parse($inicio)->endOfMonth();

        $inicio     = $inicio->format('Y-m-d');
        $termino    = $termino->format('Y-m-d');

        for ($i = $inicio; $i <= $termino; $i++) {
            $i_format       = Carbon::parse($i)->isWeekend();
            if ($i_format) {
                $days++;
            }
        }

        return $days;
    }

    protected static function booted()
    {
        static::creating(function ($recarga) {
            $tz                        = 'America/Santiago';
            $date_beneficio            = Carbon::createFromDate($recarga->anio_beneficio, $recarga->mes_beneficio, '01', $tz);
            $date_calculo              = Carbon::createFromDate($recarga->anio_calculo, $recarga->mes_calculo, '01', $tz);

            $fds_beneficio             = self::totalDaysLaborales($date_beneficio);
            $fds_calculo               = self::totalDaysLaborales($date_calculo);

            $total_dias_mes_beneficio  = $date_beneficio->daysInMonth;
            $total_dias_mes_calculo    = $date_calculo->daysInMonth;

            $cod_sirh                                = $recarga->establecimiento ? $recarga->establecimiento->cod_sirh : 0;
            $recarga->codigo                         = "R{$cod_sirh}{$recarga->mes_beneficio}{$recarga->anio_beneficio}{$recarga->id}_{$recarga->id}";

            $total_dias_laborales_beneficio          = $total_dias_mes_beneficio - $fds_beneficio;
            $total_dias_laborales_calculo            = $total_dias_mes_calculo - $fds_calculo;

            $monto_estimado                          = $recarga->monto_dia * $total_dias_laborales_beneficio;

            $recarga->anio_beneficio                 = $recarga->anio_beneficio;
            $recarga->mes_beneficio                  = $recarga->mes_beneficio;
            $recarga->total_dias_mes_beneficio       = $total_dias_mes_beneficio;
            $recarga->total_dias_laborales_beneficio = $total_dias_laborales_beneficio;
            $recarga->total_dias_habiles_beneficio   = $total_dias_laborales_beneficio;
            $recarga->monto_estimado                 = $monto_estimado;

            $recarga->anio_calculo                   = $recarga->anio_calculo;
            $recarga->mes_calculo                    = $recarga->mes_calculo;
            $recarga->total_dias_mes_calculo         = $total_dias_mes_calculo;
            $recarga->total_dias_laborales_calculo   = $total_dias_laborales_calculo;
            $recarga->total_dias_habiles_calculo     = $total_dias_laborales_calculo;
            $recarga->last_status                    = 0;

            $recarga->user_created_by                = Auth::user()->id;
            $recarga->date_created_user              = Carbon::now()->toDateTimeString();
        });

        static::updating(function ($recarga) {
            $count_feriados_calculo                                         = $recarga->feriados()->where('active', true)->where('anio', $recarga->anio_calculo)->where('mes', $recarga->mes_calculo)->count();
            $count_feriados_beneficio                                       = $recarga->feriados()->where('active', true)->where('anio', $recarga->anio_beneficio)->where('mes', $recarga->mes_beneficio)->count();

            $total_dias_habiles_calculo                                     = $recarga->total_dias_laborales_calculo   - $count_feriados_calculo;
            $total_dias_habiles_beneficio                                   = $recarga->total_dias_laborales_beneficio - $count_feriados_beneficio;

            $monto_estimado                                                 = $recarga->monto_dia * $total_dias_habiles_beneficio;

            $recarga->total_dias_habiles_calculo                            = $total_dias_habiles_calculo;
            $recarga->total_dias_habiles_beneficio                          = $total_dias_habiles_beneficio;
            $recarga->monto_estimado                                        = $monto_estimado;
            $recarga->user_update_by                                        = Auth::user()->id;
            $recarga->date_updated_user                                     = Carbon::now()->toDateTimeString();
        });

        static::updated(function ($recarga) {
            $recarga->esquemas->each(function ($esquema) use ($recarga) {
                $monto_total_dias               = $esquema->total_dias_cancelar * $recarga->monto_dia;
                $monto_total_cancelar           = $monto_total_dias + $esquema->total_monto_ajuste;

                $esquema->monto_total_cancelar = $monto_total_cancelar;
                $esquema->save();
            });
        });
    }
}
