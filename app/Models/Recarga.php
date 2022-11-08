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
        'anio',
        'mes',
        'total_dias_mes',
        'total_dias_habiles',
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

    public function reglas()
    {
        return $this->hasMany(Regla::class);
    }

    public function establecimiento()
    {
        return $this->belongsTo(Establecimiento::class, 'establecimiento_id');
    }

    public function users()
    {
        return $this->belongsToMany(User::class);
    }

    public function userCreatedBy()
    {
        return $this->belongsTo(User::class, 'user_created_by');
    }

    public function userUpdateBy()
    {
        return $this->belongsTo(User::class, 'user_update_by');
    }

    protected static function booted()
    {
        static::creating(function ($recarga) {
            $tz                         = 'America/Santiago';
            $days_in_month              = Carbon::createFromDate($recarga->anio, $recarga->mes, '01', $tz)->daysInMonth;

            $recarga->codigo            = sprintf('%d%d', $recarga->id, mt_Rand(100, 999));
            $recarga->total_dias_mes    = (int)$days_in_month;
            $recarga->user_created_by   = Auth::user()->id;
            $recarga->date_created_user = Carbon::now()->toDateTimeString();
        });

        static::updating(function ($recarga) {
            $recarga->user_update_by    = Auth::user()->id;
            $recarga->date_updated_user = Carbon::now()->toDateTimeString();
        });
    }
}
