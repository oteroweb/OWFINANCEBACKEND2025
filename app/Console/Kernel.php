<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     * NOTE: In Laravel 12, scheduling is defined in bootstrap/app.php via withSchedule().
     * This method is kept for reference only.
     */
    protected function schedule(Schedule $schedule): void
    {
        // Scheduled in bootstrap/app.php
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
