<?php

namespace App\Services;

use App\Models\Appointment;
use Carbon\Carbon;

class AppointmentService
{
    public function canBeCancelled(Appointment $appointment): bool
    {
        $today = Carbon::now('Asia/Damascus')->toDateString();
        return $appointment->date > $today;
    }
}
