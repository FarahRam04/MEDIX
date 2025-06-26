<?php


namespace App\Console\Scheduling;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\ServiceProvider;

class ScheduleServiceProvider extends ServiceProvider
{
    public function boot(Schedule $schedule): void
    {
        // أمر التذكير بالمواعيد كل ساعة
        $schedule->command('reminders:appointments')->hourly();

        // أو إذا تريده يومياً الساعة 12
        // $schedule->command('reminders:appointments')->dailyAt('12:00');
    }
}

