<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class CartolaLinksMasivo extends Mailable
{
    use Queueable, SerializesModels;
    public $esquema;
    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct($esquema)
    {
        $this->esquema = $esquema;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->markdown('emails.cartola-links-masivo')->subject("SBA - Cartola mensual beneficio de alimentación")->withSwiftMessage(function($message){
            $message->setPriority(\Swift_Message::PRIORITY_HIGHEST);
        });
    }
}
