<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class RecargaContrato extends Model
{
    protected $table = "recarga_contratos";
    protected $primaryKey = 'id';

    protected $fillable = [
        'uuid',
        'fecha_inicio',
        'fecha_termino',
        'alejamiento',
        'total_dias_contrato',
        'fecha_inicio_periodo',
        'fecha_termino_periodo',
        'total_dias_contrato_periodo',
        'total_dias_habiles_contrato_periodo',
        'user_id',
        'establecimiento_id',
        'unidad_id',
        'planta_id',
        'cargo_id',
        'ley_id',
        'hora_id',
        'recarga_id',
        'usuario_add_id',
        'fecha_add',
        'usuario_update_id',
        'fecha_update'
    ];

    public function funcionario()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function recarga()
    {
        return $this->belongsTo(Recarga::class, 'recarga_id');
    }

    public function establecimiento()
    {
        return $this->belongsTo(Establecimiento::class, 'user_id');
    }

    public function unidad()
    {
        return $this->belongsTo(Unidad::class, 'unidad_id');
    }

    public function planta()
    {
        return $this->belongsTo(Planta::class, 'planta_id');
    }

    public function cargo()
    {
        return $this->belongsTo(Cargo::class, 'cargo_id');
    }

    public function ley()
    {
        return $this->belongsTo(Ley::class, 'ley_id');
    }

    public function hora()
    {
        return $this->belongsTo(Hora::class, 'hora_id');
    }

    protected static function booted()
    {
        static::creating(function ($contrato) {
            $days = 0;
            $inicio     = Carbon::parse($contrato->fecha_inicio_periodo)->format('Y-m-d');
            $termino    = Carbon::parse($contrato->fecha_termino_periodo)->format('Y-m-d');
            for ($i = $inicio; $i <= $termino; $i++) {
                $i_format       = Carbon::parse($i)->isWeekend();
                if($i_format){
                    $days++;
                }
            }

            $contrato->uuid              = Str::uuid();
            $contrato->usuario_add_id    = Auth::user()->id;
            $contrato->fecha_add         = Carbon::now()->toDateTimeString();
            $contrato->total_dias_habiles_contrato_periodo = ($contrato->total_dias_contrato_periodo - $days);
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
