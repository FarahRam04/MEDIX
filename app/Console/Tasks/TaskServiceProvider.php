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

        // تحديث حالات الإجازات يومياً عند منتصف الليل
        $schedule->call(function () {
            Vacation::where('status', 'active')
                ->where('end_day', '<', now()->toDateString())
                ->update(['status' => 'expired']);
        })->dailyAt('00:00');
    }
}

