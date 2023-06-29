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
        'esquema_id',
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

    public function esquema()
    {
        return $this->belongsTo(Esquema::class, 'esquema_id');
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
                if ($i_format) {
                    $days++;
                }
            }

            $contrato->uuid              = Str::uuid();
            $contrato->user_created_by    = Auth::user()->id;
            $contrato->date_created_user  = Carbon::now()->toDateTimeString();
            $contrato->total_dias_habiles_contrato_periodo = ($contrato->total_dias_contrato_periodo - $days);
        });

        static::created(function ($contrato) {
            $esquema = $contrato->crearEsquema();

            $feriados_count       = $contrato->recarga->feriados()->where('active', true)->whereBetween('fecha', [$contrato->fecha_inicio_periodo, $contrato->fecha_termino_periodo])->count();
            $total                = $contrato->total_dias_habiles_contrato_periodo - $feriados_count;

            $contrato->total_dias_habiles_contrato_periodo  = $total;
            $contrato->esquema_id                           = $esquema ? $esquema->id : null;
            $contrato->save();
        });

        static::deleted(function ($contrato) {
            if ($contrato->esquema) {
                $contrato->esquema->total_dias_contrato = 0;
                $contrato->esquema->total_dias_habiles_contrato = 0;
                $contrato->esquema->total_dias_feriados_contrato = 0;
                $contrato->esquema->contrato_n_registros = 0;
                $contrato->esquema->calculo_contrato = 0;
                $contrato->esquema->save();
            }
        });
    }

    public function crearEsquema()
    {
        $esquema = Esquema::where('recarga_id', $this->recarga_id)
            ->where('user_id', $this->user_id)
            ->first();

        if (!$esquema) {
            $feriados_count     = $this->contarFeriadosEnContrato($this->recarga, $this);
            $calculo_contrato   = $this->total_dias_habiles_contrato_periodo - $feriados_count;
            $esquema = $this->esquema()->create([
                'total_dias_feriados_contrato'  => $feriados_count,
                'calculo_contrato'              => $calculo_contrato,
                'fecha_alejamiento'             => $this->alejamiento ? true : false,
                'total_dias_contrato'           => $this->total_dias_contrato_periodo,
                'total_dias_habiles_contrato'   => $this->total_dias_habiles_contrato_periodo,
                'contrato_n_registros'          => 1,
                'user_id'                       => $this->funcionario->id,
                'recarga_id'                    => $this->recarga->id,
                'total_dias_cancelar'           => $calculo_contrato,
                'monto_total_cancelar'          => $calculo_contrato * $this->recarga->monto_dia
            ]);

            return $esquema;
        }
        return $esquema;
    }

    private function contarFeriadosEnContrato($recarga, $contrato)
    {
        $feriados_count = $recarga->feriados()->where('active', true)->whereBetween('fecha', [$contrato->fecha_inicio_periodo, $contrato->fecha_termino_periodo])->count();

        return $feriados_count;
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
