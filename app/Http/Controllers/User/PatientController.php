<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Http\Requests\BookAppointmentRequest;
use App\Models\Appointment;
use App\Models\AvailableSlot;
use App\Models\Patient;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Carbon\Carbon;

class PatientController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(BookAppointmentRequest $request)
    {
        DB::transaction(function () use ($request) {
            // 1. تحديث المستخدم
            $user = User::findOrFail($request->user_id);
            $user->is_patient = true;
            $user->save();

            // 2. إنشاء مريض إذا لم يكن موجود
            $patient = Patient::firstOrCreate([
                'user_id' => $request->user_id
            ]);

            // 3. التحقق من العلاقة بين الدكتور والـ slot
            $exists = DB::table('available_slot_doctor')
                ->where('available_slot_id', $request->slot_id)
                ->where('doctor_id', $request->doctor_id)
                ->exists();

            if (!$exists) {
                throw ValidationException::withMessages([
                    'slot_id' => 'هذا الموعد لا يتبع الدكتور المحدد.',
                ]);
            }

            // 4. التأكد أن الموعد غير محجوز مسبقًا لهذا الدكتور
            $alreadyBooked = Appointment::where('doctor_id', $request->doctor_id)
                ->where('slot_id', $request->slot_id)
                ->where('date', $request->date)
                ->exists();

            if ($alreadyBooked) {
                throw ValidationException::withMessages([
                    'slot_id' => 'هذا الموعد محجوز مسبقًا لهذا الدكتور في هذا التاريخ.',
                ]);
            }

            // 5. التأكد أن المريض لا يملك موعدًا آخر بنفس التاريخ والـ slot
            $patientConflict = Appointment::where('patient_id', $patient->id)
                ->where('slot_id', $request->slot_id)
                ->where('date', $request->date)
                ->exists();

            if ($patientConflict) {
                throw ValidationException::withMessages([
                    'slot_id' => 'لديك موعد آخر في نفس الوقت.',
                ]);
            }

            //this situation won`t be done. because in the application the doctor will appear only in his working days
            // ✅ 6. التأكد من أن اليوم الموافق للتاريخ موجود ضمن أيام دوام الدكتور
            $dayOfWeek = Carbon::parse($request->date)->dayOfWeek; // 0=sun ... 6=sat

            $doctorWorksThatDay = DB::table('times')
                ->join('day_time', 'times.id', '=', 'day_time.time_id')
                ->where('times.employee_id', $request->doctor_id)
                ->where('day_time.day_id', $dayOfWeek)
                ->exists();

            if (!$doctorWorksThatDay) {
                throw ValidationException::withMessages([
                    'date' => 'الدكتور لا يعمل في هذا اليوم.',
                ]);
            }

            // 7. قفل السجل لضمان الحجز
            $slot = AvailableSlot::lockForUpdate()->findOrFail($request->slot_id);

            // 8. إنشاء الموعد
            Appointment::create([
                'patient_id' => $patient->id,
                'doctor_id' => $request->doctor_id,
                'slot_id' => $slot->id,
                'date' => $request->date,
                'type' => $request->type,
                'specialization' => $request->specialization,
                'status' => $request->status ?? 'pending',
                'check_up_price' => $request->check_up_price,
                'lab_tests' => $request->lab_tests ?? false,
                'total_price' => $request->total_price,
                'payment_status' => $request->payment_status,
            ]);
        });

        return response()->json(['message' => 'تم حجز الموعد بنجاح.']);
    }



    public function getDoctorSchedule($doctorId)
    {
        $today = Carbon::today();
        $result = [];

        for ($i = 0; $i < 5; $i++) {
            $date = $today->copy()->addDays($i);
            $carbonDayOfWeek = $date->dayOfWeek; // Carbon: الأحد = 0

            // ✅ جلب time_ids الخاصة بالدكتور المرتبطة بهذا اليوم
            $timeIdsForDay = DB::table('times')
                ->join('day_time', 'times.id', '=', 'day_time.time_id')
                ->where('times.employee_id', $doctorId)
                ->where('day_time.day_id', $carbonDayOfWeek)
                ->pluck('times.id')
                ->toArray();

            if (empty($timeIdsForDay)) {
                $result[] = [
                    'date' => $date->toDateString(),
                    'day_name' => $date->locale('en')->dayName,
                    'slots' => [],
                ];
                continue;
            }

            // ✅ جلب جميع الـ slots المرتبطة بالدكتور من جدول available_slot_doctor
            $slots = DB::table('available_slot_doctor')
                ->join('available_slots', 'available_slots.id', '=', 'available_slot_doctor.available_slot_id')
                ->where('available_slot_doctor.doctor_id', $doctorId)
                ->select('available_slots.id as slot_id', 'available_slots.start_time')
                ->get();

            // ✅ جلب المواعيد المحجوزة لهذا الدكتور في هذا اليوم
            $bookedSlotIds = DB::table('appointments')
                ->where('doctor_id', $doctorId)
                ->whereDate('date', $date)
                ->pluck('slot_id')
                ->toArray();

            // ✅ تحديد كل slot إذا كان محجوز أم لا
            $slotsWithAvailability = $slots->map(function ($slot) use ($bookedSlotIds) {
                return [
                    'slot_id' => $slot->slot_id,
                    'start_time' => $slot->start_time,
                    'available' => !in_array($slot->slot_id, $bookedSlotIds),
                ];
            });

            $result[] = [
                'date' => $date->toDateString(),
                'day_name' => $date->locale('en')->dayName,
                'slots' => $slotsWithAvailability,
            ];
        }

        return response()->json($result);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
