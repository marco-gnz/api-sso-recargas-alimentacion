<?php

namespace App\Mail;

use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Dompdf\Dompdf;

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
        try {
            $pdf = \PDF::loadView('pdf.funcionario.cartola', [
                'esquema'        => $this->esquema,
            ]);

            $pdf->setOptions([
                'chroot'  => public_path('/img/')
            ]);

            $password_funcionario   = $this->esquema->funcionario->rut;
            $password_admin         = "1234";
            $pdf->setEncryption($password_funcionario, $password_admin, ['copy', 'print', 'modify']);

            $pdfContent = $pdf->output();

            return $this->markdown('emails.cartola-links-masivo')
                ->subject("SBA - Cartola mensual beneficio de alimentación")
                ->attachData($pdfContent, $this->esquema->nameFieldPdf(), [
                    'mime' => 'application/pdf',
                ])
                ->withSwiftMessage(function ($message) {
                    $message->setPriority(\Swift_Message::PRIORITY_HIGHEST);
                });
        } catch (\Exception $e) {
            \Log::error("Error al construir el correo: " . $e->getMessage(), [
                'esquema_id' => $this->esquema->id ?? null,
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }
}
