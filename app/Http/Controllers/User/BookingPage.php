<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\AvailableSlot;
use App\Models\Department;
use App\Models\Doctor;
use App\Models\Offer;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class BookingPage extends Controller
{

    public function departments(Request $request)
    {
        $locale = app()->getLocale();

        if ($request->query('keyword'))
        {
            $request->validate([
                'keyword' => 'required|string'
            ]);

            $keyword = $request->input('keyword');

            $departments = Department::where("name->{$locale}", 'LIKE', "%{$keyword}%")->get();
        } else {
            $departments = Department::all();
        }

        $data = [];
        foreach ($departments as $department) {

            $morningDoctors = 0;
            $afternoonDoctors = 0;

            foreach ($department->doctors as $doctor) {
                $time = $doctor->employee->time;
                $time->start_time === '09:00:00'
                    ? $morningDoctors++
                    : $afternoonDoctors++;
            }

            $data[] = [
                'id' => $department->id,
                'name' => $department->getTranslation('name', $locale), // بترجع الترجمة حسب اللغة
                'morning_Doctors_Count' => $morningDoctors,
                'afternoon_Doctors_Count' => $afternoonDoctors,
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
                'time' => $slot->start_time,
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

        $response = $this->getNextFiveDays();
        $days = $response->getData(true);

        $doctorDaysIds = $offer->doctor->employee->time->days->pluck('id')->toArray();
        foreach ($days as &$day) {
            $date = Carbon::parse($day['day'])->startOfDay();

            if ($date->between($start, $end) && in_array($date->dayOfWeek, $doctorDaysIds)) {
                $day['isAvailable'] = true;
            }
        }

        return response()->json($days);
    }

    public function getDepartmentAvailability($department_id)
    {

        $response = $this->getNextFiveDays();
        $daysData = $response->getData(true);

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
                ->whereNotExists(function ($query) use ($date) {
                    $query->select(DB::raw(1))
                        ->from('vacations')
                        ->whereColumn('vacations.employee_id', 'employees.id')
                        ->where('vacations.status', 'active')
                        ->where('vacations.start_day', '<=', $date->toDateString())
                        ->where('vacations.end_day', '>', $date->toDateString());
                })
                ->exists();
        }

        return response()->json($daysData);
    }

    public function getDaysRelatedToDoctor($doctor_id)
    {
        $doctor = Doctor::find($doctor_id);
        if (!$doctor) {
            return response()->json(['error' => 'Doctor not found'], 404);
        }
        $response = $this->getNextFiveDays();
        $daysData = $response->getData(true);
        foreach ($daysData as &$day) {
            $date = Carbon::parse($day['day']);
            $dayId = $date->dayOfWeek;
            $doctorDays = $doctor->employee->time->days->pluck('id')->toArray();
            $hasVacation = $doctor->employee->vacations()
                ->where('status', 'active')
                ->where('start_day', '<=', $date->toDateString())
                ->where('end_day', '>', $date->toDateString())
                ->exists();
            if (in_array($dayId, $doctorDays) && !$hasVacation) {
                $day['isAvailable'] = true;
            }
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
        $dayId = $date->dayOfWeek;

        $slotRange = $request->shift === 'morning'
            ? range(1, 8)
            : range(9, 16);

        $shiftStart = $request->shift === 'morning' ? '09:00:00' : '14:00:00';
        $shiftEnd = $request->shift === 'morning' ? '13:00:00' : '18:00:00';

        // جلب الدكتور باستخدام موديل Eloquent مع العلاقات
        $doctor = Doctor::with(['employee.vacations'])
            ->where('department_id', $departmentId)
            ->whereHas('employee.time.days', function ($query) use ($dayId, $shiftStart, $shiftEnd) {
                $query->where('day_id', $dayId)
                    ->where('times.start_time', $shiftStart)
                    ->where('times.end_time', $shiftEnd);
            })
            ->first();

        if (!$doctor) {
            return response()->json([
                'doctor_id' => null,
                'slots' => [],
            ]);
        }

        // جلب الـ slots المرتبطة بهذا الدكتور
        $doctorSlots = $doctor->availableSlots
            ->whereIn('id', $slotRange)
            ->pluck('id')
            ->toArray();

        // جلب معلومات الـ slots
        $slots = AvailableSlot::whereIn('id', $slotRange)
            ->orderBy('id')
            ->get()
            ->map(function ($slot) use ($doctor, $doctorSlots, $date) {

                $hasVacation = $doctor->employee->vacations()
                    ->where('status', 'active')
                    ->where('start_day', '<=', $date->toDateString())
                    ->where('end_day', '>', $date->toDateString())
                    ->exists();

                $isDoctorAvailable = in_array($slot->id, $doctorSlots) && !$hasVacation;

                $isAlreadyBooked = $doctor->appointments()
                    ->where('slot_id', $slot->id)
                    ->where('date', $date->toDateString())
                    ->exists();

                $appointmentDateTime = Carbon::parse($date->toDateString() . ' ' . $slot->start_time);
                $isInThePast = $appointmentDateTime->isPast();

                return [
                    'id' => $slot->id,
                    'time' => $slot->start_time,
                    'isAvailable' => $isDoctorAvailable && !$isAlreadyBooked && !$isInThePast,
                ];
            });

        return response()->json([
            'doctor_id' => $doctor->id,
            'slots' => $slots,
        ]);
    }



}
