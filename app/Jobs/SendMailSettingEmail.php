<?php

namespace App\Jobs;

use App\Mail\SettingEmail;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;

class SendMailSettingEmail implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private $email;
    private $link;
    private $customer;

    /**
     * Create a new job instance.
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
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        Mail::to($this->email)->send(new SettingEmail($this->customer, $this->email, $this->link));
    }
}
