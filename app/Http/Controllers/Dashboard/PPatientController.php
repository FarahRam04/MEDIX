<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\Patient;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class PPatientController extends Controller
{

    public function registerPatient(Request $request)
    {

        $validatedData = $request->validate([
            'first_name'   => 'required|string|max:255',
            'last_name'    => 'required|string|max:255',
            'email'        => 'required|email|unique:users,email',
            'phone_number' => 'required|string|unique:users,phone_number',
            'gender'       => 'required|in:male,female',
            'birth_date'   => 'required|date',
        ]);
        $temporaryPassword = Str::random(10);

        try {

            $user = User::create([
                'first_name'   => $validatedData['first_name'],
                'last_name'    => $validatedData['last_name'],
                'email'        => $validatedData['email'],
                'phone_number' => $validatedData['phone_number'],
                'gender'       => $validatedData['gender'],
                'birth_date'   => $validatedData['birth_date'],
                'password'     => Hash::make($temporaryPassword),
                'is_patient'   => true,
            ]);

            $patient = Patient::create(['user_id' => $user->id]);
            $fullName = $user->first_name . ' ' . $user->last_name;
            $emailBody = "Hello $fullName,\n\n" .
                "Your account has been successfully created reception  in our Clinic MEDIX.\n" .
                "Here are your temporary login credentials:\n\n" .
                "Email: " . $user->email . "\n" .
                "Temporary Password: " . $temporaryPassword . "\n\n" .
                "For your security, please change this password after your first login.\n\n" .
                "Best regards,\n" .
                "System Administration";

            Mail::raw($emailBody, function ($message) use ($user, $fullName) {
                $message->to($user->email)
                    ->subject("Your Login Details - $fullName");
            });

            return response()->json([
                'message' => 'Patient account created successfully. A temporary password has been sent to their email.',
                'patient' => [
                    'id' => $patient->id,
                    'name' => $user->first_name . ' ' . $user->last_name,
                    'email' => $user->email,
                ]
            ], 201);

        } catch (\Exception $e) {

            return response()->json([
                'message' => 'An error occurred while creating the patient account.',
                'error' => $e->getMessage()
            ], 500);
        }
    }


    public function search(Request $request)
    {

        $request->validate(['query' => 'required|string|min:2']);
        $query = $request->query('query');

        // 2. البحث في جدول users عن المرضى المطابقين
        $patients = User::where('is_patient', true)
            ->where(function($q) use ($query) {
                $q->where('first_name', 'like', "{$query}%")
                    ->orWhere('last_name', 'like', "{$query}%")
                    ->orWhere('phone_number', 'like', "{$query}%");
            })
            ->limit(10) // نحدد 10 نتائج كحد أقصى لتجنب إرجاع بيانات ضخمة
            ->get();

        // 3. تنسيق البيانات لتكون سهلة الاستخدام في الواجهة
        $formattedPatients = $patients->map(function ($user) {
            // نتأكد من وجود سجل patient مرتبط لتجنب الأخطاء
            if ($user->patient) {
                return [
                    'id' => $user->patient->id, // هذا هو patient_id الذي نحتاجه للحجز
                    'name' => $user->first_name . ' ' . $user->last_name,
                    'phone' => $user->phone_number,
                ];
            }
            return null;
        })->filter(); // filter() لإزالة أي نتائج فارغة (null)

        return response()->json( ['suggests'=>$formattedPatients]);
    }
}
