<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class Recarga extends Model
{
    protected $table = "recargas";
    protected $primaryKey = 'id';

    protected $fillable = [
        'codigo',
        'anio_beneficio',
        'mes_beneficio',
        'total_dias_mes_beneficio',
        'total_dias_laborales_beneficio',
        'anio_calculo',
        'mes_calculo',
        'total_dias_mes_calculo',
        'total_dias_laborales_calculo',
        'monto_dia',
        'active',
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
            $recarga->codigo                         = "R{$cod_sirh}{$recarga->mes_beneficio}{$recarga->anio_beneficio}{$recarga->id}";

            $recarga->anio_beneficio                 = $recarga->anio_beneficio;
            $recarga->mes_beneficio                  = $recarga->mes_beneficio;
            $recarga->total_dias_mes_beneficio       = $total_dias_mes_beneficio;
            $recarga->total_dias_laborales_beneficio = ($total_dias_mes_beneficio - $fds_beneficio);

            $recarga->anio_calculo                   = $recarga->anio_calculo;
            $recarga->mes_calculo                    = $recarga->mes_calculo;
            $recarga->total_dias_mes_calculo         = $total_dias_mes_calculo;
            $recarga->total_dias_laborales_calculo   = ($total_dias_mes_calculo - $fds_calculo);

            $recarga->user_created_by                = Auth::user()->id;
            $recarga->date_created_user              = Carbon::now()->toDateTimeString();
        });

        static::updating(function ($recarga) {
            $recarga->user_update_by                = Auth::user()->id;
            $recarga->date_updated_user             = Carbon::now()->toDateTimeString();
        });
    }
}
