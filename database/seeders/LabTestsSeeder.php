<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Appointment;
use App\Models\LabTest;

class LabTestsSeeder extends Seeder
{
    public function run(): void
    {
        // قائمة اختبارات مخبرية بالإنكليزي والعربي
        $labTests = [
            ['en' => 'Complete Blood Count (CBC)', 'ar' => 'تعداد الدم الكامل'],
            ['en' => 'Blood Sugar Test', 'ar' => 'اختبار سكر الدم'],
            ['en' => 'Liver Function Test', 'ar' => 'اختبار وظائف الكبد'],
            ['en' => 'Kidney Function Test', 'ar' => 'اختبار وظائف الكلى'],
            ['en' => 'Lipid Profile', 'ar' => 'تحليل الدهون'],
            ['en' => 'Urine Analysis', 'ar' => 'تحليل البول'],
            ['en' => 'Thyroid Function Test', 'ar' => 'اختبار وظائف الغدة الدرقية'],
            ['en' => 'Electrolyte Test', 'ar' => 'اختبار الشوارد']
        ];

        $appointments = Appointment::where('status->en', 'completed')->get();

        foreach ($appointments as $appointment) {
            // اختار اختبارين عشوائيين لكل معاينة
            $randomTests = collect($labTests)->shuffle()->take(2);

            foreach ($randomTests as $test) {
                LabTest::create([
                    'appointment_id' => $appointment->id,
                    'name' => [
                        'en' => $test['en'],
                        'ar' => $test['ar']
                    ]
                ]);
            }
        }
    }
}
