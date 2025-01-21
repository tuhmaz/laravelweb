<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class VerificationEmail extends Mailable
{
    use Queueable, SerializesModels;

    public $user;
    public $verificationUrl;

    public function __construct($user, $verificationUrl)
    {
        $this->user = $user;
        $this->verificationUrl = $verificationUrl;
    }

    public function build()
    {
        return $this->subject('تأكيد البريد الإلكتروني - ' . config('app.name'))
            ->priority(1)
            ->from(config('mail.from.address'), config('mail.from.name'))
            ->replyTo(config('mail.from.address'), config('mail.from.name'))
            ->metadata([
                'custom_domain' => 'alemedu.com',
                'message_type' => 'verification'
            ])
            ->headers([
                'X-Priority' => '1',
                'X-MSMail-Priority' => 'High',
                'Importance' => 'High'
            ])
            ->view('emails.verification');
    }
}
