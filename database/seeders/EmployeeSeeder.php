<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use App\Models\Employee;
use App\Models\Doctor;
use App\Models\Time;
use Spatie\Permission\Models\Role;
use Faker\Factory as Faker;
use App\HelperFunctions;
use Stichoza\GoogleTranslate\GoogleTranslate;

class EmployeeSeeder extends Seeder
{
    use HelperFunctions;

    public function run()
    {
        $departmentSpecialists = $this->getSpecialists();
        $faker = Faker::create();

        // مترجم جوجل
        $tr = new GoogleTranslate();
        $tr->setSource('en');
        $tr->setTarget('ar');

        $doctorRole = Role::firstOrCreate(['name' => 'doctor']);
        $receptionistRole = Role::firstOrCreate(['name' => 'receptionist']);

        // صار يشمل القسم الأول
        $departments = [1, 2, 3, 4, 5, 6];

        $firstThreeDays = [0, 1, 2]; // الأحد، الإثنين، الثلاثاء
        $lastFourDays = [3, 4, 5, 6]; // الأربعاء - السبت

        $doctorIndex = 1;

        foreach ($departments as $departmentId) {
            for ($i = 0; $i < 4; $i++) {
                $firstName = $faker->firstNameMale;
                $lastName = $faker->lastName;
                $tr=new GoogleTranslate();
                $tr->setSource('en');
                $tr->setTarget('ar');
                $doctor = Employee::create([
                    'first_name' => ['en'=>$firstName, 'ar'=>$tr->translate($firstName)],
                    'last_name' => ['en'=>$lastName,'ar'=>$tr->translate($lastName)],
                    'email' => "heba.doc.200{$doctorIndex}@gmail.com",
                    'password' => Hash::make('password'),
                    'role' => 'doctor',
                    'salary' => 5000 + $doctorIndex * 100,
                ]);
                $doctor->assignRole($doctorRole);

                // الترجمة للاختصاص
                $specialistEn = $departmentSpecialists[$departmentId];
                $specialistAr = $tr->translate($specialistEn);

                $years_of_experience = 3 + $doctorIndex;

                // الترجمة للبايو
                $bioEn = "Dr. $firstName $lastName has over $years_of_experience years of experience in $specialistEn.";
                $bioAr = $tr->translate($bioEn);

                $doctorModel = Doctor::create([
                    'employee_id' => $doctor->id,
                    'department_id' => $departmentId,
                    'certificate' => "Certificate $doctorIndex",
                    'years_of_experience' => $years_of_experience,
                    'medical_license_number' => "MLN-1000$doctorIndex",
                    'image' => "doctors/doctor$doctorIndex.png",
                    'specialist' => ['en' => $specialistEn, 'ar' => $specialistAr],
                    'number_of_treatments' => $faker->numberBetween(0, 100),
                    'bio' => ['en' => $bioEn, 'ar' => $bioAr],
                ]);

                $isMorning = ($i % 2 === 0);
                $start_time = $isMorning ? '09:00:00' : '14:00:00';
                $end_time = $isMorning ? '13:00:00' : '18:00:00';

                $time = Time::create([
                    'employee_id' => $doctor->id,
                    'start_time' => $start_time,
                    'end_time' => $end_time,
                ]);

                $days = $i < 2 ? $firstThreeDays : $lastFourDays;
                foreach ($days as $day_id) {
                    DB::table('day_time')->insert([
                        'time_id' => $time->id,
                        'day_id' => $day_id,
                    ]);
                }

                // ربط الـ doctor بـ available_slots حسب فترة دوامه
                $slotIds = $isMorning ? range(1, 8) : range(9, 16);
                DB::table('available_slot_doctor')->insert(
                    array_map(function ($slotId) use ($doctorModel) {
                        return [
                            'doctor_id' => $doctorModel->id,
                            'available_slot_id' => $slotId
                        ];
                    }, $slotIds)
                );

                $doctorIndex++;
            }
        }// Receptionists
        for ($j = 1; $j <= 8; $j++) {
            $receptionist = Employee::create([
                'first_name' => "ReceptionistFirst$j",
                'last_name' => "ReceptionistLast$j",
                'email' => "hebaxxg$j@gmail.com",
                'password' => Hash::make('password'),
                'role' => 'receptionist',
                'salary' => 3000 + $j * 100,
            ]);
            $receptionist->assignRole($receptionistRole);
        }
    }
}
