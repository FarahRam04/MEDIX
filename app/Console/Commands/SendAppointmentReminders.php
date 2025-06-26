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

        foreach ($appointments as $appointment) {
            $user = $appointment->patient->user;

            // تحقق من وجود توكين صالح
            if (!$user || !$user->fcm_token) continue;

            $appointmentTime = $appointment->date . ' ' . $appointment->slot->start_time;

            $title = "تذكير بموعدك في العيادة";
            $body = "لديك موعد غدًا في تمام الساعة " . date('H:i', strtotime($appointment->slot->start_time));

            // إرسال الإشعار
            app(NotificationService::class)->sendFCMNotification($user->fcm_token, $title, $body);

        }

        $this->info('Appointment reminders sent.');
    }
}
