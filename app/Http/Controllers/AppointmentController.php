<?php

namespace App\Http\Controllers;

use App\Http\Resources\AppointmentResource;
use App\Models\Appointment;
use App\Services\AppointmentService;
use Carbon\Carbon;
use Carbon\CarbonTimeZone;
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
    public function show($id)
    {
        $appointment = Appointment::with(['slot', 'doctor']) // تأكد أن العلاقات معرفة
        ->findOrFail($id);

        // تحويل النوع إلى request_type_id
        $requestTypeId = match ($appointment->type) {
            'check_up' => 1,
            'follow_up' => 2,
            default => null
        };

        // استخراج time_id من جدول الـ slot
        $timeId = $appointment->slot_id;

        return response()->json([
            'department_id'       => $appointment->department_id,
            'doctor_id'           => $appointment->doctor_id,
            'request_type_id'     => $requestTypeId,
            'day'                 => $appointment->date,
            'time_id'             => $timeId,
            'with_medical_report' => (bool) $appointment->with_medical_report,
        ]);
    }

    public function canCancelAppointment($id, AppointmentService $service)
    {
        $appointment = Appointment::findOrFail($id);

        if (!$service->canBeCancelled($appointment)) {
            return response()->json([
                'can_cancel' => false,
            ], 403);
        }

        return response()->json([
            'can_cancel' => true,
        ]);
    }

    public function destroy($id,AppointmentService $service)
    {
        $appointment = Appointment::with('patient.user')->findOrFail($id);

        // تحقق أن المستخدم الحالي هو صاحب الموعد
        if ($appointment->patient->user_id !== auth()->id()) {
            return response()->json([
                'message' => 'You are not authorized to delete this appointment.'
            ], 403);
        }

        // تحقق من إمكانية الإلغاء (عن طريق الـ Service)
        if (!$service->canBeCancelled($appointment)) {
            return response()->json([
                'can_cancel' => false,
            ], 403);
        }


        // تحقق من أن حالة الموعد Pending فقط
        if ($appointment->status !== 'pending') {
            return response()->json([
                'message' => 'Only pending appointments can be cancelled.'
            ], 403);
        }

        $appointment->delete();

        return response()->json([
            'message' => 'Appointment cancelled successfully.'
        ]);
    }

}
