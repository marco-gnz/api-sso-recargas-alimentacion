<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TipoIncremento extends Model
{
    use HasFactory;

    protected $table = "tipo_incrementos";
    protected $primaryKey = 'id';

    protected $fillable = [
        'codigo_sirh',
        'nombre',
        'sigla',
        'estado'
    ];

    public $timestamps = false;

    public function scopeInput($query, $input)
    {
        if ($input)
            return $query->where('codigo_sirh', 'like', '%' . $input . '%')
                ->orWhere('nombre', 'like', '%' . $input . '%')
                ->orWhere('sigla', 'like', '%' . $input . '%');
    }
}
