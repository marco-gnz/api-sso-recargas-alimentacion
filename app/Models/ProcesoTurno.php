<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProcesoTurno extends Model
{
    protected $table = "proceso_turnos";
    protected $primaryKey = 'id';

    protected $fillable = [
        'cod_sirh',
        'nombre'
    ];

    public function scopeInput($query, $input)
    {
        if ($input)
            return $query->where('cod_sirh', 'like', '%' . $input . '%')
                ->orWhere('nombre', 'like', '%' . $input . '%');
    }
}
