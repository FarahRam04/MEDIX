<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DepartmentsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('departments')->insert([
            [
                'name'=>'General'
            ],
            [
                'name'=>'Cardiology'
            ],
            [
                'name'=>'Dermatology'
            ],
            [
                'name'=>'Gastroenterology'
            ],
            [
                'name'=>'Neurology'
            ],
            [
                'name'=>'Pediatric'
            ]

        ]);
    }
}
