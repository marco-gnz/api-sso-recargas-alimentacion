<?php

namespace App\Listeners;

use App\Events\CartolaEnviadaMasivo;
use App\Mail\CartolaLinksMasivo;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Mail;

class EnviarLinksCartolaMasivo
{

    public function handle(CartolaEnviadaMasivo $event)
    {
        Mail::to($event->esquema->funcionario->email)->queue(
            new CartolaLinksMasivo($event->esquema)
        );
    }
}
