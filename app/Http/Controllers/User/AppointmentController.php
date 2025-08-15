<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Http\Resources\AppointmentResource;
use App\Models\Appointment;
use App\Services\AppointmentService;
use Carbon\Carbon;
use Illuminate\Http\Request;

class AppointmentController extends Controller
{

    public function getPatientAppointments(Request $request)
    {
        // تحقق من أن المستخدم الحالي هو مريض
        $user = auth()->user();
        $request->validate([
            'status'=>'required|in:pending,completed'
        ]);
        $status = $request->query('status');

        if (!$user->patient) {
            return response()->json([], 200);
        }

        // جلب المريض المرتبط بالمستخدم
        $patient = $user->patient;

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

        if (!$service->canBeCancelledAndEdited($appointment)) {
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
        if (!$service->canBeCancelledAndEdited($appointment)) {
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

    public function testBill($id)
    {
        $appointment = Appointment::with('patient.user')->findOrFail($id);
        return response()->json([
            'Id'=>$appointment->id,
            'Status'=>$appointment->payment_status === 0 ? 'Unpaid' : 'paid',
            'Payment Date'=>Carbon::today()->format('Y-m-d'),
            'Payment Time'=>Carbon::now()->format('H:i:s'),
            'Payment Method'=>$appointment->total_price === 0 ?'Points' : 'Cash',
            'Check_Up Price'=>'50000 SYP',
            'Medical Report Price'=>$appointment->with_medical_report ? '25000 SYP' : '0 SYP',
            'Total Price'=>$appointment->total_price,
        ]);
    }

}
