<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class MailMarkViolation extends Mailable
{
    use Queueable, SerializesModels;

    private array $input;

    /**
     * Create a new message instance.
     *
     * @param array $input
     */
    public function __construct(array $input)
    {
        $this->input = $input;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->subject('商品は依頼になってしましたメール')
            ->from(config('mail.from.address'), config('mail.from.name'))
            ->view('mail_template.mark_violation', [
                'input' => $this->input,
            ]);
    }
}
