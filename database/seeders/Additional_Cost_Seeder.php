<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class Additional_Cost_Seeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('additional__costs')->insert([
            [
                'title'=>'Deep Teeth Cleaning',
                'price'=>15000,
                'appointment_id'=>1,
            ],
            [
                'title'=>'Hollywood Smile',
                'price'=>20000,
                'appointment_id'=>1,
            ],
            [
                'title'=>'ECG',
                'price'=>10000,
                'appointment_id'=>2,
            ],
            [
                'title'=>'Blood Sugar Analysis',
                'price'=>5000,
                'appointment_id'=>2,
            ],
            [
                'title'=>'Local Anesthesia Injection',
                'price'=>17500,
                'appointment_id'=>3,
            ],

        ]);
    }
}
