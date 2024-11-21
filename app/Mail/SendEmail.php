<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class SendEmail extends Mailable
{
    use Queueable, SerializesModels;

    public $emailData;

    public function __construct($emailData)
    {
        $this->emailData = $emailData;
    }

    public function build()
    {
        /*
        return $this->view('emails.email_template')
                    ->subject('Bem-vindo ao SeuApp');
        */
        return $this->view('emails.email_template')
                    ->subject($this->emailData['subject'])
                    ->with('data', $this->emailData);
    }
}
