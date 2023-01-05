<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use Illuminate\Support\Str;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $table = "users";
    protected $primaryKey = 'id';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'uuid',
        'rut',
        'dv',
        'rut_completo',
        'nombres',
        'apellidos',
        'nombre_completo',
        'estado',
        'email',
        'password',
        'turno',
        'establecimiento_id',
        'unidad_id',
        'planta_id',
        'cargo_id',
        'usuario_add_id',
        'fecha_add'
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    /* protected $guarded = ['id']; */

    public function ausentismos()
    {
        return $this->hasMany(Ausentismo::class);
    }

    public function asistencias()
    {
        return $this->hasMany(Asistencia::class);
    }

    public function turnos()
    {
        return $this->hasMany(UserTurno::class);
    }

    public function establecimiento()
    {
        return $this->belongsTo(Establecimiento::class, 'establecimiento_id');
    }

    public function unidad()
    {
        return $this->belongsTo(Unidad::class, 'unidad_id');
    }

    public function planta()
    {
        return $this->belongsTo(Planta::class, 'planta_id');
    }

    public function cargo()
    {
        return $this->belongsTo(Cargo::class, 'cargo_id');
    }

    public function recargas()
    {
        return $this->belongsToMany(Recarga::class)->withPivot('beneficio');
    }

    protected static function booted()
    {
        static::creating(function ($usuario) {
            $usuario->uuid                  = Str::uuid();
            $usuario->rut_completo          = $usuario->rut.'-'.$usuario->dv;
            $usuario->nombre_completo       = $usuario->nombres.' '.$usuario->apellidos;
            $usuario->password              = bcrypt($usuario->rut);
            /* $usuario->usuario_add_id        = Auth::user()->id; */
            $usuario->fecha_add             = Carbon::now()->toDateTimeString();
        });

        static::updating(function ($usuario) {
            $usuario->usuario_update_id         = Auth::user()->id;
            $usuario->fecha_update              = Carbon::now()->toDateTimeString();
        });
    }
}
