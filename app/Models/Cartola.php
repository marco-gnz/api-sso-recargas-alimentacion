<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class Cartola extends Model
{
    protected $table = "cartolas";
    protected $primaryKey = 'id';

    public $timestamps = false;

    protected $fillable = [
        'uuid',
        'active',
        'es_turnante',
        'total_dias_contrato',
        'total_dias_habiles_contrato',
        'fecha_alejamiento',
        'total_dias_turno_largo',
        'total_dias_turno_nocturno',
        'total_dias_libres',
        'total_dias_grupo_uno',
        'total_dias_habiles_grupo_uno',
        'total_dias_grupo_dos',
        'total_dias_habiles_grupo_dos',
        'total_dias_grupo_tres',
        'total_dias_habiles_grupo_tres',
        'total_dias_viaticos',
        'total_dias_habiles_viaticos',
        'total_dias_ajustes',
        'total_monto_ajuste',
        'user_id',
        'recarga_id',
        'user_created_by',
        'date_created_user',
        'user_update_by',
        'date_updated_user'
    ];

    public function recarga()
    {
        return $this->belongsTo(Recarga::class, 'recarga_id');
    }

    public function funcionario()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    protected static function booted()
    {
        static::creating(function ($cartola) {
            $cartola->uuid                   = Str::uuid();
            /* $cartola->user_created_by        = Auth::user()->id; */
            $cartola->date_created_user      = Carbon::now()->toDateTimeString();
        });

        static::updating(function ($cartola) {
            $cartola->user_update_by    = Auth::user()->id;
            $cartola->date_updated_user = Carbon::now()->toDateTimeString();
        });
    }
}
