<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Patient;
use App\Models\Doctor;
use App\Models\Appointment;
use Faker\Factory as Faker;
use Carbon\Carbon;

class AppointmentSeeder extends Seeder
{
    public function run()
    {
        $faker = Faker::create();
        $patients = Patient::all();
        $doctors = Doctor::with(['department', 'employee.time.days', 'availableSlots'])->get();

        // توليد المواعيد لكل حالة status
        $this->createAppointments('pending', 40, $patients, $doctors, $faker, true);    // مواعيد حالية/مستقبلية
        $this->createAppointments('completed', 40, $patients, $doctors, $faker, false); // مواعيد سابقة
    }


    private function createAppointments($status, $maxAppointments, $patients, $doctors, $faker, $futureDates = true)
    {
        $appointmentsCreated = 0;
        $maxAttempts = 1000;
        $attempts = 0;

        while ($appointmentsCreated < $maxAppointments && $attempts < $maxAttempts) {
            $attempts++;

            $patient = $patients->random();
            $doctor = $doctors->random();

            $time = $doctor->employee->time;
            if (!$time) continue; // لا يوجد جدول دوام

            $availableDays = $time->days->pluck('id')->all();
            if (empty($availableDays)) continue;

            // توليد تاريخ ضمن أيام دوام الدكتور
            $date = null;
            $dayOfWeek = null;
            for ($i = 0; $i < 20; $i++) { // محاولة 20 مرة لتوليد تاريخ مناسب
                $tempDate = $futureDates
                    ? Carbon::today('Asia/Damascus')->addDays(rand(0, 30))
                    : Carbon::today('Asia/Damascus')->subDays(rand(1, 30));

                $tempDay = $tempDate->dayOfWeek;
                if (in_array($tempDay, $availableDays, true)) {
                    $date = $tempDate->format('Y-m-d');
                    $dayOfWeek = $tempDay;
                    break;
                }
            }
            if (!$date) continue; // لم نتمكن من توليد تاريخ مناسب

            // اختيار slot متوافق مع اليوم
            $slot = $doctor->availableSlots->random(null);
            if (!$slot) continue;

            // تحقق من عدم وجود تعارض للمريض
            $conflict = Appointment::where('date', $date)
                ->where('slot_id', $slot->id)
                ->exists();
            if ($conflict) continue;

            // إنشاء الموعد
            Appointment::create([
                'doctor_id' => $doctor->id,
                'patient_id' => $patient->id,
                'department_id' => $doctor->department_id,
                'date' => $date,
                'slot_id' => $slot->id,
                'type' => $faker->randomElement(['check_up', 'follow_up']),
                'specialization' => $doctor->department->name ?? 'General',
                'status' => $status,
                'total_price' => 50000 ,
                'payment_status' => $status === 'completed',
                'with_medical_report' => $status === 'completed' && $faker->boolean(30),
            ]);

            $appointmentsCreated++;
        }

        echo "✅ $status Appointments created: $appointmentsCreated\n";
    }
}
