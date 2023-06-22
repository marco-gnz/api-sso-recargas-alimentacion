<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Auth\AuthenticationException;
use App\Models\User;
use Illuminate\Http\Request;

class LoginController extends Controller
{
    public function __construct()
    {
        $this->middleware(['guest']);
    }

    public function login(LoginRequest $request)
    {
        try {

            $user = User::where('email', $request->email)->first();

            if ($user && !$user->estado) {
                return response('inhabilitado', 503);
            }

            if (!$user || !auth()->guard()->attempt($request->only('email', 'password'))) {
                throw new AuthenticationException();
            }

        } catch (\Exception $error) {
            return $error->getMessage();
        }
    }
}
