<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ReajusteAlerta extends Model
{
    public const TIPO_ADVERTENCIA   = 0;
    public const TIPO_ERROR         = 1;

    public const TIPO_NOM = [
        self::TIPO_ADVERTENCIA  => 'ADVERTENCIA',
        self::TIPO_ERROR        => 'ERROR'
    ];

    protected $table        = "reajuste_alertas";
    protected $primaryKey   = 'id';

    protected $fillable = [
        'observacion',
        'tipo',
        'reajuste_id'
    ];

    public $timestamps = false;

    public function reajuste()
    {
        return $this->belongsTo(Reajuste::class, 'reajuste_id');
    }
}
