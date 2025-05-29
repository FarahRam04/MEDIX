<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateWorkingDetailsRequest;
use App\Http\Requests\WorkingDetailsRequest;
use App\Http\Resources\TimeResource;
use App\Models\Day;
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

        return new TimeResource($time->load('employee', 'days'));
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

    public function update(UpdateWorkingDetailsRequest $request, Time $time)
    {
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
        $time = Time::findOrFail($id); // البحث عن العنصر أو إرجاع 404 تلقائياً

        $time->days()->detach(); // فك الارتباط من جدول pivot (optional، بس احتياطي)

        $time->delete(); // حذف العنصر من جدول times

        return response()->json(['message' => 'Time deleted successfully.']);
    }

    protected function validateShiftAndConflict(
        array $validated,
        ?int $excludeTimeId = null,
        ?Employee $employee = null,
        ?Time $time = null
    ) {
        // 1. تحديد وقت البداية والنهاية
        $start = isset($validated['start_time'])
            ? Carbon::createFromFormat('g:i A', $validated['start_time'])->format('H:i:s')
            : ($time ? $time->start_time : null);

        $end = isset($validated['end_time'])
            ? Carbon::createFromFormat('g:i A', $validated['end_time'])->format('H:i:s')
            : ($time ? $time->end_time : null);

        if (!$start || !$end) {
            return ['error' => 'Start and end time must be provided.'];
        }

        // 2. التحقق من الشيفت
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
            $employee = Employee::findOrFail($validated['employee_id']);
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

        // 5. البحث عن تعارض
        foreach ($dayIds as $dayId) {
            $conflictingQuery = Time::whereHas('days', fn($q) => $q->where('day_id', $dayId))
                ->where('start_time', $start)
                ->where('end_time', $end);

            if ($excludeTimeId) {
                $conflictingQuery->where('id', '!=', $excludeTimeId);
            }

            if ($employee->role === 'doctor') {
                $conflictingQuery->whereHas('employee.doctor', fn($q) => $q->where('department_id', $employee->doctor->department_id));
            } else {
                $conflictingQuery->whereHas('employee', fn($q) => $q->where('role', 'receptionist'));
            }

            if ($conflictingQuery->exists()) {
                return ['error' => 'Schedule conflict for this day or shift'];
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
