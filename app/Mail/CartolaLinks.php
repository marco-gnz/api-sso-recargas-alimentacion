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
        try {
            $email = $this->markdown('emails.cartola-links')
                ->subject("SBA - Cartola beneficio de alimentación")
                ->withSwiftMessage(function ($message) {
                    $message->setPriority(\Swift_Message::PRIORITY_HIGHEST);
                });

            foreach ($this->esquemas as $esquema) {
                $pdf = \PDF::loadView('pdf.funcionario.cartola', [
                    'esquema' => $esquema,
                ]);

                $pdf->setOptions([
                    'chroot' => public_path('/img/'),
                ]);

                $password_funcionario   = $esquema->funcionario->rut;
                $password_admin         = "1234";
                $pdf->setEncryption($password_funcionario, $password_admin, ['copy', 'print', 'modify']);

                $pdfContent = $pdf->output();

                $email->attachData($pdfContent, $esquema->nameFieldPdf(), [
                    'mime' => 'application/pdf',
                ]);
            }

            return $email;
        } catch (\Exception $e) {
            \Log::error("Error al construir el correo: " . $e->getMessage(), [
                'esquemas' => array_map(fn($esquema) => $esquema->id ?? null, $this->esquemas),
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e;
        }
    }

}
