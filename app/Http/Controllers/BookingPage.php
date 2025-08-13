<?php

namespace App\Http\Controllers;

use App\Models\AvailableSlot;
use App\Models\Department;
use App\Models\Offer;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class BookingPage extends Controller
{
    public function departments(){
        $departments = Department::all();
        foreach($departments as $department){
            if ($department->id === 1){
                continue;
            }
            $morningDoctors=0;
            $afternoonDoctors=0;
            $doctors=$department->doctors;
            foreach($doctors as $doctor){
               $time=$doctor->employee->time;
               $time->start_time ==='09:00:00' ? $morningDoctors++ : $afternoonDoctors++ ;
            }
            $data[]=[
                'id'=>$department->id,
                'name'=>$department->name,
                'morning_Doctors_Count'=>$morningDoctors,
                'afternoon_Doctors_Count'=>$afternoonDoctors,
            ];
        }
        return response()->json($data);
    }

    public function getNextFiveDays()
    {
        $days = [];
        $today = Carbon::now('Asia/Damascus')->startOfDay();

        for ($i = 0; $i < 5; $i++) {
            $days[] = [
                'id' => $i + 1,
                'day' => $today->copy()->addDays($i)->toDateString(),
                'isAvailable' => false,
            ];
        }

        return response()->json($days);
    }

    public function getSlotsByRange(Request $request)
    {
        $startId = $request->query('start_id');
        $endId = $request->query('end_id');

        if (!$startId || !$endId) {
            return response()->json(['error' => 'start_id and end_id are required'], 400);
        }

        $slots = AvailableSlot::whereBetween('id', [$startId, $endId])->get();

        $result = $slots->map(function ($slot) {
            return [
                'id' => $slot->id,
                'time' => $slot->start_time, // لأنه عندك فقط start_time بدون تاريخ
                'isAvailable' => false
            ];
        });

        return response()->json($result);
    }
    public function offerDays($offerId)
    {
        $offer = Offer::find($offerId);

        if (!$offer) {
            return response()->json(['error' => 'Offer not found'], 404);
        }

        $start = Carbon::parse($offer->start_date)->startOfDay();
        $end = Carbon::parse($offer->end_date)->startOfDay();

        // استدعاء التابع السابق
        $response = $this->getNextFiveDays();
        $days=$response->getData(true);
        // تعديل isAvailable إذا ضمن المدى
        foreach ($days as &$day) {//& للتعديل على المصفوفة الاصلية
            $date = Carbon::parse($day['day'])->startOfDay();
            $day['isAvailable'] = $date->between($start, $end);
        }

        return response()->json($days);
    }

    public function getDepartmentAvailability($department_id)
    {
        // استدعاء التابع الأصلي بدون تعديل
        $response = $this->getNextFiveDays();
        $daysData = $response->getData(true); // نحول JsonResponse إلى Array

        foreach ($daysData as &$day) {
            $date = Carbon::parse($day['day']);
            $dayId = $date->dayOfWeek;

            $day['isAvailable'] = DB::table('doctors')
                ->join('employees', 'employees.id', '=', 'doctors.employee_id')
                ->join('times', 'times.employee_id', '=', 'employees.id')
                ->join('day_time', 'day_time.time_id', '=', 'times.id')
                ->where('employees.role', 'doctor')
                ->where('doctors.department_id', $department_id)
                ->where('day_time.day_id', $dayId)
                ->exists();
        }

        return response()->json($daysData);
    }

    public function getShiftSlotsWithDoctor(Request $request)
    {
        $request->validate([
            'department_id' => 'required|exists:departments,id',
            'date' => 'required|date',
            'shift' => 'required|in:morning,afternoon',
        ]);

        $departmentId = $request->department_id;
        $date = Carbon::parse($request->date);
        $dayId = $date->dayOfWeek; // 0 = الأحد، 6 = السبت

        $slotRange = $request->shift === 'morning'
            ? range(1, 8)
            : range(9, 16);

        $shiftStart = $request->shift === 'morning' ? '09:00:00' : '14:00:00';
        $shiftEnd = $request->shift === 'morning' ? '13:00:00' : '18:00:00';

        // جلب الدكتور الذي يعمل في هذا القسم وبهذا اليوم وضمن الشيفت
        $doctor = DB::table('doctors')
            ->join('employees', 'employees.id', '=', 'doctors.employee_id')
            ->join('times', 'times.employee_id', '=', 'employees.id')
            ->join('day_time', 'day_time.time_id', '=', 'times.id')
            ->where('employees.role', 'doctor')
            ->where('doctors.department_id', $departmentId)
            ->where('day_time.day_id', $dayId)
            ->where('times.start_time', $shiftStart)
            ->where('times.end_time', $shiftEnd)
            ->select('doctors.id as doctor_id')
            ->first();

        if (!$doctor) {
            return response()->json([
                'doctor_id' => null,
                'slots' => [],
            ]);
        }

        // جلب الـ slots المرتبطة بهذا الدكتور
        $doctorSlots = DB::table('available_slot_doctor')
            ->where('doctor_id', $doctor->doctor_id)
            ->whereIn('available_slot_id', $slotRange)
            ->pluck('available_slot_id')
            ->toArray();

        // جلب معلومات الـ slots
        $slots = DB::table('available_slots')
            ->whereIn('id', $slotRange)
            ->orderBy('id')
            ->get()
            ->map(function ($slot) use ($doctorSlots, $doctor, $date) {
                $isDoctorAvailable = in_array($slot->id, $doctorSlots);

                $isAlreadyBooked = DB::table('appointments')
                    ->where('doctor_id', $doctor->doctor_id)
                    ->where('slot_id', $slot->id)
                    ->where('date', $date->toDateString())
                    ->exists();

                return [
                    'id' => $slot->id,
                    'time' => $slot->start_time,
                    'isAvailable' => $isDoctorAvailable && !$isAlreadyBooked,
                ];
            });

        return response()->json([
            'doctor_id' => $doctor->doctor_id,
            'slots' => $slots,
        ]);
    }
}
