<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        $schedule->command('check:booking_status')->cron('* * * * *');
        $schedule->command('cancel:livestream')->cron('* * * * *');
        $schedule->command('insert:revenueProduct')->dailyAt('01:00');
        $schedule->command('insert:revenueOrder')->dailyAt('01:00');
        $schedule->command('insert:revenueAge')->dailyAt('01:00');
        $schedule->command('update:VideoComplete')->cron('* * * * *');

        // $schedule->command('order:cancel')->cron('* * * * *');
    }

    /**
     * Register the commands for the application.
     *
     * @return void
     */
    protected function commands()
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
