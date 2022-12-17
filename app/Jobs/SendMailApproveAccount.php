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
    private array $info;
    private string $mail_template;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($email, $info, $mail_template)
    {
        $this->email = $email;
        $this->info = $info;
        $this->mail_template = $mail_template;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        Mail::to($this->email)->send(new SendMailConfirmAccount(
            $this->info, $this->mail_template
        ));
    }
}
