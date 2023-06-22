<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Establecimiento extends Model
{
    protected $table = "establecimientos";
    protected $primaryKey = 'id';

    protected $fillable = [
        'cod_sirh',
        'sigla',
        'nombre'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
