<?php

namespace App\Jobs;

use App\Mail\MailResetPassword;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;

class SendMailResetPassword implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private object $customer;
    private array $links;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($customer, $links)
    {
        $this->customer = $customer;
        $this->links = $links;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        Mail::to($this->customer->email)->send(new MailResetPassword($this->customer, $this->links));
    }
}
