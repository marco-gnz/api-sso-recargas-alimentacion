<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class SeguimientoRecarga extends Model
{
    protected $table = "seguimiento_recargas";
    protected $primaryKey = 'id';

    protected $fillable = [
        'recarga_id',
        'estado_id',
        'user_id'
    ];

    public function recarga()
    {
        return $this->belongsTo(Recarga::class, 'recarga_id');
    }

    public function estado()
    {
        return $this->belongsTo(EstadoRecarga::class, 'estado_id');
    }

    public function userBy()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    protected static function booted()
    {
        static::creating(function ($seguimiento) {
            $seguimiento->user_id   = Auth::user()->id;
        });
    }
}
