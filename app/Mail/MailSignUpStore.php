<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class MailSignUpStore extends Mailable
{
    use Queueable, SerializesModels;

    private string $linkSignUp;
    private bool $isAgain;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct($linkSignUp, $isAgain)
    {
        $this->linkSignUp = $linkSignUp;
        $this->isAgain = $isAgain;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        if ($this->isAgain) {
            return $this->subject('店舗を登録する断るメール')
                ->from(config('mail.from.address'), config('mail.from.name'))
                ->view('mail_template.signup_store_again', ['linkSignUp' => $this->linkSignUp]);
        }
        return $this->subject('ユーザーが店舗を登録する前に確認メール')
            ->from(config('mail.from.address'), config('mail.from.name'))
            ->view('mail_template.signup_store', ['linkSignUp' => $this->linkSignUp]);
    }
}
