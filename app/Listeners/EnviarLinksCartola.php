<?php

namespace App\Listeners;

use App\Events\CartolaEnviadaManual;
use App\Mail\CartolaLinks;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Mail;

class EnviarLinksCartola
{
    /**
     * Handle the event.
     *
     * @param  \App\Events\CartolaEnviadaManual  $event
     * @return void
     */
    public function handle(CartolaEnviadaManual $event)
    {
        Mail::to($event->email)->queue(
            new CartolaLinks($event->esquemas)
        );
    }
}
