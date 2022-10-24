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

    protected static function booted()
    {
        static::creating(function ($usuario) {
            $usuario->uuid                  = Str::uuid();
            $usuario->rut_completo          = $usuario->rut.'-'.$usuario->dv;
            $usuario->nombre_completo       = $usuario->nombres.' '.$usuario->apellidos;
            $usuario->password              = bcrypt($usuario->rut);
            $usuario->usuario_add_id        = Auth::user()->id;
            $usuario->fecha_add             = Carbon::now()->toDateTimeString();
        });

        static::updating(function ($usuario) {
            $usuario->usuario_update_id         = Auth::user()->id;
            $usuario->fecha_update              = Carbon::now()->toDateTimeString();
        });
    }
}
