<?php

namespace Database\Seeders;

use App\Models\Medication;
use Illuminate\Database\Seeder;
use App\Models\Appointment;


class MedicineSeeder extends Seeder
{
    public function run(): void
    {
        $medicines = [
            [
                'name' => ['en' => 'Paracetamol', 'ar' => 'باراسيتامول'],
                'type' => ['en' => 'Tablet', 'ar' => 'أقراص'],
                'dosage' => ['en' => '500 mg', 'ar' => '500 ملغ'],
                'frequency' => ['en' => 'Twice daily', 'ar' => 'مرتين يومياً'],
                'duration' => ['en' => '5 days', 'ar' => '5 أيام'],
                'note' => ['en' => 'After meals', 'ar' => 'بعد الطعام'],
            ],
            [
                'name' => ['en' => 'Amoxicillin', 'ar' => 'أموكسيسيلين'],
                'type' => ['en' => 'Capsule', 'ar' => 'كبسولات'],
                'dosage' => ['en' => '250 mg', 'ar' => '250 ملغ'],
                'frequency' => ['en' => 'Three times daily', 'ar' => 'ثلاث مرات يومياً'],
                'duration' => ['en' => '7 days', 'ar' => '7 أيام'],
                'note' => ['en' => 'Before meals', 'ar' => 'قبل الطعام'],
            ],
            [
                'name' => ['en' => 'Cough Syrup', 'ar' => 'شراب السعال'],
                'type' => ['en' => 'Syrup', 'ar' => 'شراب'],
                'dosage' => ['en' => '10 ml', 'ar' => '10 مل'],
                'frequency' => ['en' => 'Twice daily', 'ar' => 'مرتين يومياً'],
                'duration' => ['en' => '3 days', 'ar' => '3 أيام'],
                'note' => ['en' => 'Before sleep', 'ar' => 'قبل النوم'],
            ],
            [
                'name' => ['en' => 'Ibuprofen', 'ar' => 'إيبوبروفين'],
                'type' => ['en' => 'Tablet', 'ar' => 'أقراص'],
                'dosage' => ['en' => '400 mg', 'ar' => '400 ملغ'],
                'frequency' => ['en' => 'Twice daily', 'ar' => 'مرتين يومياً'],
                'duration' => ['en' => '5 days', 'ar' => '5 أيام'],
                'note' => ['en' => 'After meals', 'ar' => 'بعد الطعام'],
            ],
            [
                'name' => ['en' => 'Vitamin C', 'ar' => 'فيتامين سي'],
                'type' => ['en' => 'Tablet', 'ar' => 'أقراص'],
                'dosage' => ['en' => '1000 mg', 'ar' => '1000 ملغ'],
                'frequency' => ['en' => 'Once daily', 'ar' => 'مرة يومياً'],
                'duration' => ['en' => '10 days', 'ar' => '10 أيام'],
                'note' => ['en' => 'Morning after breakfast', 'ar' => 'صباحاً بعد الفطور'],
            ],
        ];

        // اختار فقط المواعيد المنتهية (completed)
        $appointments = Appointment::where('status->en', 'completed')->get();

        foreach ($appointments as $appointment) {
            $randomMeds = collect($medicines)->shuffle()->take(2);

            foreach ($randomMeds as $med) {
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
    }
}
