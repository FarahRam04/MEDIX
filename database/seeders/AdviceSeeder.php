<?php

namespace Database\Seeders;

use App\Models\Appointment;
use App\Models\Advice;
use Illuminate\Database\Seeder;

class AdviceSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $appointments = Appointment::where('status->en', 'completed')->get();

        $advices = [
            ['en' => 'Avoid excessive stress', 'ar' => 'تجنب الإجهاد المفرط'],
            ['en' => 'Drink enough water', 'ar' => 'اشرب كمية كافية من الماء'],
            ['en' => 'Get enough sleep', 'ar' => 'خذ قسطاً كافياً من النوم'],
            ['en' => 'Follow a balanced diet', 'ar' => 'اتبع نظام غذائي متوازن'],
            ['en' => 'Do regular exercise', 'ar' => 'مارس التمارين الرياضية بانتظام'],
            ['en' => 'Avoid smoking', 'ar' => 'تجنب التدخين'],
            ['en' => 'Reduce sugar intake', 'ar' => 'قلل من تناول السكر'],
            ['en' => 'Take medications on time', 'ar' => 'تناول الأدوية في وقتها'],
            ['en' => 'Maintain a positive mindset', 'ar' => 'حافظ على عقلية إيجابية'],
        ];

        foreach ($appointments as $appointment) {
            // لكل معاينة completed نضيف نصيحتين
            for ($i = 0; $i < 2; $i++) {
                Advice::create([
                    'advice' => $advices[array_rand($advices)],
                    'appointment_id' => $appointment->id,
                ]);
            }
        }
    }
}
