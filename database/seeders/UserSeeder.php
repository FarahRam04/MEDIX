<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Patient;
use Illuminate\Support\Facades\Hash;
use Faker\Factory as Faker;

class UserSeeder extends Seeder
{
    public function run()
    {
        $faker = Faker::create();

        for ($i = 1; $i <= 10; $i++) {
            $user = User::create([
                'first_name' => $faker->firstName,
                'last_name' => $faker->lastName,
                'gender' => $faker->randomElement(['male', 'female']),
                'birth_date' => $faker->date('Y-m-d', '-18 years'),
                'phone_number' => $faker->phoneNumber,
                'email' => "user$i@example.com",
                'password' => Hash::make('password'),
                'is_patient' => true,
                'image' => null,
                'fcm_token' => $faker->uuid, // أو أي نص وهمي
                'fcm_token_updated_at' => now(),


            ]);

            Patient::create([
                'user_id' => $user->id,
                'heart_rate'=>'100',
    'blood_group'=>'A+',
    'temperature'=>'37.5',
    'weight'=>'60',
    'height'=>'165',
    'pressure'=>'120/80',
    'blood_sugar'=>'30'
            ]);
        }
    }
}
