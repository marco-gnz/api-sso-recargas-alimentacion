<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class Viatico extends Model
{
    protected $table        = "viaticos";
    protected $primaryKey   = 'id';

    protected $fillable = [
        'uuid',
        'fecha_inicio',
        'fecha_termino',
        'total_dias',
        'fecha_inicio_periodo',
        'fecha_termino_periodo',
        'total_dias_periodo',
        'total_dias_habiles_periodo',
        'jornada',
        'tipo_resolucion',
        'n_resolucion',
        'fecha_resolucion',
        'tipo_comision',
        'motivo_viatico',
        'valor_viatico',
        'user_id',
        'recarga_id',
        'user_created_by',
        'date_created_user',
        'user_update_by',
        'date_updated_user'
    ];

    public function funcionario()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function recarga()
    {
        return $this->belongsTo(Recarga::class, 'recarga_id');
    }

    public function userCreatedBy()
    {
        return $this->belongsTo(User::class, 'user_created_by');
    }

    protected static function booted()
    {
        static::creating(function ($viatico) {
            $days = 0;
            $fecha_inicio   = Carbon::parse($viatico->fecha_inicio);
            $fecha_termino  = Carbon::parse($viatico->fecha_termino);
            $days_diff      = $fecha_inicio->diffInDays($fecha_termino) + 1;

            $fecha_inicio_periodo   = Carbon::parse($viatico->fecha_inicio_periodo);
            $fecha_termino_periodo  = Carbon::parse($viatico->fecha_termino_periodo);
            $days_diff_periodo      = $fecha_inicio_periodo->diffInDays($fecha_termino_periodo) + 1;

            $fecha_inicio   = $fecha_inicio->format('Y-m-d');
            $fecha_termino  = $fecha_termino->format('Y-m-d');

            $fecha_inicio_periodo   = $fecha_inicio_periodo->format('Y-m-d');
            $fecha_termino_periodo  = $fecha_termino_periodo->format('Y-m-d');

            for ($i = $fecha_inicio_periodo; $i <= $fecha_termino_periodo; $i++) {
                $i_format       = Carbon::parse($i)->isWeekend();
                if($i_format){
                    $days++;
                }
            }

            $viatico->uuid                  = Str::uuid();
            $viatico->total_dias            = $days_diff;
            $viatico->total_dias_periodo    = $days_diff_periodo;
            $viatico->total_dias_habiles_periodo    = ($days_diff_periodo - $days);
            $viatico->user_created_by       = Auth::user()->id;
            $viatico->date_created_user     = Carbon::now()->toDateTimeString();
        });
    }

    public function scopeInput($query, $input)
    {
        if ($input)
            return $query->where(function ($query) use ($input) {
                $query->whereHas('funcionario', function ($query) use ($input) {
                    $query->where('rut_completo', 'like', '%' . $input . '%')
                        ->orWhere('rut', 'like', '%' . $input . '%')
                        ->orWhere('nombres', 'like', '%' . $input . '%')
                        ->orWhere('apellidos', 'like', '%' . $input . '%')
                        ->orWhere('nombre_completo', 'like', '%' . $input . '%');
                });
            });
    }
}
