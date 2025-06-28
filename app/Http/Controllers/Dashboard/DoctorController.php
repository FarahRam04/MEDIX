<?php

namespace App\Http\Controllers\Dashboard;
use App\Http\Requests\WritePrescriptionRequest;
use App\Models\Advice;
use App\Models\Appointment;
use App\Models\LabTest;
use App\Models\Surgery;
use Illuminate\Support\Facades\Auth;
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
        $doctor->qualifications = $request->input('qualifications', $doctor->qualifications);
        $doctor->bio=$request->input('bio', $doctor->bio);
        $doctor->medical_license_number=$request->input('medical_license_number', $doctor->medical_license_number);
        $years_of_experience=$request->input('years_of_experience', $doctor->years_of_experience);
        $doctor->years_of_experience =$years_of_experience ;
        if ($years_of_experience <= 1) {
            $ratio = 3.1;
        } elseif ($years_of_experience <= 4) {
            $ratio = 3.6;
        } elseif ($years_of_experience <= 9) {
            $ratio = 4.15;
        } elseif ($years_of_experience <= 15) {
            $ratio = 4.5;
        } else {
            $ratio = 4.7;
        }

        $doctor->rating = $ratio;

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



        $doctor->save();

        return response()->json(['message' => 'Profile updated successfully.', 'doctor' => $doctor->load('department','employee')], 200);

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
        $doctor = Auth::user();
        if ($appointment->doctor_id !== $doctor->id) {
            return response()->json(['message' => 'هذا الموعد لا يخص الطبيب الحالي'], 403);
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
        $appointment->save();

        return response()->json([
            'message' => 'تم حفظ الوصفة وتحديث حالة الموعد',
        ], 200);
    }

    public function getMedications(string $id)
    {
        $appointment = Appointment::find($id);
        if (!$appointment) {
            return response()->json(['message' => 'الموعد غير موجود'], 404);
        }
        $medications = $appointment->medications;

        return response()->json($medications);
    }
    public function getLabTests(string $id)
    {
        $appointment = Appointment::find($id);
        if (!$appointment) {
            return response()->json(['message' => 'الموعد غير موجود'], 404);
        }
        $lab_tests = $appointment->labTests;

        return response()->json($lab_tests);
    }
    public function getSurgeries(string $id)
    {
        $appointment = Appointment::find($id);
        if (!$appointment) {
            return response()->json(['message' => 'الموعد غير موجود'], 404);
        }
        $surgeries = $appointment->surgeries;

        return response()->json($surgeries);
    }

    public function getAdvices(string $id)
    {
        $appointment = Appointment::find($id);
        if (!$appointment) {
            return response()->json(['message' => 'الموعد غير موجود'], 404);
        }
        $advices = $appointment->advices;

        return response()->json($advices);
    }





}
