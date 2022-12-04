<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class SendMailConfirmAccount extends Mailable
{
    use Queueable, SerializesModels;

    private array $info;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct($info)
    {
        $this->info = $info;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->subject('Thư gửi từ hệ thống MY CART')
            ->from(config('mail.from.address'), config('mail.from.name'))
            ->view('mail_template.approve_account', [
                'info' => $this->info
            ]);
    }
}
