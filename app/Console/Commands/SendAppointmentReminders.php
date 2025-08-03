<?php
namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Appointment;
use App\Services\NotificationService;


class SendAppointmentReminders extends Command
{
    protected $signature = 'reminders:appointments';
    protected $description = 'Send reminders to patients who have appointments in 24 hours';

    public function handle()
    {
        // احسب الوقت بعد 24 ساعة من الآن (دقيقة دقيقة)
        $targetDateTime = now()->addDay(); // الآن + 24 ساعة
        $targetDate = $targetDateTime->toDateString();
        $targetTime = $targetDateTime->format('H:i:s'); // وقت الموعد بالضبط

// حساب مجال زمني ±5 دقائق
        $fromTime = $targetDateTime->copy()->subMinutes(5)->format('H:i:s');
        $toTime = $targetDateTime->copy()->addMinutes(5)->format('H:i:s');

        // جلب كل المواعيد التي تحدث بالضبط بعد 24 ساعة
        $appointments = Appointment::with(['slot', 'patient.user'])
            ->whereDate('date', $targetDate)          // مواعيد الغد
            ->whereRelation('slot','start_time', '>=', $fromTime)
            ->whereRelation('slot','start_time', '<=', $toTime)
            ->where('status', 'pending')
            ->get();

        if ($appointments->isEmpty()) {
            $this->info("⚠ لا يوجد مواعيد بعد 24 ساعة من الآن.");
            return;
        }

        foreach ($appointments as $appointment) {
            $user = $appointment->patient->user ?? null;
            if (!$user || !$user->fcm_token) continue;

            $title = "تذكير بموعدك في العيادة";
            $time = \Carbon\Carbon::parse($appointment->slot->start_time)->format('h:i A');
            $body = "موعدك غدًا الساعة " . $time;
            $type = $type = 'reminders';


            app(NotificationService::class)->sendFCMNotification($user->fcm_token, $title, $body,$type);

            $this->info("send {$user->first_name} to {$appointment->slot->start_time}");
        }

        $this->info("send all notification!");
    }

}
