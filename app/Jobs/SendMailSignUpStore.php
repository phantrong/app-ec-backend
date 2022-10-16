<?php

namespace App\Jobs;

use App\Mail\MailSignUpStore;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;

class SendMailSignUpStore implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private string $linkSignUp;
    private string $email;
    private bool $isAgain;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($linkSignUp, $email, $isAgain)
    {
        $this->linkSignUp = $linkSignUp;
        $this->email = $email;
        $this->isAgain = $isAgain;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        Mail::to($this->email)->send(new MailSignUpStore($this->linkSignUp, $this->isAgain));
    }
}
