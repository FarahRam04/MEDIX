<?php

namespace App\Http\Controllers\Dashboard;
use App\HelperFunctions;
use App\Http\Requests\WritePrescriptionRequest;
use App\Http\Resources\DoctorResource;
use App\Http\Resources\HomeResource;
use App\Models\Advice;
use App\Models\Appointment;
use App\Models\Department;
use App\Models\LabTest;
use App\Models\Qualification;
use App\Models\Surgery;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use App\Services\NotificationService;
use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateDoctorProfileRequest;
use App\Models\Doctor;
use Illuminate\Http\Request;

use App\Models\Medication;
use Stichoza\GoogleTranslate\Exceptions\LargeTextException;
use Stichoza\GoogleTranslate\Exceptions\RateLimitException;
use Stichoza\GoogleTranslate\Exceptions\TranslationRequestException;
use Stichoza\GoogleTranslate\GoogleTranslate;
use function Pest\Laravel\json;


class DoctorController extends Controller
{
    use HelperFunctions;

    public function assignDepartmentAndSpecialty(Request $request, $id)
    {
        $doctor = Doctor::with(['employee', 'department'])->find($id);
        if (!$doctor) {
            return response()->json(['message' => 'Doctor not found.'], 404);
        }
        $validatedData = $request->validate([
            'department_id' => 'required|exists:departments,id',
            'specialist'    => 'required|string|max:255',
        ]);
        $doctor->department_id = $validatedData['department_id'];
        $doctor->specialist = $validatedData['specialist'];
        $doctor->save();
        $doctor->load('department');

        return response()->json([
            'message' => 'Doctor\'s department and specialty have been updated successfully.',
            'doctor' => [
                'id'          => $doctor->id,
                'name'        => optional($doctor->employee)->first_name . ' ' . optional($doctor->employee)->last_name,
                'department'  => optional($doctor->department)->name,
                'specialist'  => $doctor->specialist,
            ]
        ]);
    }



    public function index(Request $request)
    {

        $query = Doctor::with('employee');

        if ($request->has('department_id')) {
            $query->where('department_id', $request->department_id);
        }
        $doctors = $query->get();

        return DoctorResource::collection($doctors);

}

    /**
     * Store a newly created resource in storage.
     */

    public function show(string $id)
    {
        $doctor=Doctor::with('employee','department')->find($id);
        if(!$doctor){
            return  response()->json(['message'=>'Doctor not found.'],200);

        }

        return response()->json(['message'=>'Doctor details retrieved successfully',
            'doctor'=>new DoctorResource($doctor)],200);
    }

    /**
     * Update the specified resource in storage.
     */


    public function update(Request $request)
    {
        // 1. جلب الطبيب المرتبط بالمستخدم المسجل دخوله
        $doctor = Auth::user()->doctor;

        if (!$doctor) {
            return response()->json(['message' => 'Doctor profile not found.'], 404);
        }

        $validatedData = $request->validate([
            'certificate'            => 'nullable|string|max:255',
            'medical_license_number' => 'nullable|string|max:255',
            'bio'                    => 'nullable|string',
            'years_of_experience'    => 'nullable|integer|min:0',
            'image'                  => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048', // 2MB max
            'qualifications'         => 'nullable|array',
            'qualifications.*'       => 'string|max:255', // كل عنصر في المصفوفة يجب أن يكون نصاً
        ]);

        $doctor->fill($request->only([
            'certificate',
            'medical_license_number',
            'bio',
            'years_of_experience'
        ]));


        if ($request->has('qualifications')) {

            $doctor->qualifications()->delete();
            foreach ($request->qualifications as $name) {
                $doctor->qualifications()->create(['name' => $name]);
            }
        }

        if ($request->hasFile('image')) {
            if ($doctor->image && Storage::disk('public')->exists($doctor->image)) {
                Storage::disk('public')->delete($doctor->image);
            }

            $doctor->image = $request->file('image')->store('doctors', 'public');
        }
        $doctor->save();

        $doctor->load('employee', 'department', 'qualifications');
        return response()->json([
            'message' => 'Profile updated successfully.',
            'doctor' => new DoctorResource($doctor)
        ], 200);}

    public function rate(Request $request)
    {
        $validated = $request->validate([
            'appointment_id' => 'required|exists:appointments,id',
            'rating' => 'required|numeric|min:1|max:5',
        ]);

        $user = Auth::user();

        // جلب الموعد والتأكد من علاقته باليوزر والدكتور
        $appointment = Appointment::where('id', $validated['appointment_id'])
            ->where('patient_id', $user->id)
            ->first();

        if (!$appointment) {
            return response()->json([
                'message' => 'هذا الموعد غير مرتبط بك أو غير موجود',
            ], 403);
        }
        if($appointment->status != 'completed'){
           return response()->json([
               'message'=>'لا يمكنك تقييم الدكتور قبل اتمام الزيارة '
           ]);
    }
        $is_rated=$appointment->is_rated;
        if($is_rated){
            return response()->json(['Sorry,you already rated this appointment.'], 403);
        }

        $doctor = Doctor::findOrFail($appointment->doctor_id);

        // تطبيق التقييم على الدكتور
        $doctor->applyRating($validated['rating']);

        return response()->json([
            'message' => 'تمت إضافة التقييم بنجاح',
            'new_final_rating' => round($doctor->final_rating, 2),
            'total_votes' => $doctor->rating_votes,
        ]);
    }


    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }

    /**
     * @throws LargeTextException
     * @throws RateLimitException
     * @throws TranslationRequestException
     */
    public function writePrescription(string $id, WritePrescriptionRequest $request)
    {
        // 1. تحقق من وجود الموعد
        $appointment = Appointment::find($id);
        if (!$appointment) {
            return response()->json(['message' => 'appointment not found'], 404);
        }

        // 2. تحقق من أن الطبيب الحالي هو صاحب الموعد
        $doctor = Auth::user()->doctor;
        if (!$doctor||$appointment->doctor_id !== $doctor->id) {
            return response()->json(['message' => 'You are not authorized to write a prescription for this appointment.'], 403);
        }

        // 3. تحقق من أن الحالة pending
        if ($appointment->status !== 'pending') {
            return response()->json(['message' => 'You can not write a prescription for a completed appointment'], 400);
        }
        try {

            DB::transaction(function () use ($request, $appointment) {

                $tr = new GoogleTranslate();
                $tr->setSource('en');
                $tr->setTarget('ar');

                if ($request->has('medications')) {
                    foreach ($request->medications as $med) {
                        Medication::create([
                            'appointment_id' => $appointment->id,
                            'name' => ['en' => $med['name'], 'ar' => $tr->translate($med['name'])],
                            'type' => ['en' => $med['type'], 'ar' => $tr->translate($med['type'])],
                            'dosage' => ['en' => $med['dosage'], 'ar' => $tr->translate($med['dosage'])],
                            'frequency' => ['en' => $med['frequency'], 'ar' => $tr->translate($med['frequency'])],
                            'duration' => ['en' => $med['duration'], 'ar' => $tr->translate($med['duration'])],
                            'note' => ['en' => $med['note'], 'ar' => $tr->translate($med['note'])],
                        ]);
                    }
                }

                if ($request->has('lab_tests')) {
                    foreach ($request->lab_tests as $labTest) {
                        LabTest::create([
                            'appointment_id' => $appointment->id,
                            'name' => ['en' => $labTest, 'ar' => $tr->translate($labTest)]
                        ]);
                    }
                }

                if ($request->has('surgeries')) {
                    foreach ($request->surgeries as $surgery) {
                        Surgery::create([
                            'appointment_id' => $appointment->id,
                            'name' => ['en' => $surgery, 'ar' => $tr->translate($surgery)]
                        ]);
                    }
                }


                if ($request->has('advices')) {
                    foreach ($request->advices as $advice) {
                        Advice::create([
                            'appointment_id' => $appointment->id,
                            'advice' => ['en' => $advice, 'ar' => $tr->translate($advice)]
                        ]);
                    }
                }

                $totalAdditionalCost = 0;

                if ($request->has('additional_costs')) {
                    foreach ($request->additional_costs as $cost) {
                        $appointment->additionalCosts()->create([
                            'title' => $cost['title'],
                            'price' => $cost['price'],
                        ]);
                        $totalAdditionalCost += $cost['price'];
                    }
                }
                $appointment->final_total_price = $appointment->init_total_price + $totalAdditionalCost;

                $appointment->setTranslation('status', 'en', 'completed');
                $appointment->setTranslation('status', 'ar', 'مكتمل');
                $appointment->save();
                $appointment->doctor->increment('number_of_treatments');


                $patientUser = $appointment->patient->user;
                if ($patientUser && $patientUser->fcm_token) {

                    $notificationService = app(NotificationService::class);

                    $title = 'زيارتك اكتملت';
                    $body ='وصفتك ومعلوماتك الطبية أصبحت متاحة.يرجى المراجعة.';
                    $type = 'prescription';
                    $notificationService->sendFCMNotification($patientUser->fcm_token, $title, $body, $type);
                }
            });

        } catch (\Exception $e) {
            // في حال فشل أي عملية داخل الـ transaction
            return response()->json(['message' => 'An error occurred while saving the prescription.', 'error' => $e->getMessage()], 500);
        }

        return response()->json(['message' => 'Prescription saved and appointment status updated successfully.'], 200);
    }

    public function getPrescription(string $id)
    {
        $appointment = Appointment::find($id);
        if (!$appointment) {
            return response()->json(['message' => 'الموعد غير موجود'], 404);
        }

        $user=Auth::user();

        if ($appointment->patient->user_id != $user->id){
            return response()->json(['message'=> 'انت لا تستطيع الوصول الى الوصفات الطبية التي لا تخصك ..',
                'appointment_patient_id'=>$appointment->patient_id,
                'user_id'=>$user->id
                ]);
        }
        $locale = app()->getLocale();
        $medications = [];

        foreach ($appointment->medications as $med) {
            $medications[] = [
                'id'=>$med->id,
                'name' => $med->getTranslation('name', $locale),
                'type' => $med->getTranslation('type', $locale),
                'dosage' => $med->getTranslation('dosage', $locale),
                'frequency' => $med->getTranslation('frequency', $locale),
                'duration' => $med->getTranslation('duration', $locale),
                'note' => $med->getTranslation('note', $locale),
            ];
        }
        $lab_tests = [];
        foreach($appointment->labTests as $labTest){
            $lab_tests[]=[
              'id'=>$labTest->id,
              'name'=>$labTest->getTranslation('name', $locale),
            ];
        }
        $surgeries =[];
        foreach($appointment->surgeries as $sur){
            $surgeries[]=[
                'id'=>$sur->id,
                'name'=>$sur->getTranslation('name', $locale),
            ];
        }
        $advices =[];
        foreach($appointment->advices as $advice){
            $advices[]=[
                'id'=>$advice->id,
                'advice'=>$advice->getTranslation('advice', $locale),
            ];
        }

        $is_prescription_viewed=$appointment->is_prescription_viewed;
        $viewed=false;
        if ($is_prescription_viewed == 1){
            $viewed=true;
        }

        return response()->json([
            'medications'=>$medications,
            'lab_tests'=>$lab_tests,
            'surgeries'=>$surgeries,
            'advices'=>$advices,
            'is_prescription_viewed'=>$viewed,
        ]);
    }

    public function updatePrescription(Request $request,string $id)
    {
        $request->validate(['is_prescription_viewed'=>'required|boolean']);
        $appointment = Appointment::find($id);
        if (!$appointment) {
            return response()->json(['message' => 'الموعد غير موجود'], 404);
        }

        $appointment->is_prescription_viewed=$request->input('is_prescription_viewed');
        $appointment->save();
        return response()->json(['is_prescription_viewed'=>$appointment->is_prescription_viewed]);

    }

    public function getTop5Doctors()
    {
        $doctors=Doctor::with('employee')
            ->orderByDesc('final_rating')
            ->limit(5)
            ->get();

        return HomeResource::collection($doctors);
    }

    public function getDoctorProfile($id)
    {

        $doctor=Doctor::with('employee.time','department','qualifications')->find($id);
        if (!$doctor) {
            return  response()->json(['doctor not found.']);
        }
        return new DoctorResource($doctor);
    }
    public function getDoctorsRelatedToDepartment(Request $request, $id)
    {
        $request->validate([
            'status' => 'required|in:morning,afternoon'
        ]);

        $startTime = $request->status === 'morning' ? '09:00:00' : '14:00:00';

        $doctors = Doctor::with(['employee.time'])
            ->where('department_id', $id)
            ->whereHas('employee.time', fn($q) => $q->where('start_time', $startTime))
            ->get()
            ->map(function ($doctor){
                return[
                    'id'=>$doctor->id,
                    'name'=>$doctor->employee->first_name.' '.$doctor->employee->last_name,
                    'image'=>$doctor->image_url,
                    'shift'=>$doctor->employee->time->start_time === '09:00:00' ? 'morning' :'afternoon',
                    'treatments'=>$doctor->number_of_treatments,
                    'experience'=>$doctor->years_of_experience,
                    'rate'=>$doctor->final_rating
                ];
            });
        return response()->json($doctors);
    }



    public function uploadMedicalReport(Request $request, $id)
    {

        $request->validate([
            'medical_report' => 'required|file|mimes:pdf,jpg,png|max:5120', // هنا الملف مطلوب
        ]);

        $appointment = Appointment::find($id);
        if (!$appointment) {
            return response()->json(['message' => 'Appointment not found.'], 404);
        }

        $doctor = Auth::user()->doctor;
        if ($appointment->doctor_id !== $doctor->id) {
            return response()->json(['message' => 'You are not authorized for this appointment.'], 403);
        }


        if (!$appointment->with_medical_report) {
            return response()->json(['message' => 'A medical report was not requested for this appointment.'], 400);
        }


        try {

            if ($appointment->medical_report_path && Storage::disk('public')->exists($appointment->medical_report_path)) {
                Storage::disk('public')->delete($appointment->medical_report_path);
            }

            $path = $request->file('medical_report')->store('medical_reports', 'public');
            $appointment->medical_report_path = $path;
            $appointment->save();
            $patientUser = $appointment->patient->user;
            if ($patientUser && $patientUser->fcm_token) {
                $notificationService = app(NotificationService::class);
                $title = 'التقرير الطبي متاح';
                $body = 'تقريرك الطبي رفع وأصبح متاح .';
                $notificationService->sendFCMNotification($patientUser->fcm_token, $title, $body, 'report');
            }

        } catch (\Exception $e) {
            return response()->json(['message' => 'Failed to upload report.', 'error' => $e->getMessage()], 500);
        }

        return response()->json([
            'message' => 'Medical report uploaded successfully.',
            'report_url' => Storage::disk('public')->url($appointment->medical_report_path)
        ], 200);
    }



}
