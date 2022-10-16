<?php

namespace App\Mail;

use App\Models\Store;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class SendMailStripeRejectAccount extends Mailable
{
    use Queueable, SerializesModels;

    private array $data; // [name, link]

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct($data)
    {
        $this->data = $data;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->subject('店舗が登録できないメール')
            ->from(config('mail.from.address'), config('mail.from.name'))
            ->view('mail_template.stripe_reject_account', [
                'data' => $this->data
            ]);
    }
}
