<?php


namespace App\Http\Controllers\Dashboard;

use App\HelperFunctions;
use App\Http\Controllers\Controller;
use App\Http\Requests\BookAppointmentRequest;
use App\Http\Resources\DashboardAppointmentResource;
use App\Models\Appointment;
use App\Models\AvailableSlot;
use App\Models\Doctor;
use App\Models\Offer;
use App\Models\Patient;
use App\Models\Vacation;
use App\Services\AppointmentService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AAppointmentController extends Controller
{
    // استخدام الدوال المساعدة لحساب الأسعار
    use HelperFunctions;

    public function index(Request $request)
    {
        $query = Appointment::with(['patient.user', 'doctor.employee', 'department', 'slot']);

        if ($request->has('date')) {
            $query->whereDate('date', $request->date);
        } else {
            $month = $request->input('month', Carbon::now()->month);
            $year = $request->input('year', Carbon::now()->year);
            $query->whereYear('date', $year)->whereMonth('date', $month);
        }


        $query->when($request->has('status'), function ($q) use ($request) {
            return $q->where('status->en', $request->status);
        });


        $query->when($request->has('doctor_id'), function ($q) use ($request) {
            return $q->where('doctor_id', $request->doctor_id);
        });

        $query->when($request->has('department_id'), function ($q) use ($request) {
            return $q->where('department_id', $request->department_id);
        });

        $query->when($request->has('patient_name'), function ($q) use ($request) {
            return $q->whereHas('patient.user', function ($subQ) use ($request) {
                $subQ->where('first_name', 'like', '%' . $request->patient_name . '%')
                    ->orWhere('last_name', 'like', '%' . $request->patient_name . '%');
            });
        });


        $appointments = $query->orderBy('date', 'asc')->orderBy('slot_id', 'asc')->paginate(15);
        return DashboardAppointmentResource::collection($appointments);
    }


    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'patient_id' => 'required|exists:patients,id',
            'department_id' => 'required|exists:departments,id',
            'doctor_id' => 'required|exists:doctors,id',
            'date' => 'required|date|after_or_equal:today',
            'slot_id' => 'required|exists:available_slots,id',
            'request_type_id' => 'required|in:1,2', // 1 for check_up, 2 for follow_up
            'with_medical_report' => 'sometimes|boolean',
            'offer_id' => 'nullable|exists:offers,id', // العرض اختياري
        ]);

        // استخدام transaction لضمان تنفيذ كل العمليات معاً أو لا شيء
        try {
            $appointment = DB::transaction(function () use ($validatedData, $request) {

                $patient = Patient::findOrFail($validatedData['patient_id']);
                $doctor = Doctor::findOrFail($validatedData['doctor_id']);
                $offer = isset($validatedData['offer_id']) ? Offer::find($validatedData['offer_id']) : null;

                $this->validateAppointmentLogic($validatedData, $doctor, $patient);

                // 2. حساب السعر
                $finalPrice = $this->calculateAppointmentPrice($validatedData);

                // --------------------------------------------------
                // 4. إنشاء الموعد في قاعدة البيانات
                // --------------------------------------------------
                $newAppointment = Appointment::create([
                    'patient_id' => $patient->id,
                    'department_id' => $validatedData['department_id'],
                    'doctor_id' => $doctor->id,
                    'date' => $validatedData['date'],
                    'slot_id' => $validatedData['slot_id'],
                    'type' => $validatedData['request_type_id'] === 1 ? 'check_up' : 'follow_up',
                    'with_medical_report' => $validatedData['with_medical_report'] ?? false,
                    'offer_id' => ($offer && $offer->payment_method === 'cash') ? $offer->id : null,
                    'specialization' => $doctor->department->name,
                    'init_total_price' => $finalPrice,
                    'final_total_price' => $finalPrice,
                    'status' => 'pending',
                    'payment_status' => false,
                ]);

                return $newAppointment->load(['slot', 'doctor.employee', 'department', 'patient.user']);

            });

        } catch (ValidationException $e) {
            // إرجاع أخطاء التحقق المنطقي
            return response()->json(['errors' => $e->errors()], 422);
        }

        return response()->json([
            'message' => 'Appointment booked successfully!',
            'appointment' => new DashboardAppointmentResource($appointment)
        ], 201); // 201 = Created
    }

    // يمكنك إضافة دوال index, show, update, destroy هنا لاحقاً


    /**
     * Display the specified resource.
     */
    public function show($id)
    {

        $appointment = Appointment::with(['patient.user', 'doctor.employee', 'department', 'slot'])->find($id);
        if (!$appointment) {
            return response()->json(['message' => 'Appointment not found.'], 404);
        }
        return response()->json(['message'=>'Appointment and its details.',
            'Appointment'=>new DashboardAppointmentResource($appointment)],200);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(BookAppointmentRequest $request, $id, AppointmentService $service)
    {
        // 1. ابحث عن الموعد المطلوب
        $appointment = Appointment::find($id);
        if(!$appointment){
            return response()->json(['message'=>'Appointment not found']);
        }


        if ($appointment->status === 'completed') {
            return response()->json(['message' => 'Cannot update a completed appointment.'], 403);
        }

        if (!$service->canBeCancelledAndEdited($appointment)) {
            return response()->json(['message' => 'This appointment cannot be updated as it is less than 24 hours away.'], 403);
        }

        $validatedData= $request->validated();
        // 4. ابدأ الـ Transaction لضمان سلامة البيانات
        try {
            $updateAppointment=DB::transaction(function () use ($validatedData, $appointment) {

                // *** ملاحظة: لقد أزلنا التحقق من ملكية المريض للموعد ***
                // موظف الاستقبال لديه صلاحية تعديل أي موعد

                $doctor = Doctor::findOrFail($validatedData['doctor_id']);
                $this->validateAppointmentLogic($validatedData, $doctor, $appointment->patient, $appointment->id);

                // إعادة حساب السعر وتحديثه
                $newPrice = $this->calculateAppointmentPrice($validatedData);

                $appointment->update([
                    'doctor_id'           => $validatedData['doctor_id'],
                    'department_id'       => $validatedData['department_id'],
                    'date'                => $validatedData['date'],
                    'slot_id'             => $validatedData['slot_id'],
                    'type'                => $validatedData['request_type_id'] === 1 ? 'check_up' : 'follow_up',
                    'with_medical_report' => $validatedData['with_medical_report'] ?? false,
                    'specialization'      => $doctor->department->name,
                    'init_total_price'   => $newPrice,
                    'final_total_price' => $newPrice,
                    'status' => 'pending',
                    'payment_status' => false,
                ]);
                return $appointment->load(['slot', 'doctor.employee', 'department', 'patient.user']);
            });


        } catch (ValidationException $e) {
            return response()->json(['errors' => $e->errors()], 422);
        }

        return response()->json(['message' => 'Appointment updated successfully.',
            'appointment' => new DashboardAppointmentResource($updateAppointment)],200);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id, AppointmentService $service)
    {
        $appointment = Appointment::find($id);

        if (!$appointment) {
            return response()->json(['message' => 'Appointment not found.'], 404);
        }
        if ($appointment->status === 'completed') {
            return response()->json(['message' => 'Cannot cancel a completed appointment.'], 403);
        }
        if (!$service->canBeCancelledAndEdited($appointment)) {
            return response()->json(['message' => 'This appointment cannot be cancelled as it is less than 24 hours away.'], 403);
        }
        $deletAppointment=new DashboardAppointmentResource($appointment);
        $appointment->delete();
        return response()->json(['message' => 'Appointment cancelled successfully.',
            'Deleted Appointment '=>$deletAppointment
            ],200);
    }

//////////////////////////////////////////////////////////////////////////////////
    private function validateAppointmentLogic(array $data, Doctor $doctor, Patient $patient, $appointmentIdToIgnore = null)
    {
        $appointmentDate = Carbon::parse($data['date']);
        $dayOfWeek = $appointmentDate->dayOfWeek;
        $selectedSlot = AvailableSlot::find($data['slot_id']);
        $selectedSlotTime = $selectedSlot->start_time;

        // التحقق 0: هل الموعد في الماضي؟
        if (Carbon::parse($data['date'] . ' ' . $selectedSlotTime)->isPast()) {
            throw ValidationException::withMessages(['slot_id' => 'Cannot book an appointment in the past.']);
        }

        // التحقق 1: هل الطبيب في إجازة؟
        $isOnVacation = Vacation::where('employee_id', $doctor->employee_id)
            ->where('status', 'active')
            ->where('start_day', '<=', $appointmentDate)
            ->where('end_day', '>=', $appointmentDate)
            ->exists();
        if ($isOnVacation) {
            throw ValidationException::withMessages(['doctor_id' => 'The selected doctor is on vacation on this date.']);
        }

        // التحقق 2: هل الوقت يقع ضمن دوام الطبيب؟
        $isSlotInShift = DB::table('times')
            ->join('day_time', 'times.id', '=', 'day_time.time_id')
            ->where('times.employee_id', $doctor->employee_id)
            ->where('day_time.day_id', $dayOfWeek)
            ->where('times.start_time', '<=', $selectedSlotTime)
            ->where('times.end_time', '>', $selectedSlotTime)
            ->exists();
        if (!$isSlotInShift) {
            throw ValidationException::withMessages(['slot_id' => 'The selected time slot is outside the doctor\'s working hours.']);
        }

        // التحقق 3: هل الوقت محجوز مسبقاً لهذا الطبيب؟
        $alreadyBookedQuery = Appointment::where('doctor_id', $doctor->id)
            ->where('slot_id', $data['slot_id'])
            ->where('date', $data['date']);

        if ($appointmentIdToIgnore) {
            $alreadyBookedQuery->where('id', '!=', $appointmentIdToIgnore);
        }

        if ($alreadyBookedQuery->exists()) {
            throw ValidationException::withMessages(['slot_id' => 'This time slot is already booked for this doctor.']);
        }

        // التحقق 4: هل لدى المريض موعد آخر في نفس الوقت؟
        $patientConflictQuery = Appointment::where('patient_id', $patient->id)
            ->where('slot_id', $data['slot_id'])
            ->where('date', $data['date']);

        if ($appointmentIdToIgnore) {
            $patientConflictQuery->where('id', '!=', $appointmentIdToIgnore);
        }

        if ($patientConflictQuery->exists()) {
            throw ValidationException::withMessages(['patient_id' => 'The patient already has another appointment at this exact time.']);
        }

        // التحقق 5: شرط المتابعة
        if ($data['request_type_id'] == 2) {
            $visitedRecently = Appointment::where('doctor_id', $doctor->id)
                ->where('patient_id', $patient->id)
                ->where('type', 'check_up')
                ->whereDate('date', '>=', $appointmentDate->copy()->subDays(15))
                ->whereDate('date', '<', $appointmentDate)
                ->exists();
            if (!$visitedRecently) {
                throw ValidationException::withMessages(['request_type_id' => 'A follow-up can only be booked if the patient had a check-up with this doctor in the last 15 days.']);
            }
        }
    }
    private function calculateAppointmentPrice(array $data): float|int
    {
        $offer = isset($data['offer_id']) ? Offer::find($data['offer_id']) : null;

        if ($offer && $offer->payment_method === 'cash') {
            // استخدام دالة حساب سعر العرض من الـ Trait
            return $this->getTotalOfferPrice(
                $offer->id,
                $data['request_type_id'],
                $data['with_medical_report'] ?? false
            );
        } else {
            // استخدام دالة حساب السعر العادي من الـ Trait
            return $this->getTotalPriceWithoutOffer(
                $data['request_type_id'],
                $data['with_medical_report'] ?? false
            );
        }
    }

    public function getDoctorsByDepartment(Request $request)
    {
        $validated = $request->validate([
            'department_id' => 'required|exists:departments,id'
        ]);


        $doctors = Doctor::with('employee')
            ->where('department_id', $validated['department_id'])
            ->get();
        if ($doctors->isEmpty()) {
            return response()->json([
                'message' => 'No doctors found for the selected department.'
            ], 404);
        }


        $formattedDoctors = $doctors->map(function ($doctor) {

            if ($doctor->employee) {
                return [
                    'id' => $doctor->id,
                    'name' => $doctor->employee->first_name . ' ' . $doctor->employee->last_name,
                    'department'=> $doctor->department_id
                ];
            }
            return null;
        })->filter();


        return response()->json($formattedDoctors);
    }
    public function confirmPayment(string $id)
    {
        $appointment = Appointment::find($id);
        if (!$appointment) {
            return response()->json(['message' => 'Appointment not found.'], 404);
        }

        if ($appointment->status !== 'completed') {
            return response()->json(['message' => 'Cannot confirm payment for an appointment that is not yet completed.'], 400);
        }

        if ($appointment->payment_status) {
            return response()->json(['message' => 'This appointment has already been paid.'], 400);
        }

        $appointment->payment_status = true;

        $appointment->payment_date = now();

        $appointment->save();

        return response()->json([
            'message' => 'Payment confirmed successfully.',
            'appointment_id' => $appointment->id,
            'new_payment_status' => 'Paid'
        ], 200);
    }

}
