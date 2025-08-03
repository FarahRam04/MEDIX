<?php

namespace App\Services;

use App\Models\Appointment;
use Carbon\Carbon;

class AppointmentService
{
    /**
     * Determine if the appointment can be cancelled.
     * The appointment can be cancelled only if:
     * - Its date is strictly after today
     */
    public function canBeCancelled(Appointment $appointment): bool
    {
        $appointmentDate = Carbon::parse($appointment->date)->toDateString();
        $today = Carbon::now()->toDateString();

        return $appointmentDate > $today;
    }
}
