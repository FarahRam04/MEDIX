<?php

namespace App\Services;

use App\Models\Appointment;
use App\Models\AvailableSlot;
use Carbon\Carbon;

class AppointmentService
{
    public function canBeCancelled(Appointment $appointment): bool
    {
        $slotTime = AvailableSlot::find($appointment->slot_id)?->start_time;
        if (!$slotTime) {
            return false;
        }
        $appointmentDateTime = Carbon::parse($appointment->date . ' ' . $slotTime);

        return Carbon::now()->diffInHours($appointmentDateTime, false) > 24;
    }
}
