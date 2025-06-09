<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Patient;
use App\Models\Doctor;
use App\Models\Appointment;
use Illuminate\Support\Facades\DB;
use Faker\Factory as Faker;
use Carbon\Carbon;

class AppointmentSeeder extends Seeder
{
    public function run()
    {
        $faker = Faker::create();

        $patients = Patient::all();
        $doctors = Doctor::with(['department', 'employee'])->get();

        $appointmentsCreated = 0;
        $maxAppointments = 40;
        $maxAttempts = 1000;
        $attempts = 0;

        // 🟢 المواعيد "الحالية" والمستقبلية (status = pending)
        $validFutureDates = collect(range(0, 4))
            ->map(fn($i) => Carbon::today('Asia/Damascus')->addDays($i)->format('Y-m-d'))
            ->toArray();

        while ($appointmentsCreated < $maxAppointments && $attempts < $maxAttempts) {
            $attempts++;

            $patient = $patients->random();
            $doctor = $doctors->random();

            $departmentId = $doctor->department_id;
            $specialization = $doctor->department->name ?? 'General';

            $slotIds = $doctor->availableSlots()->pluck('available_slots.id')->toArray();
            if (empty($slotIds)) continue;

            $slotId = $faker->randomElement($slotIds);
            $date = $faker->randomElement($validFutureDates);

            // منع تكرار الموعد للمريض في نفس اليوم والوقت
            $conflict = Appointment::where('patient_id', $patient->id)
                ->where('date', $date)
                ->where('slot_id', $slotId)
                ->exists();

            if ($conflict) continue;

            Appointment::create([
                'doctor_id' => $doctor->id,
                'patient_id' => $patient->id,
                'department_id' => $departmentId,
                'date' => $date,
                'slot_id' => $slotId,
                'type' => $faker->randomElement(['check_up', 'follow_up']),
                'specialization' => $specialization,
                'status' => 'pending',
                'check_up_price' => 50000,
                'lab_tests' => false,
                'total_price' => null,
                'payment_status' => false,
                'with_medical_report' => false,
            ]);

            $appointmentsCreated++;
        }

        echo "✅ Future Appointments created: $appointmentsCreated\n";

        // 🟣 المواعيد السابقة (status = completed)
        $completedAppointments = 0;
        $maxCompleted = 40;
        $attempts = 0;

        while ($completedAppointments < $maxCompleted && $attempts < $maxAttempts) {
            $attempts++;

            $patient = $patients->random();
            $doctor = $doctors->random();

            $departmentId = $doctor->department_id;
            $specialization = $doctor->department->name ?? 'General';

            $slotIds = $doctor->availableSlots()->pluck('available_slots.id')->toArray();
            if (empty($slotIds)) continue;

            $slotId = $faker->randomElement($slotIds);

            // ✅ نولد تاريخ عشوائي قبل اليوم (مثلاً من -30 يوم إلى -1 يوم)
            $date = Carbon::today('Asia/Damascus')->subDays(rand(1, 30))->format('Y-m-d');

            $conflict = Appointment::where('patient_id', $patient->id)
                ->where('date', $date)
                ->where('slot_id', $slotId)
                ->exists();

            if ($conflict) continue;

            Appointment::create([
                'doctor_id' => $doctor->id,
                'patient_id' => $patient->id,
                'department_id' => $departmentId,
                'date' => $date,
                'slot_id' => $slotId,
                'type' => $faker->randomElement(['check_up', 'follow_up']),
                'specialization' => $specialization,
                'status' => 'completed',
                'check_up_price' => 50000,
                'lab_tests' => false,
                'total_price' => 50000,
                'payment_status' => true,
                'with_medical_report' => $faker->boolean(30),
            ]);
            $completedAppointments++;
        }

        echo "✅ Completed Appointments created: $completedAppointments\n";
    }
}
