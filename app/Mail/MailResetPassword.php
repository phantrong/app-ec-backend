<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class MailResetPassword extends Mailable
{
    use Queueable, SerializesModels;

    private array $link;
    private $customer;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct($customer, $link)
    {
        $this->link = $link;
        $this->customer = $customer;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->subject('パスワード再設定のお知らせ')
            ->from(config('mail.from.address'), config('mail.from.name'))
            ->view('mail_template.reset_password', [
                'customer' => $this->customer,
                'links' => $this->link
            ]);
    }
}
