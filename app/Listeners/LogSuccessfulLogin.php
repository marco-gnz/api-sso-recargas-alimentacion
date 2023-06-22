<?php

namespace App\Listeners;

use App\Models\LoginHistory;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Auth\Events\Login;

class LogSuccessfulLogin implements ShouldQueue
{
    use InteractsWithQueue;

    public function handle(Login $event)
    {
        try {
            $user = $event->user;
            LoginHistory::create([
                'user_id' => $user->id,
                'ip_address' => request()->ip(),
                'login_at' => now(),
            ]);
        } catch (\Exception $e) {
            report($e);
            \Log::error($e->getMessage());
        }
    }
}
