<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class CartolaLinks extends Mailable
{
    use Queueable, SerializesModels;
    public $esquemas;
    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct($esquemas)
    {
        $this->esquemas = $esquemas;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->markdown('emails.cartola-links')->subject("SBA - Envío de cartola")->withSwiftMessage(function ($message) {
            $message->setPriority(\Swift_Message::PRIORITY_HIGHEST);
        });
    }
}
