<?php

namespace App\Http\Controllers\User;

use App\HelperFunctions;
use App\Http\Controllers\Controller;
use App\Http\Requests\BookAppointmentRequest;
use App\Models\Appointment;
use App\Models\AvailableSlot;
use App\Models\Doctor;
use App\Models\Offer;
use App\Models\Patient;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Carbon\Carbon;

class PatientController extends Controller
{
    use HelperFunctions;
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
            // 1. جلب المستخدم من التوكن وتحديث حالته كمريض
            $user = auth()->user();
            $user->is_patient = true;
            $user->save();

            // 2. إنشاء مريض إذا لم يكن موجود
            $patient = Patient::firstOrCreate([
                'user_id' => $user->id
            ]);

            // 3. جلب الدكتور والتخصص
            $doctor = Doctor::findOrFail($request->doctor_id);
            if ($doctor->department_id !== (int) $request->department_id) {
                throw ValidationException::withMessages([
                    'department_id' => 'this doctor does not work in that department',
                ]);
            }
            $department_id = $doctor->department_id;
            $specialization = $doctor->department->name;

            // 4. التأكد أن الـ slot فعلاً مربوط بهذا الدكتور
            $exists = DB::table('available_slot_doctor')
                ->where('available_slot_id', $request->slot_id)
                ->where('doctor_id', $request->doctor_id)
                ->exists();
            if (!$exists) {
                throw ValidationException::withMessages([
                    'slot_id' => 'this time does not belong to this doctor',
                ]);
            }

            // 5. التأكد من أن الموعد غير محجوز مسبقًا لهذا الدكتور في نفس التاريخ والوقت
            $alreadyBooked = Appointment::where('doctor_id', $request->doctor_id)
                ->where('slot_id', $request->slot_id)
                ->where('date', $request->date)
                ->exists();
            if ($alreadyBooked) {
                throw ValidationException::withMessages([
                    'slot_id' => 'this time slot is already booked for this doctor',
                ]);
            }

            // 6. التأكد أن المريض لا يملك موعدًا آخر في نفس التاريخ والوقت
            $patientConflict = Appointment::where('patient_id', $patient->id)
                ->where('slot_id', $request->slot_id)
                ->where('date', $request->date)
                ->exists();
            if ($patientConflict) {
                throw ValidationException::withMessages([
                    'slot_id' => 'Book denied: you already have another appointment at this time.',
                ]);
            }

            // 7. التأكد من أن الدكتور يعمل في هذا اليوم
            $employeeId = DB::table('doctors')
                ->where('id', $request->doctor_id)
                ->value('employee_id');

            $dayOfWeek = Carbon::parse($request->date)->dayOfWeek; // 0 = الأحد ... 6 = السبت
            $doctorWorksThatDay = DB::table('times')
                ->join('day_time', 'times.id', '=', 'day_time.time_id')
                ->where('times.employee_id', $employeeId)
                ->where('day_time.day_id', $dayOfWeek)
                ->exists();
            if (!$doctorWorksThatDay) {
                throw ValidationException::withMessages([
                    'date' => 'The doctor does not work in this day',
                ]);
            }

            // 7.5 تحقق من شرط المراجعة (follow_up)
            if ($request->request_type_id === 2) {
                $visitedRecently = Appointment::where('doctor_id', $request->doctor_id)
                    ->where('patient_id', $patient->id)
                    ->where('type', 'check_up') // فقط المواعيد من نوع معاينة
                    ->whereDate('date', '>=', Carbon::parse($request->date)->subDays(15))
                    ->whereDate('date', '<', $request->date)
                    ->exists();

                if (!$visitedRecently) {
                    throw ValidationException::withMessages([
                        'type' => 'You can only book a follow-up if you have visited this doctor within the last 15 days.',
                    ]);
                }
            }

            // 8. قفل الـ slot للحجز الآمن
            $slot = AvailableSlot::lockForUpdate()->findOrFail($request->slot_id);


            if ($request->input('offer_id')){
                $offer = Offer::findOrFail($request->offer_id);
                if ($offer->payment_method === 'cash') {
                    $finalPrice=$this->getTotalOfferPrice($offer->id,$request->request_type_id,$request->with_medical_report);
                }
                elseif ($offer->payment_method === 'points') {
                    if ($user->points < $offer->points_required){
                        throw ValidationException::withMessages([
                            'points' => 'Sorry, you do not have enough points to book this appointment.',
                        ]);
                    } else {
                        $finalPrice = 0;
                        $user->points -= $offer->points_required;
                        $user->save();
                    }
                }
            }
            $priceWithoutOffer=0;
            if ($request->request_type_id === 1){
                $priceWithoutOffer=50000;
            }elseif ($request->request_type_id === 2){
                $priceWithoutOffer=25000;
            }
            if ($request->with_medical_report){
                $priceWithoutOffer+=20000;
            }
            // 9. إنشاء الموعد
            $appointment = Appointment::create([
                'doctor_id'           => $request->doctor_id,
                'patient_id'          => $patient->id,
                'department_id'       => $department_id,
                'date'                => $request->date,
                'slot_id'             => $request->slot_id,
                'type'                => $request->request_type_id ===1 ?'check_up' : 'follow_up',
                'with_medical_report' => $request->with_medical_report ?? false,
                'specialization'      => $specialization,
                'total_price'         => $finalPrice ?? $priceWithoutOffer,
            ]);
        });

        return response()->json(['message' => 'Appointment booked successfully']);
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
    public function update(BookAppointmentRequest $request, $id)
    {
        DB::transaction(function () use ($request, $id) {
            $appointment = Appointment::findOrFail($id);

            $user = auth()->user();
            $patient = $appointment->patient;

            // التأكد أن المستخدم هو صاحب الموعد
            if ($user->id !== $patient->user_id) {
                throw ValidationException::withMessages([
                    'unauthorized' => 'You are not authorized to update this appointment.',
                ]);
            }

            // التأكد أن الموعد لم يمر بعد
            if (Carbon::parse($appointment->date)->isPast()) {
                throw ValidationException::withMessages([
                    'date' => 'You cannot update a past appointment.',
                ]);
            }

            $doctor = Doctor::findOrFail($request->doctor_id);
            if ($doctor->department_id !== (int) $request->department_id) {
                throw ValidationException::withMessages([
                    'department_id' => 'this doctor does not work in that department',
                ]);
            }

            $department_id = $doctor->department_id;
            $specialization = $doctor->department->name;

            // التأكد أن الـ slot فعلاً مربوط بهذا الدكتور
            $exists = DB::table('available_slot_doctor')
                ->where('available_slot_id', $request->slot_id)
                ->where('doctor_id', $request->doctor_id)
                ->exists();
            if (!$exists) {
                throw ValidationException::withMessages([
                    'slot_id' => 'this time does not belong to this doctor',
                ]);
            }

            // التأكد من عدم وجود موعد آخر لنفس الدكتور في هذا التاريخ والوقت (باستثناء الموعد الحالي)
            $alreadyBooked = Appointment::where('doctor_id', $request->doctor_id)
                ->where('slot_id', $request->slot_id)
                ->where('date', $request->date)
                ->where('id', '!=', $appointment->id)
                ->exists();
            if ($alreadyBooked) {
                throw ValidationException::withMessages([
                    'slot_id' => 'this time slot is already booked for this doctor',
                ]);
            }

            // التأكد من أن المريض لا يملك موعدًا آخر في نفس التاريخ والوقت (باستثناء الموعد الحالي)
            $patientConflict = Appointment::where('patient_id', $patient->id)
                ->where('slot_id', $request->slot_id)
                ->where('date', $request->date)
                ->where('id', '!=', $appointment->id)
                ->exists();
            if ($patientConflict) {
                throw ValidationException::withMessages([
                    'slot_id' => 'Book denied: you already have another appointment at this time.',
                ]);
            }

            // التأكد أن الدكتور يعمل في هذا اليوم
            $employeeId = DB::table('doctors')
                ->where('id', $request->doctor_id)
                ->value('employee_id');
            $dayOfWeek = Carbon::parse($request->date)->dayOfWeek;
            $doctorWorksThatDay = DB::table('times')
                ->join('day_time', 'times.id', '=', 'day_time.time_id')
                ->where('times.employee_id', $employeeId)
                ->where('day_time.day_id', $dayOfWeek)
                ->exists();
            if (!$doctorWorksThatDay) {
                throw ValidationException::withMessages([
                    'date' => 'The doctor does not work in this day',
                ]);
            }

            // تحقق من شرط المراجعة
            if ($request->request_type_id === 2) {
                $visitedRecently = Appointment::where('doctor_id', $request->doctor_id)
                    ->where('patient_id', $patient->id)
                    ->where('type', 'check_up')
                    ->whereDate('date', '>=', Carbon::parse($request->date)->subDays(15))
                    ->whereDate('date', '<', $request->date)
                    ->exists();

                if (!$visitedRecently) {
                    throw ValidationException::withMessages([
                        'type' => 'You can only book a follow-up if you have visited this doctor within the last 15 days.',
                    ]);
                }
            }

            // قفل الـ slot للحجز الآمن
            $slot = AvailableSlot::lockForUpdate()->findOrFail($request->slot_id);
            // تحديث بيانات الموعد
            $appointment->update([
                'doctor_id'           => $request->doctor_id,
                'department_id'       => $department_id,
                'date'                => $request->date,
                'slot_id'             => $request->slot_id,
                'type'                => $request->request_type_id === 1?'check_up':'follow_up',
                'with_medical_report' => $request->with_medical_report ?? false,
                'specialization'      => $specialization
            ]);
        });

        return response()->json(['message' => 'Appointment updated successfully']);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }


}
