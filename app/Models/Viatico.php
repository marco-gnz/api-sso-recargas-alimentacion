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
        'total_dias_periodo_turno',
        'total_dias_habiles_periodo_turno',
        'descuento_turno_libre',
        'jornada',
        'tipo_resolucion',
        'n_resolucion',
        'fecha_resolucion',
        'tipo_comision',
        'motivo_viatico',
        'valor_viatico',
        'user_id',
        'recarga_id',
        'esquema_id',
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

    public function esquema()
    {
        return $this->belongsTo(Esquema::class, 'esquema_id');
    }

    public function userCreatedBy()
    {
        return $this->belongsTo(User::class, 'user_created_by');
    }

    protected static function booted()
    {
        static::creating(function ($viatico) {
            $viatico->uuid                  = Str::uuid();
            $viatico->user_created_by       = Auth::user()->id;
            $viatico->date_created_user     = Carbon::now()->toDateTimeString();
        });

        static::deleted(function ($viatico) {
            if ($viatico->esquema) {
                $viatico->esquema->total_dias_viaticos = 0;
                $viatico->esquema->total_dias_habiles_viaticos = 0;
                $viatico->esquema->total_dias_feriados_viaticos = 0;
                $viatico->esquema->viaticos_n_registros = 0;
                $viatico->esquema->calculo_viaticos = 0;
                $viatico->esquema->save();
            }
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

    public function scopeDescuento($query, $descuento)
    {
        if ($descuento) {
            if (in_array(1, $descuento) && in_array(0, $descuento)) {
                return $query;
            } elseif (in_array(1, $descuento)) {
                return $query->where('valor_viatico', '>', 0);
            } elseif (in_array(0, $descuento)) {
                return $query->where('valor_viatico', '<=', 0);
            }
        }
    }

    public function scopeDescuentoTurnoLibre($query, $array)
    {
        if ($array) {
            return $query->where('descuento_turno_libre', $array);
        }
    }
}
