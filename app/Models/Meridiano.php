<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Meridiano extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $table = "meridianos";
    protected $primaryKey = 'id';

    protected $fillable = [
        'nombre',
        'codigo'
    ];

    public function reglas()
    {
        return $this->belongsToMany(Regla::class);
    }
}
