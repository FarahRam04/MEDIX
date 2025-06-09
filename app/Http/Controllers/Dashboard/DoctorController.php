<?php

namespace App\Http\Controllers\Dashboard;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateDoctorProfileRequest;
use App\Models\Doctor;
use Illuminate\Http\Request;

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
        $doctor->department_id = $request->input('department_id', $doctor->department_id);
        $doctor->certificate = $request->input('certificate', $doctor->certificate);
        $doctor->qualifications = $request->input('qualifications', $doctor->qualifications);
        $doctor->years_of_experience = $request->input('years_of_experience', $doctor->years_of_experience);
        $doctor->medical_license_number=$request->input('medical_license_number', $doctor->medical_license_number);
        // معالجة الصورة إن وُجدت
        if ($request->hasFile('image')) {
            // حذف الصورة القديمة إن وُجدت
            if ($doctor->image && Storage::exists($doctor->image)) {
                Storage::delete($doctor->image);
            }

            // رفع الصورة الجديدة وتحديث المسار
            $imagePath = $request->file('image')->store('images', 'public');
            $doctor->image = asset('storage/' . $imagePath);
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




}
