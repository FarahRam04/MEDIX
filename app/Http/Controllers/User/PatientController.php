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
use App\Services\AppointmentService;
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

            // 2. إنشاء المريض إذا لم يكن موجود
            $patient = Patient::firstOrCreate([
                'user_id' => $user->id
            ]);

            $offer = null;
            $finalPrice = null;
            $doctor = null;
            $department_id = null;
            $specialization = null;

            // ========================
            // 🟢 حالة الحجز مع أوفر
            // ========================
            if ($request->input('offer_id')) {
                $offer = Offer::findOrFail($request->offer_id);

                // تحقق من تطابق الدكتور
                if ($offer->doctor_id !== (int) $request->doctor_id) {
                    throw ValidationException::withMessages([
                        'doctor_id' => __('messages.doctor_offer'),
                    ]);
                }

                // تحقق من تطابق القسم
                if ($offer->department_id !== (int) $request->department_id) {
                    throw ValidationException::withMessages([
                        'department_id' => __('messages.department_offer'),
                    ]);
                }

                $doctor = Doctor::findOrFail($offer->doctor_id);
                $department_id = $offer->department_id;
                $specialization = $doctor->department->name;

                // حساب السعر بناءً على وسيلة الدفع
                if ($offer->payment_method === 'cash') {
                    $finalPrice = $this->getTotalOfferPrice(
                        $offer->id,
                        $request->request_type_id,
                        $request->with_medical_report
                    );
                } elseif ($offer->payment_method === 'points') {
                    if ($user->points < $offer->points_required) {
                        throw ValidationException::withMessages([
                            'points' => __('messages.points'),
                        ]);
                    } else {
                        $finalPrice = 0;
                        $user->points -= $offer->points_required;
                        $user->save();
                    }
                }
            }
            // ========================
            // 🟢 حالة الحجز العادي
            // ========================
            else
            {
                $doctor = Doctor::findOrFail($request->doctor_id);

                if ($doctor->department_id !== (int) $request->department_id) {
                    throw ValidationException::withMessages([
                        'department_id' => __('messages.department_doctor'),
                    ]);
                }

                $department_id = $doctor->department_id;
                $specialization = $doctor->department->name;

                // السعر العادي
                $priceWithoutOffer = 0;
                if ($request->request_type_id === 1) { // check up
                    $priceWithoutOffer = 50000;
                } elseif ($request->request_type_id === 2) { // follow up
                    $priceWithoutOffer = 25000;
                }
                if ($request->with_medical_report) {
                    $priceWithoutOffer += 20000;
                }
                $finalPrice = $priceWithoutOffer;
            }

            // ========================
            // 🟡 التشيكات المشتركة
            // ========================

            // 1. slot مرتبط بالدكتور
            $exists = DB::table('available_slot_doctor')
                ->where('available_slot_id', $request->slot_id)
                ->where('doctor_id', $doctor->id)
                ->exists();
            if (!$exists) {
                throw ValidationException::withMessages([
                    'slot_id' =>__('messages.slot_doctor'),
                ]);
            }
            // 2. عدم وجود حجز مسبق لهذا الدكتور
            $alreadyBooked = Appointment::where('doctor_id', $doctor->id)
                ->where('slot_id', $request->slot_id)
                ->where('date', $request->date)
                ->exists();
            if ($alreadyBooked) {
                throw ValidationException::withMessages([
                    'slot_id' => __('messages.slot_booked'),
                ]);
            }

            // 3. عدم وجود تعارض عند المريض
            $patientConflict = Appointment::where('patient_id', $patient->id)
                ->where('slot_id', $request->slot_id)
                ->where('date', $request->date)
                ->exists();
            ///////////////////////////////////////////
            if ($patientConflict) {
                throw ValidationException::withMessages([
                    'slot_id' => __('messages.slot_double'),
                ]);
            }

            // 4. تأكد أن الدكتور يعمل في هذا اليوم
            $employeeId = $doctor->employee_id;
            $dayOfWeek = Carbon::parse($request->date)->dayOfWeek; // 0 = الأحد ... 6 = السبت
            $doctorWorksThatDay = DB::table('times')
                ->join('day_time', 'times.id', '=', 'day_time.time_id')
                ->where('times.employee_id', $employeeId)
                ->where('day_time.day_id', $dayOfWeek)
                ->exists();
            if (!$doctorWorksThatDay) {
                throw ValidationException::withMessages([
                    'date' => __('messages.date_doctor'),
                ]);
            }

            // 5. تحقق من شرط المراجعة (follow_up)
            if ($request->request_type_id === 2) {
                $visitedRecently = Appointment::where('doctor_id', $doctor->id)
                    ->where('patient_id', $patient->id)
                    ->where('type', 'check_up')
                    ->whereDate('date', '>=', Carbon::parse($request->date)->subDays(15))
                    ->whereDate('date', '<', $request->date)
                    ->exists();

                if (!$visitedRecently) {
                    throw ValidationException::withMessages([
                      'type'=>__('messages.type')  ,
                    ]);
                }
            }

            // 6. قفل الـ slot
            $slot = AvailableSlot::lockForUpdate()->findOrFail($request->slot_id);//lock the row of this slot until the transaction function ends to avoid race condition
            $appointmentDateTime = Carbon::parse($request->date . ' ' . $slot->start_time);

            if ($appointmentDateTime->isPast()) {
                throw ValidationException::withMessages(['slot_id' => __('messages.past_slot')]);
            }
            // ========================
            // 🟢 إنشاء الموعد
            // ========================

            $appointment = Appointment::create([
                'doctor_id'           => $doctor->id,
                'patient_id'          => $patient->id,
                'department_id'       => $department_id,
                'offer_id'            => $offer->id ?? null,
                'date'                => $request->date,
                'slot_id'             => $request->slot_id,
                'type'                => $request->request_type_id === 1 ? 'check_up' : 'follow_up',
                'with_medical_report' => $request->with_medical_report ?? false,
                'specialization'      => $specialization,
                'init_total_price'    => $finalPrice,
                'final_total_price'   => $finalPrice,
                'status'              =>[
                'en' => "pending",
                'ar' => "قيد الانتظار",
            ]
            ]);


        });

        return response()->json(['message' =>__('messages.booked')]);
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
    public function update(BookAppointmentRequest $request, $id, AppointmentService $service)
    {
        DB::transaction(function () use ($request, $id, $service) {
            $appointment = Appointment::findOrFail($id);

            // التحقق من إمكانية التعديل
            if (!$service->canBeCancelledAndEdited($appointment)) {
                throw ValidationException::withMessages([
                    'unauthorized' => __('messages.unauthorized_time'),
                ]);
            }

            $user = auth()->user();
            $patient = $appointment->patient;

            // التأكد أن المستخدم هو صاحب الموعد
            if ($user->id !== $patient->user_id) {
                throw ValidationException::withMessages([
                    'unauthorized' => __('messages.unauthorized_user'),
                ]);
            }

            // التأكد أن الموعد لم يمر بعد
            if (Carbon::parse($appointment->date)->isPast()) {
                throw ValidationException::withMessages([
                    'date' => __('messages.past_appointment'),
                ]);
            }

            $offer = null;
            $finalPrice = null;
            $doctor = null;
            $department_id = null;
            $specialization = null;

            // ========================
            // 🟢 حالة التعديل مع أوفر
            // ========================
            if ($request->input('offer_id')) {
                $offer = Offer::findOrFail($request->offer_id);

                // تحقق من تطابق الدكتور
                if ($offer->doctor_id !== (int) $request->doctor_id) {
                    throw ValidationException::withMessages([
                        'doctor_id' => __('messages.doctor_offer'),
                    ]);
                }

                // تحقق من تطابق القسم
                if ($offer->department_id !== (int) $request->department_id) {
                    throw ValidationException::withMessages([
                        'department_id' => __('messages.department_offer'),
                    ]);
                }

                $doctor = Doctor::findOrFail($offer->doctor_id);
                $department_id = $offer->department_id;
                $specialization = $doctor->department->name;

                // حساب السعر بناءً على وسيلة الدفع
                if ($offer->payment_method === 'cash') {
                    $finalPrice = $this->getTotalOfferPrice(
                        $offer->id,
                        $request->request_type_id,
                        $request->with_medical_report
                    );
                } elseif ($offer->payment_method === 'points') {
                    if ($user->points < $offer->points_required) {
                        throw ValidationException::withMessages([
                            'points' => __('messages.points'),
                        ]);
                    } else {
                        $finalPrice = 0;
                        $user->points -= $offer->points_required;
                        $user->save();
                    }
                }
            }
            // ========================
            // 🟢 حالة التعديل العادي
            // ========================
            else {
                $doctor = Doctor::findOrFail($request->doctor_id);

                if ($doctor->department_id !== (int) $request->department_id) {
                    throw ValidationException::withMessages([
                        'department_id' => __('messages.department_doctor'),
                    ]);
                }

                $department_id = $doctor->department_id;
                $specialization = $doctor->department->name;

                // السعر العادي
                $priceWithoutOffer = 0;
                if ($request->request_type_id === 1) { // check up
                    $priceWithoutOffer = 50000;
                } elseif ($request->request_type_id === 2) { // follow up
                    $priceWithoutOffer = 25000;
                }
                if ($request->with_medical_report) {
                    $priceWithoutOffer += 20000;
                }
                $finalPrice = $priceWithoutOffer;
            }

            // ========================
            // 🟡 التشيكات المشتركة
            // ========================
            // التأكد أن الـ slot فعلاً مربوط بهذا الدكتور
            $exists = DB::table('available_slot_doctor')
                ->where('available_slot_id', $request->slot_id)
                ->where('doctor_id', $doctor->id)
                ->exists();
            if (!$exists) {
                throw ValidationException::withMessages([
                    'slot_id' => __('messages.slot_doctor'),
                ]);
            }

            // عدم وجود موعد آخر لنفس الدكتور (باستثناء الموعد الحالي)
            $alreadyBooked = Appointment::where('doctor_id', $doctor->id)
                ->where('slot_id', $request->slot_id)
                ->where('date', $request->date)
                ->where('id', '!=', $appointment->id)
                ->exists();
            if ($alreadyBooked) {
                throw ValidationException::withMessages([
                    'slot_id' => __('messages.slot_booked'),
                ]);
            }

            // عدم وجود تعارض عند المريض (باستثناء الموعد الحالي)
            $patientConflict = Appointment::where('patient_id', $patient->id)
                ->where('slot_id', $request->slot_id)
                ->where('date', $request->date)
                ->where('id', '!=', $appointment->id)
                ->exists();
            if ($patientConflict) {
                throw ValidationException::withMessages([
                    'slot_id' => __('messages.slot_double'),
                ]);
            }

            // تأكد أن الدكتور يعمل في هذا اليوم
            $employeeId = $doctor->employee_id;
            $dayOfWeek = Carbon::parse($request->date)->dayOfWeek;
            $doctorWorksThatDay = DB::table('times')
                ->join('day_time', 'times.id', '=', 'day_time.time_id')
                ->where('times.employee_id', $employeeId)
                ->where('day_time.day_id', $dayOfWeek)
                ->exists();
            if (!$doctorWorksThatDay) {
                throw ValidationException::withMessages([
                    'date' => __('messages.date_doctor'),
                ]);
            }

            // تحقق من شرط المراجعة
            if ($request->request_type_id === 2) {
                $visitedRecently = Appointment::where('doctor_id', $doctor->id)
                    ->where('patient_id', $patient->id)
                    ->where('type', 'check_up')
                    ->whereDate('date', '>=', Carbon::parse($request->date)->subDays(15))
                    ->whereDate('date', '<', $request->date)
                    ->exists();

                if (!$visitedRecently) {
                    throw ValidationException::withMessages([
                        'type' => __('messages.type'),
                    ]);
                }
            }

            // قفل الـ slot
            $slot = AvailableSlot::lockForUpdate()->findOrFail($request->slot_id);
            $appointmentDateTime = Carbon::parse($request->date . ' ' . $slot->start_time);

            if ($appointmentDateTime->isPast()) {
                throw ValidationException::withMessages(['slot_id' => __('messages.past_slot')]);
            }

            // ========================
            // 🟢 تحديث بيانات الموعد
            // ========================
            $appointment->update([
                'doctor_id'           => $doctor->id,
                'department_id'       => $department_id,
                'offer_id'            => $offer->id ?? null,
                'date'                => $request->date,
                'slot_id'             => $request->slot_id,
                'type'                => $request->request_type_id === 1 ? 'check_up' : 'follow_up',
                'with_medical_report' => $request->with_medical_report ?? false,
                'specialization'      => $specialization,
                'init_total_price'    => $finalPrice,
                'final_total_price'   => $finalPrice,
            ]);
        });

        return response()->json(['message' => __('messages.updated')]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }


}
