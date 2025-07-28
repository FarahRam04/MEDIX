<?php

namespace App\Http\Controllers\Dashboard;
use App\Http\Requests\WritePrescriptionRequest;
use App\Http\Resources\DoctorResource;
use App\Http\Resources\HomeResource;
use App\Models\Advice;
use App\Models\Appointment;
use App\Models\LabTest;
use App\Models\Surgery;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateDoctorProfileRequest;
use App\Models\Doctor;
use Illuminate\Http\Request;

use App\Models\Medication;
class DoctorController extends Controller
{
    public function index()
    {
        return response()->json(Doctor::with('employee','department')->get(),200);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $doctor=Doctor::with('employee','department')->findOrFail($id);
        return response()->json($doctor,200);
    }

    /**
     * Update the specified resource in storage.
     */


    public function update(UpdateDoctorProfileRequest $request)
    {
        // الحصول على الدكتور الحالي من الموظف المسجل
        $doctor = Auth::user()->doctor; // يفترض أنه مستخدم ضمن Guard خاص بالموظفين

        if (!$doctor) {
            return response()->json(['message' => 'Doctor profile not found.'], 404);
        }

        // تحديث البيانات المطلوبة فقط
        $department_id = $request->input('department_id', $doctor->department_id);
        $doctor->department_id = $department_id;
        $doctor->certificate = $request->input('certificate', $doctor->certificate);
        //$doctor->qualifications = $request->input('qualifications', $doctor->qualifications);
        $doctor->bio=$request->input('bio', $doctor->bio);
        $doctor->medical_license_number=$request->input('medical_license_number', $doctor->medical_license_number);
        $years_of_experience=$request->input('years_of_experience', $doctor->years_of_experience);
        $doctor->years_of_experience =$years_of_experience ;


        $departmentSpecialists = [
            1 => 'General Practitioner',
            2 => 'Cardiologist',
            3 => 'Dermatologist',
            4 => 'Gastroenterologist',
        ];

        $doctor->specialist = $departmentSpecialists[$department_id];
        // معالجة الصورة إن وُجدت
        if ($request->hasFile('image')) {
            // حذف الصورة القديمة إن وُجدت
            if ($doctor->image && Storage::exists($doctor->image)) {
                Storage::delete($doctor->image);
            }

            // رفع الصورة الجديدة وتحديث المسار
            $imagePath = $request->file('image')->store('images', 'public');
            $doctor->image = $imagePath;
        }

        /////////////Rating
        $doctor->initial_rating=Doctor::getInitialRatingFromExperience($doctor->years_of_experience);
        $doctor->rating_votes = 0;
        $doctor->rating_total = 0;
        $doctor->final_rating = $doctor->initial_rating;
        $doctor->save();

        return response()->json(['message' => 'Profile updated successfully.', 'doctor' => $doctor->load('department','employee')], 200);

    }
    // إضافة تقييم جديد لدكتور

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

    public function writePrescription(string $id, WritePrescriptionRequest $request)
    {
        // 1. تحقق من وجود الموعد
        $appointment = Appointment::find($id);
        if (!$appointment) {
            return response()->json(['message' => 'الموعد غير موجود'], 404);
        }

        // 2. تحقق من أن الطبيب الحالي هو صاحب الموعد
        $employee = Auth::user();

        $doctor = DB::table('doctors')
            ->where('employee_id', $employee->id)
            ->first();

        if ($appointment->doctor_id !== $doctor->id) {
            return response()->json([
                'message' => 'هذا الموعد لا يخص الطبيب الحالي',
                'appointment->doctor_id'=>$appointment->doctor_id,
                'token->doctor_id'=>$doctor->id
            ], 403);

        }

        // 3. تحقق من أن الحالة pending
        if ($appointment->status !== 'pending') {
            return response()->json(['message' => 'لا يمكن كتابة وصفة لموعد غير معلق'], 400);
        }

        // 4. تخزين الأدوية
        $medications = $request->input('medications');
        if (is_array($medications)) {
            foreach ($medications as $med) {
                Medication::create([
                    'appointment_id' => $appointment->id,
                    'name' => $med['name'],
                    'type' => $med['type'],
                    'dosage' => $med['dosage'],
                    'frequency' => $med['frequency'],
                    'duration' => $med['duration'],
                    'note' => $med['note'],
                ]);
            }
        }
        $labTests = $request->input('lab_tests');
        if (is_array($labTests)) {
            foreach ($labTests as $labTest) {
                LabTest::create([
                    'appointment_id' => $appointment->id,
                    'name'=>$labTest
                ]);
            }
        }

        $surgeries=$request->input('surgeries');
        if (is_array($surgeries)) {
            foreach ($surgeries as $sur) {
                Surgery::create([
                    'appointment_id' => $appointment->id,
                    'name'=>$sur
                ]);
            }
        }
        $advices=$request->input('advices');
        if (is_array($advices)) {
            foreach ($advices as $ad) {
                Advice::create([
                    'appointment_id' => $appointment->id,
                    'advice'=>$ad
                ]);
            }
        }


        // 5. تحديث حالة الموعد
        $appointment->status = 'completed';
        $appointment->doctor->number_of_treatments +=1;
        $appointment->doctor->save();
        $appointment->save();

        return response()->json([
            'message' => 'تم حفظ الوصفة وتحديث حالة الموعد',
        ], 200);
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
        $medications = $appointment->medications;
        $lab_tests = $appointment->labTests;
        $surgeries = $appointment->surgeries;
        $advices = $appointment->advices;

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





}
