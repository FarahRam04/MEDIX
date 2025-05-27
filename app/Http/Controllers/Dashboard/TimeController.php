<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
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

        $start = Carbon::createFromFormat('g:i A', $validated['start_time'])->format('H:i:s');
        $end = Carbon::createFromFormat('g:i A', $validated['end_time'])->format('H:i:s');

        // الشيفتات المسموحة فقط
        $allowedShifts = [
            ['start' => '09:00:00', 'end' => '13:00:00'],
            ['start' => '14:00:00', 'end' => '18:00:00'],
        ];

        $isValidShift = collect($allowedShifts)->contains(function ($shift) use ($start, $end) {
            return $start === $shift['start'] && $end === $shift['end'];
        });

        if (!$isValidShift) {
            return response()->json(['error' => 'Only two shifts allowed: 9-13 or 14-18'], 422);
        }

        // جلب الموظف والتحقق من نوعه
        $employee = Employee::findOrFail($validated['employee_id']);

        $dayIds = Day::whereIn('day_name', array_map('strtolower', $validated['days']))->pluck('id');

        if ($dayIds->isEmpty()) {
            return response()->json(['error' => 'No valid days found'], 422);
        }

        foreach ($dayIds as $dayId) {
            $conflictingQuery = Time::whereHas('days', fn($q) => $q->where('day_id', $dayId))
                ->where(function ($q) use ($start, $end) {
                    $q->where(function ($q) use ($start, $end) {
                        $q->where('start_time', '<', $end)
                            ->where('end_time', '>', $start);
                    });
                });

            if ($employee->role === 'doctor') {
                $conflictingQuery->whereHas('employee.doctor', function ($q) use ($employee) {
                    $q->where('department_id', $employee->doctor->department_id);
                });

            } else {
                $conflictingQuery->whereHas('employee', function ($q) {
                    $q->where('role', 'receptionist');
                });
            }

            if ($conflictingQuery->exists()) {
                return response()->json(['error' => 'Schedule conflict for this day and shift'], 422);
            }
        }

        // إنشاء وقت جديد
        $time = Time::create([
            'employee_id' => $validated['employee_id'],
            'start_time' => $start,
            'end_time' => $end,
        ]);

        $time->days()->attach($dayIds);

        return new TimeResource($time->load('employee', 'days'));
    }
//    public function store(WorkingDetailsRequest $request)
//    {
//        $validated = $request->validated();
//
//        // تحويل الوقت إلى 24 ساعة للتخزين
//        $start = Carbon::createFromFormat('g:i A', $validated['start_time'])->format('H:i:s');
//        $end = Carbon::createFromFormat('g:i A', $validated['end_time'])->format('H:i:s');
//
//        // جلب IDs الأيام بناءً على الأسماء (lowercase)
//        $dayIds = Day::whereIn('day_name', array_map('strtolower', $validated['days']))->pluck('id');
//
//        if ($dayIds->isEmpty()) {
//            return response()->json(['error' => 'No valid days found'], 422);
//        }
//
//        // إنشاء سجل الوقت
//        $time = Time::create([
//            'employee_id' => $validated['employee_id'],
//            'start_time' => $start,
//            'end_time' => $end,
//        ]);
//
//        // ربط الأيام
//        $time->days()->attach($dayIds);
//
//        // إعادة الريسبونس باستخدام الـ Resource
//        return new TimeResource($time->load('employee', 'days'));
//    }

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

        // تعديل: حوّلي أسماء الأيام لحروف صغيرة أولاً
        if ($request->has('days')) {
            $request->merge([
                'days' => array_map('strtolower', $request->input('days'))
            ]);
        }

        $validated = $request->validate([
            'start_time' => ['required', 'date_format:g:i A'],
            'end_time' => ['required', 'date_format:g:i A'],
            'days' => ['required', 'array'],
            'days.*' => ['string', Rule::in(['sunday', 'monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday'])],
        ]);

        $time = Time::findOrFail($id);

        // تحويل الوقت إلى صيغة 24 ساعة (لحفظه في قاعدة البيانات بشكل صحيح)
        $start = date("H:i:s", strtotime($validated['start_time']));
        $end = date("H:i:s", strtotime($validated['end_time']));

        $time->update([
            'start_time' => $start,
            'end_time' => $end,
        ]);

        // الحصول على IDs الأيام من جدول Day
        $dayIds = Day::whereIn('day_name', array_map('strtolower', $validated['days']))->pluck('id');

        // مزامنة الأيام
        $time->days()->sync($dayIds);

        // رجع النتيجة باستخدام Resource
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


}
