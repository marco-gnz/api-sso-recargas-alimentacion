<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GrupoAusentismo extends Model
{
    protected $table = "grupo_ausentismos";
    protected $primaryKey = 'id';

    protected $fillable = [
        'n_grupo',
        'nombre',
        'descripcion'
    ];

    public $timestamps = false;

    public function ausentismos()
    {
        return $this->hasMany(Ausentismo::class);
    }
}
