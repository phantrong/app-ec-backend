<?php

namespace App\Jobs;

use App\Mail\SendMailConfirmAccount;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;

class SendMailApproveAccount implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private string $email;
    private array $customer;
    private string $template;
    private string $linkSignUp;
    private string $fakePassword;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($email, $customer, $template, $linkSignUp = '', $fakePassword = '')
    {
        $this->email = $email;
        $this->customer = $customer;
        $this->template = $template;
        $this->linkSignUp = $linkSignUp;
        $this->fakePassword = $fakePassword;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        Mail::to($this->email)->send(new SendMailConfirmAccount(
            $this->customer,
            $this->template,
            $this->linkSignUp,
            $this->fakePassword
        ));
    }
}
