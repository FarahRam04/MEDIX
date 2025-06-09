<?php

namespace App\Http\Controllers;

use App\Http\Resources\AppointmentResource;
use App\Models\Appointment;
use Illuminate\Http\Request;

class AppointmentController extends Controller
{
    public function getPatientAppointments(Request $request)
    {
        // تحقق من أن المستخدم الحالي هو مريض
        $user = auth()->user();
        if (!$user || !$user->patient) {
            return response()->json(['message' => 'Unauthorized or not a patient'], 403);
        }

        // جلب المريض المرتبط بالمستخدم
        $patient = $user->patient;

        // جلب الحالة من query (?status=completed or pending)
        $status = $request->query('status', 'completed'); // الافتراضي completed

        // جلب المواعيد من جدول appointments
        $appointments = Appointment::with(['doctor.employee.user', 'slot'])
            ->where('patient_id', $patient->id)
            ->where('status', $status)
            ->orderBy('date', 'desc')
            ->get();

        return AppointmentResource::collection($appointments);
    }
}
