<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class RegisterCustomer extends Mailable
{
    use Queueable, SerializesModels;

    private object $customer;
    private mixed $content;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct($customer, $content)
    {
        $this->customer = $customer;
        $this->content = $content;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build(): static
    {
        return $this->subject('ご本人確認のご案内')
            ->from(config('mail.from.address'), config('mail.from.name'))
            ->view('mail_template.register', ['customer' => $this->customer, 'content' => $this->content]);
    }
}
