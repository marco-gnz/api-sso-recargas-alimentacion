<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class Reajuste extends Model
{
    public const TYPE_DIAS   = 0;
    public const TYPE_MONTO  = 1;

    public const TYPE_IDS = [
        self::TYPE_DIAS     => ['id' => 0, 'nombre' => 'DÍAS'],
        self::TYPE_MONTO    => ['id' => 1, 'nombre' => 'MONTO']
    ];

    public const TYPE_NOM = [
        self::TYPE_DIAS     => 'DÍAS',
        self::TYPE_MONTO    => 'MONTO',
    ];

    public const TYPE_DESC = [
        self::TYPE_DIAS     => 'Ajuste de días',
        self::TYPE_MONTO    => 'Ajuste de montos ($)',
    ];

    protected $table        = "reajustes";
    protected $primaryKey   = 'id';

    protected $fillable = [
        'uuid',
        'fecha_inicio',
        'fecha_termino',
        'dias',
        'dias_periodo',
        'valor_dia',
        'monto_ajuste',
        'observacion',
        'incremento',
        'tipo_reajuste',
        'last_status',
        'user_id',
        'tipo_ausentismo_id',
        'tipo_incremento_id',
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

    public function tipoAusentismo()
    {
        return $this->belongsTo(TipoAusentismo::class, 'tipo_ausentismo_id');
    }

    public function tipoIncremento()
    {
        return $this->belongsTo(TipoIncremento::class, 'tipo_incremento_id');
    }

    public function estados()
    {
        return $this->hasMany(ReajusteEstado::class)->orderBy('created_at', 'desc');
    }

    public function latestStatus()
    {
        return $this->hasOne(ReajusteEstado::class)->latest();
    }

    public function userCreatedBy()
    {
        return $this->belongsTo(User::class, 'user_created_by');
    }

    protected static function booted()
    {
        static::creating(function ($reajuste) {
            $fecha_inicio   = Carbon::parse($reajuste->fecha_inicio);
            $fecha_termino  = Carbon::parse($reajuste->fecha_termino);
            $days           = $fecha_inicio->diffInDays($fecha_termino) + 1;

            $reajuste->uuid                 = Str::uuid();
            $reajuste->dias_periodo         = $days;
            $reajuste->user_created_by      = Auth::user()->id;
            $reajuste->date_created_user    = Carbon::now()->toDateTimeString();
            $reajuste->last_status          = ReajusteEstado::STATUS_PENDIENTE;

        });
    }

    public function scopeInput($query, $input)
    {
        if ($input)
            return $query->where(function ($query) use ($input) {
                $query->whereHas('funcionario', function ($query) use ($input) {
                    $query->where('rut_completo', 'like', '%' . $input . '%')
                        ->orWhere('nombre_completo', 'like', '%' . $input . '%');
                });
            });
    }

    public function scopeTipos($query, $tipos)
    {
        if ($tipos)
            return $query->whereIn('tipo_reajuste', $tipos);
    }

    public function scopeEstados($query, $estados)
    {
        if ($estados)
            return $query->whereIn('last_status', $estados);
    }
}
