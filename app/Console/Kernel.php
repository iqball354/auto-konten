<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule): void
    {
        $schedule->command('posts:dispatch-scheduled')->everyMinute();
        $schedule->command('posts:monthly-cleanup')->monthlyOn(1, '03:15');
        $schedule->command('meta:refresh-tokens')->dailyAt('01:00');
        $schedule->command('insight:fetch')->dailyAt('02:00');
        $schedule->command('notifications:prune --days=30')->monthlyOn(1, '03:00');
    }

    /**
     * Register the commands for the application.
     */
    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
