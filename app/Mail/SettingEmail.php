<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class SettingEmail extends Mailable
{
    use Queueable, SerializesModels;

    private string $link;
    private $customer;
    private $email;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct($customer, $email, $link)
    {
        $this->customer = $customer;
        $this->email = $email;
        $this->link = $link;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->subject('メールアドレスの変更 ')
            ->from(config('mail.from.address'), config('mail.from.name'))
            ->view('mail_template.setting_email_customer', [
                'customer' => $this->customer,
                'email' => $this->email,
                'link' => $this->link
            ]);
    }
}
