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
        $appointments = Appointment::with(['slot', 'patient.user'])
            ->whereDate('date', now()->addDay()->toDateString())
            ->where('status', 'pending')
            ->get();

        if ($appointments->isEmpty()) {
            $this->info(" NO APPOINTMENTS TOMORROW");
            return;
        }
        foreach ($appointments as $appointment) {
            //جلب المستخدم متعلق بالحجز
            $user = $appointment->patient->user;


            if (!$user || !$user->fcm_token) continue;//تجاهل ما تبقى من حلقة عند تنقيذ الشرط

            $appointmentTime = $appointment->date . ' ' . $appointment->slot->start_time;

            $title = "تذكير بموعدك في العيادة";
            $body = "لديك موعد غدًا في تمام الساعة " . date('H:i', strtotime($appointment->slot->start_time));

            // إرسال الإشعار
            app(NotificationService::class)->sendFCMNotification($user->fcm_token, $title, $body);

        }

        $this->info(" تم ارسال جميع الاشعارات بنجاح");
    }
}
