<?php

namespace App\Jobs;

use App\Mail\MailCancelForceBooking;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;

class SendMailCancelForceBooking implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private array $input;

    /**
     * Create a new job instance.
     *
     * @param array $input
     */
    public function __construct(array $input)
    {
        $this->input = $input;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        Mail::to($this->input['store']['email'])->send(new MailCancelForceBooking($this->input['store']));
        if ($this->input['customer']['send_mail']) {
            Mail::to($this->input['customer']['email'])->send(new MailCancelForceBooking($this->input['customer']));
        }
    }
}
