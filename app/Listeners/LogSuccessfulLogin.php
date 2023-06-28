<?php

namespace App\Listeners;

use App\Models\LoginHistory;
use Illuminate\Auth\Events\Login;
use Illuminate\Http\Request;

class LogSuccessfulLogin
{

    public function __construct(Request $request)
    {
        $this->request = $request;
    }

    public function handle(Login $event)
    {
        try {
            $user = $event->user;
            LoginHistory::create([
                'user_id'       => $user->id,
                'ip_address'    => $this->request->ip(),
                'login_at'      => now(),
            ]);
        } catch (\Exception $e) {
            report($e);
            \Log::error($e->getMessage());
        }
    }
}
