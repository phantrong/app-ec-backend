<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class MailCreateStaffSuccess extends Mailable
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
        return $this->subject('スタッフを追加')
            ->from(config('mail.from.address'), config('mail.from.name'))
            ->view('mail_template.create_staff_success', [
                'input' => $this->input,
            ]);
    }
}
