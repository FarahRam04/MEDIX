<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreVacationRequest;
use App\Http\Requests\UpdateVacationRequest;
use App\Http\Resources\VacationResource;
use App\Models\Appointment;
use App\Models\User;
use App\Models\Vacation;
use App\Services\NotificationService;
use Carbon\Carbon;
use Illuminate\Http\Request;



class VacationController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $vacations = Vacation::with('employee')->get();
        return VacationResource::collection($vacations);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreVacationRequest $request)
    {
        $validated = $request->validated();
        $vacation = Vacation::create($validated);
        $vacation->load('employee');

        // إلغاء المواعيد ضمن فترة الإجازة للطبيب
        if ($vacation->employee->role === 'doctor') {
        $this->cancelAppointmentsForVacation(
            $validated['employee_id'],
            $validated['start_day'],
            $validated['end_day']
        );}


        return response()->json([
            'message' => 'Vacation created',
            'vacations' => new VacationResource($vacation)
        ],201);
    }

    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        $vacation = Vacation::with('employee')->find($id);
        if(!$vacation){
            return response()->json(['message'=>'vacation not found.'],404);
        }
        return response()->json(['message'=>'vacation and its employee.',
            'vacation'=>new VacationResource($vacation)],200);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateVacationRequest $request, string $id)
    {
        $vacationToUpdate = Vacation::find($id);
        if (!$vacationToUpdate) {
            return response()->json(['message' => 'Vacation not found!'], 404);
        }
        $validatedData = $request->validated();
        if (in_array($vacationToUpdate->status, ['expired', 'cancelled']) && $vacationToUpdate->status !== 'active') {
            return response()->json(['message' => ['status' => ['لا يمكن تعديل إجازة في حالة منتهية أو ملغاة.']]], 422);
        }

        $employee = $vacationToUpdate->employee;
        $startDay = $request->input('start_day', $vacationToUpdate->start_day);
        $endDay = $request->input('end_day', $vacationToUpdate->end_day);
        $days = $request->input('days', $vacationToUpdate->days);

        if ($request->has('days') || $request->has('start_day') || $request->has('end_day')) {
            $calculatedDays = Carbon::parse($startDay)->diffInDays(Carbon::parse($endDay)) + 1;
            if ($days != $calculatedDays) {
                return response()->json(['message' => ['days' => ['عدد الأيام المدخل (' . $days . ') لا يتطابق مع الفترة المحددة. الصحيح هو: ' . $calculatedDays]]], 422);
            }
        }
        // عدم تدخل اجازتين
        $isOverlapping = Vacation::where('employee_id', $employee->id)
            ->where('id', '!=', $vacationToUpdate->id)
            ->where('status', '!=', 'cancelled')
            ->where('start_day', '<=', $endDay)
            ->where('end_day', '>=', $startDay)
            ->exists();
        if ($isOverlapping) {
            return response()->json(['message' => ['start_day' => ['فترة الإجازة المعدلة تتداخل مع إجازة أخرى.']]], 422);
        }
        //  التحقق من رصيد الإجازات
        $usedDays = $employee->vacations()->where('id', '!=', $vacationToUpdate->id)->whereIn('status', ['active', 'expired'])->sum('days');
        $maxDays = ($employee->role === 'doctor') ? 40 : 14;
        if (($usedDays + $days) > $maxDays) {
            return response()->json(['message' => ['days' => ['رصيد الإجازات سيتجاوز الحد المسموح به']]], 422);
        }

        $vacationToUpdate->update($validatedData);

        if ($vacationToUpdate->employee->role === 'doctor' && $vacationToUpdate->status === 'active') {
            $this->cancelAppointmentsForVacation($vacationToUpdate->employee_id, $startDay, $endDay);
        }

        return response()->json([
            'message' => 'Vacation updated successfully',
            'vacation' => new VacationResource($vacationToUpdate->load('employee'))
        ], 200);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $vacation = Vacation::with('employee')->find($id);
        if(!$vacation){
            return response()->json([
                'message'=>'Vacation not found.'
            ],404);}
        $vacation->status = 'cancelled';
        $vacation->save();
        return response()->json([
            'message' => 'Vacation cancelled successfully',
            'data' => new VacationResource($vacation) // استخدام الـ Resource هنا
        ], 200);


    }

    function cancelAppointmentsForVacation(int $doctorId, string $startDate, string $endDate)
    {
        $appointments = Appointment::with('patient')
            ->where('doctor_id', $doctorId)
            ->whereBetween('date', [$startDate, $endDate])
            ->get();

        $notificationService = app(NotificationService::class);

        foreach ($appointments as $appointment) {
            $appointment->forceDelete();

            $user = $appointment->patient->user ?? null;
            if (!$user || !$user->fcm_token) continue;

            // إرسال إشعار للمريض عنده fcm_token
                $title = 'إلغاء الموعد';
                $body = 'تم إلغاء موعدك مع الطبيب بتاريخ ' . $appointment->date . ' بسبب إجازة الطبيب.';
                $type = 'cancellation';

                $notificationService->sendFCMNotification($user->fcm_token, $title, $body, $type);

        }
    }
}


