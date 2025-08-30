<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Appointment;
use App\Models\Surgery;

class SurgeriesSeeder extends Seeder
{
    public function run(): void
    {
        // قائمة العمليات الجراحية مع الترجمة العربية
        $surgeryList = [
            ['en' => 'Appendectomy', 'ar' => 'استئصال الزائدة الدودية'],
            ['en' => 'Cataract Surgery', 'ar' => 'جراحة الماء الأبيض'],
            ['en' => 'Heart Bypass', 'ar' => 'تجاوز الشريان التاجي'],
            ['en' => 'Hernia Repair', 'ar' => 'إصلاح الفتق'],
            ['en' => 'Tonsillectomy', 'ar' => 'استئصال اللوزتين'],
            ['en' => 'Gallbladder Removal', 'ar' => 'استئصال المرارة'],
            ['en' => 'Knee Replacement', 'ar' => 'استبدال الركبة'],
            ['en' => 'Hip Replacement', 'ar' => 'استبدال الورك']
        ];

        $appointments = Appointment::where('status->en', 'completed')->get();

        foreach ($appointments as $appointment) {
            // اختار عمليتين عشوائياً لكل معاينة
            $randomSurgeries = collect($surgeryList)->shuffle()->take(2);

            foreach ($randomSurgeries as $surgery) {
                Surgery::create([
                    'appointment_id' => $appointment->id,
                    'name' => [
                        'en' => $surgery['en'],
                        'ar' => $surgery['ar']
                    ]
                ]);
            }
        }
    }
}
