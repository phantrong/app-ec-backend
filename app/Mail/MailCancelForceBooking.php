<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class MailCancelForceBooking extends Mailable
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
        return $this->subject('接客の強制キャンセル')
            ->from(config('mail.from.address'), config('mail.from.name'))
            ->view('mail_template.cancel_force_booking', [
                'input' => $this->input,
            ]);
    }
}
