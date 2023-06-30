<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Unidad extends Model
{
    protected $table = "unidads";
    protected $primaryKey = 'id';

    protected $fillable = [
        'cod_sirh',
        'nombre'
    ];

    public function contratos()
    {
        return $this->hasMany(RecargaContrato::class);
    }
}
