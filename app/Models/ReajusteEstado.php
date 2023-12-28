<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class ReajusteEstado extends Model
{
    public const STATUS_PENDIENTE = 0;
    public const STATUS_APROBADO  = 1;
    public const STATUS_RECHAZADO = 2;

    public const STATUS_IDS = [
        self::STATUS_PENDIENTE => ['id' => '0', 'nombre' => 'PENDIENTE'],
        self::STATUS_APROBADO  => ['id' => '1', 'nombre' => 'APROBADO'],
        self::STATUS_RECHAZADO => ['id' => '2', 'nombre' => 'RECHAZADO'],
    ];

    public const STATUS_NOM = [
        self::STATUS_PENDIENTE => 'PENDIENTE',
        self::STATUS_APROBADO  => 'APROBADO',
        self::STATUS_RECHAZADO => 'RECHAZADO',
    ];

    public const STATUS_DESC = [
        self::STATUS_PENDIENTE => 'Pendiente por validar',
        self::STATUS_APROBADO  => 'Aprobado por administrador',
        self::STATUS_RECHAZADO => 'Rechazado por administrador',
    ];

    protected $table = "reajuste_estados";
    protected $primaryKey = 'id';

    protected $fillable = [
        'status',
        'observacion',
        'user_id',
        'reajuste_id'
    ];

    public function reajuste()
    {
        return $this->belongsTo(Reajuste::class, 'reajuste_id');
    }

    public function userCreatedBy()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    protected static function booted()
    {
        static::creating(function ($reajuste_estado) {
            $reajuste_estado->user_id      = $reajuste_estado->user_id === null ? (Auth::user() ? Auth::user()->id : null) : $reajuste_estado->user_id;
            if($reajuste_estado->reajuste){
                $reajuste_estado->reajuste->update([
                    'last_status'  => $reajuste_estado->status
                ]);
            }

        });

        static::updating(function ($reajuste_estado) {
            $reajuste_estado->reajuste->last_status          = $reajuste_estado->status;
        });
    }
}
