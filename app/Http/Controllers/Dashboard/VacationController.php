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
use http\Env\Response;
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
    public function update(UpdateVacationRequest $request, Vacation $vacation)
    {

        $validated = $request->validated();
        $vacation->update($validated);
        if ($vacation->employee->role === 'doctor'&&$vacation->status === 'active') {
        $startDay = $validated['start_day'] ?? $vacation->start_day;
        $endDay = $validated['end_day'] ?? $vacation->end_day;

        // إلغاء المواعيد ضمن فترة الإجازة للطبيب
        $this->cancelAppointmentsForVacation(
            $vacation->employee_id, // استخدم ID الموظف من الإجازة مباشرة
            $startDay,
            $endDay
        );}

        return response()->json([
            'message' => 'Vacation updated successfully',
            'vacation' => new VacationResource($vacation->load('employee')) // أعد تحميل العلاقة للتأكد من حداثة البيانات
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
                $type = 'appointment';

                $notificationService->sendFCMNotification($user->fcm_token, $title, $body, $type);

        }
    }
}
