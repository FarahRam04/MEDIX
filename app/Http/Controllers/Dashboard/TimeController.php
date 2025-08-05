<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateWorkingDetailsRequest;
use App\Http\Requests\WorkingDetailsRequest;
use App\Http\Resources\TimeResource;
use App\Models\AvailableSlot;
use App\Models\Day;
use App\Models\Doctor;
use App\Models\Employee;
use App\Models\Time;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Validation\Rule;

class TimeController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {

        return TimeResource::collection(Time::with('days','employee')->get());
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(WorkingDetailsRequest $request)
    {
        $validated = $request->validated();

        $result = $this->validateShiftAndConflict($validated);

        if (isset($result['error'])) {
            return response()->json(['error' => $result['error']], 422);
        }

        $time = Time::create([
            'employee_id' => $validated['employee_id'],
            'start_time' => $result['start'],
            'end_time' => $result['end'],
        ]);

        $time->days()->attach($result['dayIds']);

        // جلب كل الـ available_slots ضمن وقت الدوام
        $matchingSlots = AvailableSlot::where('start_time', '>=', $result['start'])
            ->where('start_time', '<', $result['end']) // لأن end هو نهاية الشفت
            ->pluck('id')
            ->toArray();
        // ربط الدكتور بالـ slots المناسبة عبر جدول Pivot (doctor_slot أو employee_slot)
        $doctor = Doctor::where('employee_id', $validated['employee_id'])->first();

        if ($doctor) {
            $doctor->availableSlots()->sync($matchingSlots);
        }
        return new TimeResource($time->load('employee', 'days'));
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $time = Time::find($id);
        if (!$time) {
            return response()->json(['error' => 'Time  not found.'], 404);
        }
        $time = Time::with('employee') // إذا كنت تريد جلب بيانات الموظف أيضًا
        ->findOrFail($id);

        return response()->json([
            'status' => true,
            'message' => 'تفاصيل وقت الدوام',
            'data' => $time,
        ]);
    }


    /**
     * Update the specified resource in storage.
     */

    public function update(UpdateWorkingDetailsRequest $request, $id)
    {
        $time = Time::find($id);
        if (!$time) {
            return response()->json(['error' => 'Time  not found.'], 404);
        }

        $validated = $request->validated();

        $result = $this->validateShiftAndConflict($validated, $time->id, $time->employee,$time);

        if (isset($result['error'])) {
            return response()->json(['error' => $result['error']], 422);
        }

        $time->update([
            'start_time' => $result['start'],
            'end_time' => $result['end'],
        ]);

        $time->days()->sync($result['dayIds']);

        return new TimeResource($time->load('employee', 'days'));
    }
    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $time = Time::with('employee', 'days')->find($id);

        if (!$time) {
            return response()->json(['error' => 'Time not found.'], 404);
        }

        $deletdTimee = new TimeResource($time);
        $time->days()->detach(); // فك الارتباط من جدول pivot (optional، بس احتياطي)

        $time->delete(); // حذف العنصر من جدول times

        return response()->json(['message' => 'Time deleted successfully.',
        'Time deleted is:'=>$deletdTimee],200);
    }

    protected function validateShiftAndConflict(
        array $validated,
        ?int $excludeTimeId = null,
        ?Employee $employee = null,
        ?Time $time = null
    ) {
        // 1. تحويل وقت البدء والانتهاء إلى صيغة 24 ساعة
        $start = isset($validated['start_time'])
            ? Carbon::createFromFormat('g:i A', $validated['start_time'])->format('H:i:s')
            : ($time ? $time->start_time : null);

        $end = isset($validated['end_time'])
            ? Carbon::createFromFormat('g:i A', $validated['end_time'])->format('H:i:s')
            : ($time ? $time->end_time : null);

        if (!$start || !$end) {
            return ['error' => 'Start and end time must be provided.'];
        }

        // 2. السماح فقط بشيفتين محددتين
        $allowedShifts = [
            ['start' => '09:00:00', 'end' => '13:00:00'],
            ['start' => '14:00:00', 'end' => '18:00:00'],
        ];
        $isValidShift = collect($allowedShifts)->contains(fn($shift) => $start === $shift['start'] && $end === $shift['end']);
        if (!$isValidShift) {
            return ['error' => 'Only two shifts allowed: 9-13 or 14-18'];
        }

        // 3. جلب الموظف
        if (!$employee && isset($validated['employee_id'])) {
            $employee = Employee::with('time.days', 'doctor')->findOrFail($validated['employee_id']);
        } elseif (!$employee) {
            return ['error' => 'Employee not found.'];
        }

        // 4. تحديد الأيام
        $dayIds = isset($validated['days'])
            ? Day::whereIn('day_name', array_map('strtolower', $validated['days']))->pluck('id')
            : ($time ? $time->days->pluck('id') : collect());

        if ($dayIds->isEmpty()) {
            return ['error' => 'No valid days found'];
        }

        // 5. التأكد أن الموظف لا يملك أكثر من شيفت في نفس اليوم
        foreach ($dayIds as $dayId) {
            $existingShifts = Time::where('employee_id', $employee->id)
                ->whereHas('days', fn($q) => $q->where('day_id', $dayId))
                ->when($excludeTimeId, fn($q) => $q->where('id', '!=', $excludeTimeId))
                ->get();

            if ($existingShifts->count()) {
                return ['error' => 'Employee already has a shift on one of the selected days'];
            }

            // 6. التأكد من التزام الموظف بشيفت واحد طوال الأسبوع
            $existingShiftsAllWeek = Time::where('employee_id', $employee->id)
                ->when($excludeTimeId, fn($q) => $q->where('id', '!=', $excludeTimeId))
                ->with('days')
                ->get();

            foreach ($existingShiftsAllWeek as $shift) {
                if (($shift->start_time !== $start || $shift->end_time !== $end)) {
                    return ['error' => 'Employee must have the same shift throughout the week'];
                }
            }
        }

        // 7. منع تكرار موظف الاستقبال أو الطبيب في نفس الشيفت ونفس القسم ونفس اليوم
        foreach ($dayIds as $dayId) {
            $conflictingQuery = Time::where('start_time', $start)
                ->where('end_time', $end)
                ->whereHas('days', fn($q) => $q->where('day_id', $dayId))
                ->whereHas('employee', fn($q) => $q->where('id', '!=', $employee->id));

            if ($employee->role === 'doctor' && $employee->doctor) {
                $departmentId = $employee->doctor->department_id;

                $conflictingQuery->whereHas('employee.doctor', fn($q) =>
                $q->where('department_id', $departmentId)
                );

                if ($conflictingQuery->exists()) {
                    return ['error' => 'Another doctor is already scheduled in this department and shift on this day'];
                }
            }

            if ($employee->role === 'receptionist') {
                $receptionistsCount = Time::whereHas('days', fn($q) => $q->where('day_id', $dayId))
                    ->where('start_time', $start)
                    ->where('end_time', $end)
                    ->whereHas('employee', fn($q) =>
                    $q->where('role', 'receptionist')
                        ->where('id', '!=', $employee->id) // هذا هو التعديل المهم
                    )
                    ->count();

                if ($receptionistsCount >= 2) {
                    return ['error' => 'Maximum number of receptionists reached for this shift and day'];
                }
            }

        }

        return [
            'start' => $start,
            'end' => $end,
            'employee' => $employee,
            'dayIds' => $dayIds,
        ];
    }

}
