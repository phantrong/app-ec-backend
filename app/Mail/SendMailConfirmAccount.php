<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class SendMailConfirmAccount extends Mailable
{
    use Queueable, SerializesModels;

    private array $customer;
    private string $template;
    private string $linkSignUp;
    private string $fakePassword;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct($customer, $template, $linkSignUp = '', $fakePassword = '')
    {
        $this->customer = $customer;
        $this->template = $template;
        $this->linkSignUp = $linkSignUp;
        $this->fakePassword = $fakePassword;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        if ($this->template == 'mail_template.approve_account') {
            return $this->subject('店舗を登録する承認メール')
                ->from(config('mail.from.address'), config('mail.from.name'))
                ->view($this->template, [
                    'customer' => $this->customer,
                    'linkSignUp' => $this->linkSignUp,
                    'fakePassword' => $this->fakePassword,
                ]);
        }
        return $this->subject('店舗を登録する断るメール')
            ->from(config('mail.from.address'), config('mail.from.name'))
            ->view($this->template, [
                'customer' => $this->customer,
                'linkSignUp' => $this->linkSignUp,
                'fakePassword' => $this->fakePassword,
            ]);
    }
}
