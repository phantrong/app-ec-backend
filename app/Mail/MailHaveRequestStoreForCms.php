<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class MailHaveRequestStoreForCms extends Mailable
{
    use Queueable, SerializesModels;

    private array $data;

    /**
     * Create a new message instance.
     *
     * @param array $input
     */
    public function __construct(array $data)
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
        return $this->subject('CMSに店舗の登録があるメール')
            ->from(config('mail.from.address'), config('mail.from.name'))
            ->view('mail_template.notify_for_cms_have_request_store', [
                'data' => $this->data,
            ]);
    }
}
