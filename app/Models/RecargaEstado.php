<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class RecargaEstado extends Model
{
    protected $table = "recarga_estados";
    protected $primaryKey = 'id';

    protected $fillable = [
        'id',
        'status',
        'user_id',
        'recarga_id'
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function recarga()
    {
        return $this->belongsTo(Recarga::class, 'recarga_id');
    }

    protected static function booted()
    {
        static::creating(function ($estado) {
            $estado->user_id                = Auth::user()->id;
        });
    }
}
