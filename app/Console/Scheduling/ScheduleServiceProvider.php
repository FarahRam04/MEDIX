<?php


namespace App\Console\Scheduling;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\ServiceProvider;

class ScheduleServiceProvider extends ServiceProvider
{
    public function boot(Schedule $schedule): void
    {
        $schedule->command('reminders:appointments')
            ->everyThirtyMinutes()
            ->between('09:00', '21:00');

    }
}

