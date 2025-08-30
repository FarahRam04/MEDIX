<?php

namespace Database\Seeders;

use App\Models\Doctor;
use App\Models\Qualification;
use Illuminate\Database\Seeder;
use Stichoza\GoogleTranslate\GoogleTranslate;

class QualificationSeeder extends Seeder
{
    public function run(): void
    {
        $doctors = Doctor::all();

        // Google Translate
        $tr = new GoogleTranslate();
        $tr->setSource('en');
        $tr->setTarget('ar');

        // مؤهلات لكل قسم
        $departmentQualifications = [
            1 => ['Bachelor of surgery', 'MD Ophthalmology'],
            2 => ['MD Cardiology', 'Diploma in Cardiology'],
            3 => ['Dermatology Specialist', 'MD Dermatology'],
            4 => ['Gastroenterology Diploma', 'MD Gastroenterology'],
            5 => ['Neurology Specialist', 'MD Neurology'],
            6 => ['Pediatric MD', 'Diploma in Pediatrics'],
        ];

        foreach ($doctors as $doctor) {
            $departmentId = $doctor->department_id;
            if (!isset($departmentQualifications[$departmentId])) {
                continue; // إذا القسم بدون مؤهلات معرفة
            }

            $qualificationsPool = $departmentQualifications[$departmentId];

            // نختار 2-4 مؤهلات عشوائية من المجموعة المناسبة
            $count = rand(2, min(4, count($qualificationsPool)));
            $selectedQualifications = collect($qualificationsPool)->random($count);

            foreach ($selectedQualifications as $qualEn) {
                $qualAr = $tr->translate($qualEn);

                Qualification::create([
                    'doctor_id' => $doctor->id,
                    'name' => ['en' => $qualEn, 'ar' => $qualAr],
                ]);
            }
        }
    }
}
