<?php


namespace App\Console\Tasks;

use App\Models\Vacation;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\ServiceProvider;

class TaskServiceProvider extends ServiceProvider
{
    public function boot(Schedule $schedule): void
    {
        $schedule->command('reminders:appointments')
            ->everyMinute()
            ->between('09:00', '21:00');

        $schedule->command('app:update-expired-vacations')->daily();

        $schedule->command('offers:prune-expired')->daily();
    }
}

